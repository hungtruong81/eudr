<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductLot;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpNotFoundException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ExportProductLotAction extends ProductLotAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        $product_lot_code = addslashes(trim((string)$this->resolveArg('code')));

        $product_lot = $this->productLotRepository->findProductLotOfCode($product_lot_code);
        if (empty($product_lot)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy lô hàng");
        }

        // Get factory info
        $factory_name = '';
        $factory = $this->factoryRepository->findFactoryOfId($product_lot->getFactoryId());
        if ($factory) {
            $factory_name = $factory->getName();
        }

        // Get traceability data (farms + tickets)
        $farms = $this->productLotRepository->traceProductLotToFarms($product_lot->getId());

        // Load template
        $templatePath = __DIR__ . '/../../../../public/SAMPLE_HA_EUDR.xlsx';
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // Fill header cells
        $lotData = $product_lot->jsonSerialize();
        $sheet->setCellValue('D2', strtoupper($product_lot->getCode()));
        $sheet->setCellValue('D4', $factory_name);
        $sheet->setCellValue('I3', $product_lot->getTotalWeight());

        $productionDate = $lotData['production_date_from'] ?? '';
        if (!empty($lotData['production_date_to']) && $lotData['production_date_to'] !== $productionDate) {
            $productionDate .= ' - ' . $lotData['production_date_to'];
        }
        $sheet->setCellValue('I4', $productionDate);

        // Write farm data starting from row 11
        $rowNum = 11;
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'wrapText' => true,
                'vertical' => Alignment::VERTICAL_TOP,
            ],
        ];

        // Apply top alignment to row 10 as well
        $sheet->getStyle('A10:L10')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        foreach ($farms as $farm) {
            // Parse coordinates JSON
            $coordinates = $farm['coordinates'] ?? '';
            $coordsArr = is_string($coordinates) ? (json_decode($coordinates, true) ?: []) : [];

            // Format coordinates as multi-line text (lng, lat per line)
            $coordsTextParts = [];
            $polygon = [];
            foreach ($coordsArr as $c) {
                $lng = (float)($c['lng'] ?? $c['longitude'] ?? 0);
                $lat = (float)($c['lat'] ?? $c['latitude'] ?? 0);
                $coordsTextParts[] = $lng . ", " . $lat;
                $polygon[] = [$lng, $lat];
            }
            $coordsText = implode("\n", $coordsTextParts);

            // Close polygon for GeoJSON
            if (count($polygon) > 2) {
                $first = $polygon[0];
                $last = end($polygon);
                if ($first !== $last) {
                    $polygon[] = $first;
                }
            }

            // Build GeoJSON
            $geoJson = '';
            if (!empty($polygon)) {
                $geoJson = json_encode([
                    "type" => "FeatureCollection",
                    "features" => [[
                        "type" => "Feature",
                        "properties" => [
                            "ProducerCountry" => $farm['country'] ?? 'VN',
                            "ProductionPlace" => $farm['plot_code'],
                            "Plantation" => $farm['plot_name'],
                            "Plot" => $farm['plot_id'],
                            "Area_hectare" => $farm['land_area'],
                        ],
                        "id" => $farm['plot_id'],
                        "geometry" => [
                            "type" => "Polygon",
                            "coordinates" => [$polygon]
                        ]
                    ]]
                ], JSON_UNESCAPED_UNICODE);
            }

            // Legality / deforestation check based on eudr_status
            $legalityCheck = ($farm['eudr_status'] ?? 0) ? 'Yes' : 'No';
            $deforestationCheck = ($farm['eudr_status'] ?? 0) ? 'Yes' : 'No';

            $tickets = $farm['transaction_tickets'] ?? [];

            if (empty($tickets)) {
                // Write one row with just farm data (no ticket info)
                $sheet->setCellValue('D' . $rowNum, strtoupper($farm['plot_code']));
                $sheet->setCellValue('E' . $rowNum, $farm['plot_name']);
                $sheet->setCellValue('G' . $rowNum, $farm['land_area']);
                $sheet->setCellValue('H' . $rowNum, $legalityCheck);
                $sheet->setCellValue('I' . $rowNum, $deforestationCheck);
                $sheet->setCellValue('J' . $rowNum, $coordsText);
                $sheet->setCellValue('K' . $rowNum, $geoJson);
                $sheet->getStyle('A' . $rowNum . ':L' . $rowNum)->applyFromArray($borderStyle);
                $rowNum++;
            } else {
                // One row per ticket-farm combination
                foreach ($tickets as $ticket) {
                    $sheet->setCellValue('A' . $rowNum, strtoupper($ticket['transaction_ticket_code'] ?? ''));
                    $sheet->setCellValue('B' . $rowNum, $ticket['actual_harvest_date'] ?? $ticket['estimated_harvest_date'] ?? '');
                    $sheet->setCellValue('D' . $rowNum, strtoupper($farm['plot_code']));
                    $sheet->setCellValue('E' . $rowNum, $farm['plot_name']);
                    $sheet->setCellValue('G' . $rowNum, $farm['land_area']);
                    $sheet->setCellValue('H' . $rowNum, $legalityCheck);
                    $sheet->setCellValue('I' . $rowNum, $deforestationCheck);
                    $sheet->setCellValue('J' . $rowNum, $coordsText);
                    $sheet->setCellValue('K' . $rowNum, $geoJson);
                    $sheet->getStyle('A' . $rowNum . ':L' . $rowNum)->applyFromArray($borderStyle);
                    $rowNum++;
                }
            }
        }

        // Write to temp file then stream via Slim Response (preserves CORS middleware)
        $filename = 'DDS_' . strtoupper($product_lot->getCode()) . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        $fileContent = file_get_contents($tempFile);
        unlink($tempFile);

        $this->response->getBody()->write($fileContent);

        return $this->response
            ->withHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->withHeader('Content-Length', (string)strlen($fileContent))
            ->withHeader('Cache-Control', 'max-age=0');
    }
}
