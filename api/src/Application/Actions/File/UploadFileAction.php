<?php

declare(strict_types=1);

namespace App\Application\Actions\File;

use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\File\FileErrorException;
use App\Application\Utility\Utils;
use JBZoo\Image\Image;
use App\Application\Utility\VN2000Converter;
use Aws\Textract\TextractClient;
use Google\Cloud\DocumentAI\V1\Client\DocumentProcessorServiceClient;
use Google\Cloud\DocumentAI\V1\RawDocument;
use Google\Cloud\DocumentAI\V1\ProcessRequest;
use Google\ApiCore\ApiException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;


class UploadFileAction extends FileAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        // Check Authentication
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        $formData = $this->getFormData();

        $detection = false;
        if(!empty($formData['detection']) && $formData['detection'] === 'true') {
            $detection = true;
        }

        $zone_id = 0;
        if(!empty($formData['zone_id'])) {
            $zone_id = (int)$formData['zone_id'];
        }

        if (empty($_FILES['file']['name']) || !empty($_FILES['file']['error'])) {
            throw new HttpBadRequestException($this->request, "Thiếu trường dữ liệu file");
        }

        $upload_files = ["files" => [$_FILES['file']]];
        $output_files = [];

        foreach ($upload_files["files"] as $upload_file) {

            if ($upload_file["error"] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($upload_file["name"], PATHINFO_EXTENSION));

                if($detection === true && !in_array($ext, ["jpg", "jpeg", "png"])) {
                    throw new HttpBadRequestException($this->request, "Chỉ hỗ trợ định dạng ảnh (JPG, JPEG, PNG) cho tính năng phát hiện tọa độ");
                }

                if (!in_array($ext, ["jpg", "jpeg", "png", "pdf", "mp4", "mov", "m4v"])) {
                    throw new HttpBadRequestException($this->request, "Chỉ hỗ trợ các định dạng JPG, JPEG, PNG, PDF, MP4, MOV, M4V");
                }

                $file_name = Utils::generateRandomString(25).'-'.Utils::convert_file_name(Utils::remove_utf8($upload_file["name"]));
                $folder = 'uploads/' . Utils::folder_file(__DIR__);
                
                $file_path = $folder . '/' . $file_name;
                $file_type = $upload_file["type"];
                $file_size = $upload_file["size"];
                $full_file = $upload_file["tmp_name"];

                // Tạo thư mục nếu chưa tồn tại
                if (!is_dir($folder)) {
                    mkdir($folder, 0775, true);
                }

                // Lưu file vào server
                move_uploaded_file($full_file, $file_path);

                // Resize ảnh nếu là ảnh
                $image_size = '';
                $size = @getimagesize($file_path);
                if ($ext === "jpg" || $ext === "jpeg" || $ext === "png") {
                    $image_size = $size[0] . "x" . $size[1];

                    $save_thumb_file = $folder . '/thumb_' . $file_name;
                    (new Image($file_path))->bestFit(800, 800)->saveAs($save_thumb_file);
                }

                $file_code = $this->fileRepository->generateCode();
                $data_update = [
                    "file_code" => $file_code,
                    "user_id" => $this->auth_data['user_id'],
                    "file_name" => $file_name,
                    "file_path" => $file_path,
                    "file_type" => $file_type,
                    "file_size" => $file_size,
                    "image_size" => $image_size,
                    "folder" => $folder,
                    "detection" => $detection ? 1 : 0,
                    "text_content" => '',
                    "created_at" => date('Y-m-d H:i:s'),
                    "updated_at" => date('Y-m-d H:i:s'),
                ];

                $file = $this->fileRepository->createFile($data_update);

                // Tạo thumbnail cho video
                if ($file->getFileType() == 'video') {
                    $video_thumbnail_path = $folder . '/' . $file->getId() . '_thumb.jpg';
                    $thumbnail_file_path = '/tmp/' . $file->getId() . '_thumb.jpg';

                    $cmd = "ffmpeg -i " . escapeshellarg($file_path) . " -ss 00:00:01.000 -vframes 1 " . escapeshellarg($thumbnail_file_path);
                    exec($cmd, $output, $retval);

                    $video_thumbnail_error = $retval ? 'Error in generating video thumbnail' : '';

                    if (!$retval) {
                        copy($thumbnail_file_path, $video_thumbnail_path);
                    } else {
                        $video_thumbnail_path = '';
                    }

                    $this->fileRepository->updateFile($file->getId(), [
                        "video_thumbnail_path" => $video_thumbnail_path,
                        "video_thumbnail_error" => $video_thumbnail_error,
                    ]);
                }

                $output_file = $file->jsonSerialize();
                $output_file["file_path_full"] = $this->settings->get('url_cdn').'/'.$file_path;

                if (!empty($output_file["video_thumbnail_path"])) {
                    $output_file["video_thumbnail_path_full"] = $output_file["video_thumbnail_path"];
                }

                $output_files[] = $output_file;

                // Ghi log
                $log = [
                    "milliseconds" => floor(microtime(true) * 1000),
                    "trace_id" => $trace_id,
                    "log_type" => 'file',
                    "action" => 'create',
                    "user_id" => (string)$this->auth_data['user_id'],
                    "extra_1" => (string)$file->getId(),
                ];
                Utils::save_log($this->logger, $log);
            }
        }

        // Request AWS Textract API for OCR
        $coordinates = [];

        if($detection === true && !empty($output_files[0])) {
            
            $filePath = $output_files[0]['file_path'];

            $awsKey = $this->settings->get('s3')["AWS_S3_key"];
            $awsSecret = $this->settings->get('s3')["AWS_S3_secret"];;
            $awsRegion = 'ap-southeast-1';

            // Khởi tạo Textract client
            $client = new TextractClient([
                'version'     => 'latest',
                'region'      => $awsRegion,
                'credentials' => [
                    'key'    => $awsKey,
                    'secret' => $awsSecret
                ]
            ]);

            // Đọc file ảnh/PDF
            $fileBytes = file_get_contents($filePath);

            // Gọi Textract để lấy bảng
            $result = $client->analyzeDocument([
                'Document' => [
                    'Bytes' => $fileBytes
                ],
                'FeatureTypes' => ['TABLES']
            ]);

            $blocks = $result['Blocks'];
            
            $blockMap = [];
            foreach ($blocks as $block) {
                $blockMap[$block['Id']] = $block;
            }

            // Duyệt từng bảng
            foreach ($blocks as $block) {
                if ($block['BlockType'] === 'TABLE') {
                    $rows = [];
                    
                    // Lấy tất cả CELL và group theo hàng/cột
                    if (!empty($block['Relationships'])) {
                        foreach ($block['Relationships'] as $rel) {
                            if ($rel['Type'] === 'CHILD') {
                                foreach ($rel['Ids'] as $cellId) {
                                    $cell = $blockMap[$cellId] ?? null;
                                    if ($cell && $cell['BlockType'] === 'CELL') {
                                        $rowIndex = $cell['RowIndex'];
                                        $colIndex = $cell['ColumnIndex'];
                                        $text = Utils::getText($cell, $blockMap);
                                        $rows[$rowIndex][$colIndex] = $text;
                                    }
                                }
                            }
                        }
                    }

                    

                    // Chuyển rows thành mảng liên tiếp (1-based row index)
                    // ksort($rows);
                    // foreach ($rows as &$cols) {
                    //     ksort($cols);
                    // }

                    
                    // Kiểm tra header xem có chứa X và Y không
                    $header = array_map('strtolower', $rows[2] ?? []);
                    
                    if (in_array('x', $header) && in_array('y', $header)) {
                        echo "=== Bảng tọa độ phát hiện ===\n";

                        foreach ($rows as $i => $cols) {
                            if ($i < 3) continue; // bỏ header

                            // Tìm vị trí cột X và Y trong header (phòng khi không cố định vị trí)
                            //$xIndex = array_search('x', $header);
                            //$yIndex = array_search('y', $header);

                            $x = $cols[2] ?? '';
                            $y = $cols[3] ?? '';
                            
                            if ($x !== '' && $y !== '') {
                                $coordinates[] = [
                                    'x' => (float)$x,
                                    'y' => (float)$y
                                ];
                            }
                        }
                    }

                    if (!empty($coordinates)) {
                        $platform = 'aws';
                        break; // Dừng khi đã tìm thấy tọa độ
                    }
                }
            } 
             
        }

        // Nếu AWS không trả về tọa độ thì tiếp tục call qua google document AI
        if (empty($coordinates) && $detection === true && !empty($output_files[0])) {

            $projectId = $this->settings->get('google')['project_id'];
            $location = $this->settings->get('google')['location'];
            $processorId = $this->settings->get('google')['processor_id'];
            $credentialsPath = __DIR__ . '/../../../../config/' . $this->settings->get('google')['application_credentials'];
            $filePath = $output_files[0]['file_path'];

            putenv("GOOGLE_APPLICATION_CREDENTIALS={$credentialsPath}"); 

            try {
                $client = new DocumentProcessorServiceClient();
                $processorName = $client->processorName($projectId, $location, $processorId);

                $content = file_get_contents($filePath);
                $rawDocument = new RawDocument([
                    'content' => $content,
                    //'mime_type' => 'image/jpeg', // hoặc 'application/pdf'
                    'mime_type' => $file_type,
                ]);

                $request = new ProcessRequest([
                    'name' => $processorName,
                    'raw_document' => $rawDocument,
                ]);

                $response = $client->processDocument($request);
                $document = $response->getDocument();
                $text = $document->getText();
                $entities = $document->getEntities();
                // Log toàn bộ nội dung và entity
                $d_text = '';
                foreach ($entities as $entity) {
                    if ($entity->getType() === 'coordinates') {
                        $d_text = $entity->getMentionText();
                        $platform = 'google';
                        break;
                    }
                }

                $coordinates = Utils::extractCoordinates($d_text);
                
            } catch (ApiException $e) {
                $data_return = [
                    "result" => "error",
                    "message" => "Google API error: " . $e->getMessage(),
                ];
                //return $this->respondWithData($data_return);
            } catch (\Exception $e) {
                $data_return = [
                    "result" => "error",
                    "message" => "Internal error: " . $e->getMessage(),
                ];
                //return $this->respondWithData($data_return);
            }

        }

        // Chuyển đổi tọa độ VN2000 sang LatLng (WGS84)
        $lat_lng = [];
        if(!empty($coordinates) && !empty($zone_id)) {
            $this->db->where('zone_id', $zone_id);
            $zone = $this->db->getOne('eudr_general_vn2000_zones');
            if(empty($zone)) {
                throw new FileErrorException("Không tìm thấy vùng", 102);
            }

            foreach($coordinates as $coord) {
                // $latLng = VN2000Converter::convertUTMToLatLng($coord['lng'], $coord['lat']);
                $latLng = VN2000Converter::convertVN2000ToWGS84($coord['x'], $coord['y'], (float)$zone['value']);
                $lat_lng[] = $latLng;
            }
        }

        $res_return = [
            "result" => "success",
            "trace_id" => $trace_id,
            "data" => [
                "file" => $output_files[0] ?? [],
                "detection" => [
                    "platform" => $platform ?? '',
                    "coordinates" => $coordinates ?? [],
                    "coordinates_converted" => $lat_lng ?? [],
                    //"error" => $data_return ?? [],
                ],
            ]
        ];

        return $this->respondWithData($res_return);
    }

}
