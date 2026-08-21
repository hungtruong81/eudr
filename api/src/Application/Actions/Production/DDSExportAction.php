<?php

declare(strict_types=1);

namespace App\Application\Actions\Production;

use Psr\Http\Message\ResponseInterface as Response;
use App\Application\Utility\Utils;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DDSExportAction extends ProductionAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);
        /*
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        // Check permission to export DDS        
        $scope = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'production', 'dds_export');
        
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }
        */
        $formData = $this->request->getQueryParams();

        $sale_contract_id = $formData['sale_contract_id'] ?? null;
        $product_lot_id = $formData['product_lot_id'] ?? null;

        // Lấy dữ liệu từ bảng eudr_lands
        $lands = $this->db->get("eudr_lands", null, [
            "plot_id",
            "plot_code",
            "plot_name",
            "land_area",
            "coordinates" // JSON lưu trong DB
        ]);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'plot_id');
        $sheet->setCellValue('B1', 'plot_code');
        $sheet->setCellValue('C1', 'plot_name');
        $sheet->setCellValue('D1', 'land_area');
        $sheet->setCellValue('E1', 'coordinates');
        $sheet->setCellValue('F1', 'GeoJson');

        $rowNum = 2;

        foreach ($lands as $land) {
            $coords = json_decode($land['coordinates'], true) ?: [];

            // Ghép lại thành chuỗi nhiều dòng
            $coordsTextArr = [];
            $polygon = [];
            foreach ($coords as $c) {
                $lng = (float)$c['lng'];
                $lat = (float)$c['lat'];
                $coordsTextArr[] = $lng . ", " . $lat;
                $polygon[] = [$lng, $lat];
            }
            $coordsText = implode("\n", $coordsTextArr);

            // Đảm bảo polygon đóng kín
            if (count($polygon) > 2) {
                $first = $polygon[0];
                $last  = end($polygon);
                if ($first !== $last) {
                    $polygon[] = $first;
                }
            }

            // Build GeoJSON
            $geoJson = [
                "type" => "FeatureCollection",
                "features" => [[
                    "type" => "Feature",
                    "properties" => [
                        "ProducerName"       => "DONARUCO",
                        "ProducerCountry"    => "VN",
                        "ProductionPlace"    => $land['plot_code'],
                        "Plantation"         => $land['plot_name'],
                        "Plot"               => $land['plot_id'],
                        "Planting_year"      => null,
                        "Area_hectare"       => (float)$land['land_area'],
                        "Start_tapping_Year" => null,
                        "Find"               => $land['plot_code'] . " " . $land['plot_name']
                    ],
                    "id" => $land['plot_id'],
                    "geometry" => [
                        "type" => "Polygon",
                        "coordinates" => [$polygon]
                    ]
                ]]
            ];

            // Ghi vào Excel
            $sheet->setCellValue('A' . $rowNum, $land['plot_id']);
            $sheet->setCellValue('B' . $rowNum, $land['plot_code']);
            $sheet->setCellValue('C' . $rowNum, $land['plot_name']);
            $sheet->setCellValue('D' . $rowNum, $land['land_area']);
            $sheet->setCellValue('E' . $rowNum, $coordsText);
            $sheet->setCellValue('F' . $rowNum, json_encode($geoJson, JSON_UNESCAPED_UNICODE));

            // Cho phép xuống dòng trong ô Excel
            $sheet->getStyle('E' . $rowNum)->getAlignment()->setWrapText(true);

            $rowNum++;
        }

        // Xuất file trực tiếp ra trình duyệt
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="plots_export.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;

        return $this->respondWithData($formData);
    }
}
