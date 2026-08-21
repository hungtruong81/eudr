<?php

declare(strict_types=1);

namespace App\Application\Actions\General;

use Psr\Http\Message\ResponseInterface as Response;
use App\Application\Utility\Utils;
use Aws\Textract\TextractClient;


class AWSTextractAction extends GeneralAction
{
    protected function action(): Response
    {

        $awsKey = $_ENV['AWS_TEXTRACT_KEY'] ?? getenv('AWS_TEXTRACT_KEY') ?: '';
        $awsSecret = $_ENV['AWS_TEXTRACT_SECRET'] ?? getenv('AWS_TEXTRACT_SECRET') ?: '';
        $awsRegion = $_ENV['AWS_TEXTRACT_REGION'] ?? getenv('AWS_TEXTRACT_REGION') ?: 'ap-southeast-1';

        if ($awsKey === '' || $awsSecret === '') {
            throw new \RuntimeException('AWS Textract credentials are not configured.');
        }

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
        //$filePath = 'toado.pdf'; // đổi thành file của bạn
        $filePath = __DIR__ . '/../../../../config/so-do-dat-3.png';
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
                
                $coordinates = [];
                
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
                    break; // Dừng khi đã tìm thấy tọa độ
                }
            }
        }

        $data_return = [
            'coordinates' => $coordinates,
        ];

        return $this->respondWithData($data_return);

    }
}
