<?php

declare(strict_types=1);

namespace App\Application\Actions\File;

use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\File\FileErrorException;
use App\Application\Utility\Utils;
use JBZoo\Image\Image;

class UploadFileAction extends FileAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);
        
        if ($this->auth->type != 'auth') {
            throw new FileErrorException("PERMISSION_DENIED", 113);
        }
        
        $formData = $this->getFormData();
        

        // Validate data fields
        /* $required_fields = [];
        $missing_fields = Utils::validFields($required_fields,$formData);
        if (!empty($missing_fields)) {
            throw new FileErrorException("MISSING ".implode(", ",$missing_fields), 101);
        } */
        if (empty($_FILES['file'])) {
            throw new FileErrorException("MISSING_FILES", 101);
        }

        //$upload_files = $this->request->getUploadedFiles();
        $upload_files = ["files" => [$_FILES['file']]];

        // Validate
        $image_size = '';
        // Upload and copy all files to server
        foreach ($upload_files["files"] as $upload_file) {
            $file_name = strtolower($upload_file["name"]);
            $ext = pathinfo($file_name, PATHINFO_EXTENSION);
            if (!in_array($ext, ["jpg","jpeg","png","pdf","mp4","mov","m4v"])) {
                throw new FileErrorException("FILE_NOT_SUPPORT", 101);
            }

        }

        $AWS_S3_bucket = $this->settings->get('s3')["AWS_S3_bucket"];

        $output_files = [];
        // Upload and copy all files to server
        foreach ($upload_files["files"] as $upload_file) {
            if ($upload_file["error"] === UPLOAD_ERR_OK) {
                
                $file_name = Utils::remove_utf8($upload_file["name"]);
                $file_name = Utils::convert_file_name($file_name);
                $folder = Utils::folder_file(__DIR__);
                $file_path = $folder.'/'.$file_name;
                $ext = pathinfo($file_name, PATHINFO_EXTENSION);
                $file_type = $upload_file["type"];
                $file_size = $upload_file["size"];
                $full_file = $upload_file["tmp_name"];


                $file_item["file_path"] = $file_path;

                $s3_file_link = '';
                // Upload file to S3 bucket
                try {
                    $result = $this->s3->putObject([
                        'Bucket' => $AWS_S3_bucket,
                        'Key'    => $file_path,
                        //'ACL'    => 'public-read',
                        'SourceFile' => $full_file
                    ]);
                    $result_arr = $result->toArray();

                    if (!empty($result_arr['ObjectURL'])) {
                        $s3_file_link = $result_arr['ObjectURL'];

                    } else {
                        $api_error = 'Upload Failed! S3 Object URL not found.';
                    }
                } catch (\Aws\S3\Exception\S3Exception $e) {
                    $api_error = $e->getMessage();
                }

                if (empty($s3_file_link)) {
                    throw new FileErrorException("UPLOAD_FAILED: ".$api_error, 101);
                }

                // Thumbnail
                $save_thumb_file = '/tmp/thumb_'.$file_name;
                $size = @GetImageSize($full_file);
                if (!empty($size)) {
                    $img = (new Image($full_file))
                        ->bestFit(800, 800)
                        ->saveAs($save_thumb_file);

                    $file_path = $folder.'/thumb_'.$file_name;
                    $file_item["thumb_path"] = $file_path;
                    // Upload file to S3 bucket
                    try {
                        $result = $this->s3->putObject([
                            'Bucket' => $AWS_S3_bucket,
                            'Key'    => $file_path,
                            'ACL'    => 'public-read',
                            'SourceFile' => $save_thumb_file
                        ]);
                        $result_arr = $result->toArray();

                        if (!empty($result_arr['ObjectURL'])) {
                            $s3_file_link_thumb = $result_arr['ObjectURL'];

                        } else {
                            $api_error = 'Upload Failed! S3 Object URL not found.';
                        }
                    } catch (\Aws\S3\Exception\S3Exception $e) {
                        $api_error = $e->getMessage();
                    }
                }

                // $ts_files[] = $file_item;

                $image_size = '';
                // get image size width and height
                $size = @GetImageSize($full_file);
                if ($ext == "jpg" || $ext == "jpeg" || $ext == "png") {
                    $image_size = $size[0]."x".$size[1];
                }

                $file_code = $this->fileRepository->generateCode(0);
                $data_update = [
                    "file_code" => $file_code,
                    "user_id" => $this->auth_data['user_id'],
                    "file_name" => $file_name,
                    "file_path" => $file_path,
                    "file_type" => $file_type,
                    "file_size" => $file_size,
                    "image_size" => $image_size,
                    "folder" => $folder,
                    "text_content" => '',
                    "created_at" => date('Y-m-d H:i:s', time()),
                    "updated_at" => date('Y-m-d H:i:s', time()),
                ];
                
                $file = $this->fileRepository->createFile($data_update);

                // Create thumbnail video
                if ($file->getFileType()=='video') {
                    $video_thumbnail_path = '';
                    $video_thumbnail_error = '';
                    $thumbnail_file_name = $file->getId().'_thumb.jpg';
                    $video_thumbnail_path = $folder.'/'.$thumbnail_file_name;
                    $videoFilePath = '/tmp/'.$file_name;
                    $thumbnail_file_path = '/tmp/'.$thumbnail_file_name;

                    // copy file $full_file to new location new name
                    copy($full_file, $videoFilePath);

                    // $cmd = "ffmpeg -i $videoFilePath -deinterlace -an -ss $second -t 00:00:01 -s 300x300 -r 1 -y -vcodec mjpeg -f mjpeg $video_thumbnail_path 2>&1";
                    // $cmd = "ffmpeg -i $videoFilePath -vf \"bwdif=mode=send_field:parity=auto:deint=all\" -an -ss $second -t 00:00:01 -r 1 -y -vcodec mjpeg -f mjpeg $thumbnail_file_path 2>&1";
                    $cmd = "ffmpeg -i $videoFilePath -ss 00:00:01.000 -vframes 1 $thumbnail_file_path";

                    exec($cmd, $output, $retval);
                    if ($retval) {
                        $video_thumbnail_error = 'Error in generating video thumbnail';
                        $video_thumbnail_path = '';
                    } else {
                        $s3_file_link = '';
                        // Upload file to S3 bucket
                        try {
                            $result = $this->s3->putObject([
                                'Bucket' => $AWS_S3_bucket,
                                'Key'    => $video_thumbnail_path,
                                'ACL'    => 'private',
                                'SourceFile' => $thumbnail_file_path
                            ]);
                            $result_arr = $result->toArray();

                            if (!empty($result_arr['ObjectURL'])) {
                                $s3_file_link = $result_arr['ObjectURL'];

                            } else {
                                $api_error = 'Upload Failed! S3 Object URL not found.';
                            }
                        } catch (\Aws\S3\Exception\S3Exception $e) {
                            $api_error = $e->getMessage();
                        }
                    }
                    $file = $this->fileRepository->updateFile($file->getId(), ["video_thumbnail_path" => $video_thumbnail_path,"video_thumbnail_error"=>$video_thumbnail_error]);
                }

                $output_file = $file->jsonSerialize();
                // $output_file["file_path_full"] = Utils::signed_url($this->s3, $AWS_S3_bucket, $output_file["file_path"], 3600*12);
                $output_file["file_path_full"] = $s3_file_link;
                if (!empty($output_file["video_thumbnail_path"])) {
                    // $output_file["video_thumbnail_path_full"] = Utils::signed_url($this->s3, $AWS_S3_bucket, $output_file["video_thumbnail_path"], 3600*12);
                    $output_file["video_thumbnail_path_full"] = $s3_file_link_thumb;
                }

                $output_files[] = $output_file;

                $action = 'create';
                $log = array(
                    "milliseconds" => floor(microtime(true) * 1000),
                    "trace_id" => $trace_id,
                    "log_type" => 'file',
                    "action" => $action,
                    "user_id" => (string)$this->auth_data['user_id'],
                    "extra_1" => (string)$file->getId(),
                );

                Utils::save_log($this->logger, $log);

            }
        }

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['data'] = [
            "file" => $output_files[0]??[],
        ];

        return $this->respondWithData($res_return);
    }
}
