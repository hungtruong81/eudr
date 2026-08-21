<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductLot;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportProductLotAction extends ProductLotAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'finished_goods_receipt', 'create');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $formData = $this->getFormData();

        $supplierCompanyName = trim((string)($formData['supplier_company_name'] ?? ''));
        if ($supplierCompanyName === '') {
            throw new HttpBadRequestException($this->request, 'supplier_company_name là bắt buộc');
        }

        $productLot = $this->handleEudrImport($formData);

        $log = [
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id"     => $trace_id,
            "log_type"     => 'product_lot',
            "action"       => 'import_eudr',
            "user_id"      => (string)$this->auth_data['user_id'],
            "extra_1"      => (string)$productLot->getId(),
        ];
        Utils::save_log($this->logger, $log);

        return $this->respondWithData([
            "result"   => "success",
            "trace_id" => $trace_id,
            "data"     => $productLot->jsonSerialize(),
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    // Branch A — EUDR (có vườn): parse Excel + link eudr_lands
    // ════════════════════════════════════════════════════════════════════════
    private function handleEudrImport(array $formData): \App\Domain\ProductLot\ProductLot
    {
        $uploadedFiles = $this->request->getUploadedFiles();
        $file = $uploadedFiles['file'] ?? null;
        if (empty($file) || $file->getError() !== UPLOAD_ERR_OK) {
            throw new HttpBadRequestException($this->request, "Vui lòng tải lên file Excel (.xlsx/.xls)");
        }

        $ext = strtolower(pathinfo((string)$file->getClientFilename(), PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls'], true)) {
            throw new HttpBadRequestException($this->request, "File phải có định dạng .xlsx hoặc .xls");
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'import_eudr_');
        $file->moveTo($tempFile);

        try {
            $spreadsheet = IOFactory::load($tempFile);
            $sheet = $spreadsheet->getActiveSheet();

            // Header data
            $original_lot_code     = trim((string)$sheet->getCell('D2')->getValue());
            $supplier_factory_name = trim((string)$sheet->getCell('D4')->getValue());
            $total_weight          = (float)$sheet->getCell('I3')->getValue();
            $production_date_raw   = trim((string)$sheet->getCell('I4')->getValue());

            [$production_date_from, $production_date_to] = $this->parseDateRange($production_date_raw);

            // Farm rows
            $landsData = $this->parseFarmRowsFromSheet($sheet);
            if (empty($landsData)) {
                throw new HttpBadRequestException($this->request, "Không tìm thấy dữ liệu vườn trong file");
            }

            $transport_data = (!empty($formData['transport']) && is_array($formData['transport']))
                ? $formData['transport']
                : null;

            $lotData = [
                'product_lot_code'        => $this->productLotRepository->generateExternalCode(),
                'lot_type'                => 'external',
                'eudr_type'               => 'eudr',
                'grade'                   => trim($formData['grade'] ?? ''),
                'factory_id'              => (int)($formData['factory_id'] ?? 0),
                'owner_company_id'        => (int)$this->auth_data['company_id'],
                'owner_id'                => (int)$this->auth_data['user_id'],
                'supplier_company_name'   => trim($formData['supplier_company_name'] ?? ''),
                'supplier_factory_name'   => $supplier_factory_name ?: trim($formData['supplier_factory_name'] ?? ''),
                'supplier_phone'          => trim($formData['supplier_phone'] ?? ''),
                'supplier_address'        => trim($formData['supplier_address'] ?? ''),
                'original_product_lot_code' => $original_lot_code,
                'external_contract_code'  => trim($formData['external_contract_code'] ?? ''),
                'production_date_from'    => $production_date_from,
                'production_date_to'      => $production_date_to,
                'total_blocks'            => (int)($formData['total_blocks'] ?? 0),
                'total_weight'            => $total_weight ?: (float)($formData['total_weight'] ?? 0),
                'purchase_date'           => $formData['purchase_date'] ?? date('Y-m-d'),
                'purchase_amount'         => (float)($formData['purchase_amount'] ?? 0),
                'notes'                   => trim($formData['notes'] ?? ''),
                'status'                  => 'draft',
                'created_by'              => (int)$this->auth_data['user_id'],
                'lands'                   => $landsData,
                'transport'               => $transport_data,
            ];

            $productLot = $this->productLotRepository->createExternalProductLot($lotData);
            if (empty($productLot)) {
                throw new HttpBadRequestException($this->request, "Tạo lô hàng thất bại");
            }

            return $productLot;
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    // Private helpers
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Parse farm rows from an Excel sheet
     * Finds or auto-creates eudr_lands records and returns the lands data array.
     */
    private function parseFarmRowsFromSheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): array
    {
        $landsData     = [];
        $plotCodesSeen = [];
        $rowNum        = 11;
        $highestRow    = $sheet->getHighestRow();
        $emptyStreak   = 0;

        while ($rowNum <= $highestRow) {
            $plotCode = trim((string)$sheet->getCell('D' . $rowNum)->getValue());

            if (empty($plotCode)) {
                $emptyStreak++;
                if ($emptyStreak >= 3) {
                    break;
                }
                $rowNum++;
                continue;
            }
            $emptyStreak = 0;

            $upperCode = strtoupper($plotCode);
            if (in_array($upperCode, $plotCodesSeen, true)) {
                $rowNum++;
                continue;
            }
            $plotCodesSeen[] = $upperCode;

            $plotName  = trim((string)$sheet->getCell('E' . $rowNum)->getValue());
            $landArea  = (float)$sheet->getCell('G' . $rowNum)->getValue();
            $coordsText = trim((string)$sheet->getCell('J' . $rowNum)->getValue());

            $coordinates = [];
            if (!empty($coordsText)) {
                foreach (preg_split('/\r?\n/', $coordsText) as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;
                    $parts = explode(',', $line, 2);
                    if (count($parts) === 2) {
                        $coordinates[] = [
                            'lng' => (float)trim($parts[0]),
                            'lat' => (float)trim($parts[1]),
                        ];
                    }
                }
            }

            $existingLand = $this->landRepository->findLandOfCode($upperCode);
            $plotId = 0;

            if ($existingLand) {
                $plotId = $existingLand->getId();
            } else {
                $newLand = $this->landRepository->createLand([
                    'plot_code'      => $upperCode,
                    'plot_name'      => $plotName,
                    'land_area'      => $landArea,
                    'coordinates'    => !empty($coordinates) ? json_encode($coordinates) : null,
                    'register_type'  => 'product_lot_external',
                    'company_id'     => (int)$this->auth_data['company_id'],
                    'company_name'   => $this->auth_data['company_name'] ?? '',
                    'farmer_user_id' => (int)$this->auth_data['user_id'],
                    'farmer_name'    => $this->auth_data['full_name'] ?? '',
                    'country'        => 'VN',
                    'status'         => 'active',
                    'created_by'     => (int)$this->auth_data['user_id'],
                    'created_at'     => date('Y-m-d H:i:s'),
                ]);
                if ($newLand) {
                    $plotId = $newLand->getId();
                }
            }

            if ($plotId > 0) {
                $landsData[] = [
                    'plot_id'        => $plotId,
                    'harvest_weight' => 0,
                    'notes'          => '',
                ];
            }

            $rowNum++;
        }

        return $landsData;
    }



    /**
     * Parse a production date range string into [from, to].
     * Accepts "dd/mm/yyyy - dd/mm/yyyy" or a single date.
     *
     * @return array{0: string|null, 1: string|null}
     */
    private function parseDateRange(string $raw): array
    {
        if (empty($raw)) {
            return [null, null];
        }
        if (str_contains($raw, ' - ')) {
            $parts = explode(' - ', $raw, 2);
            return [trim($parts[0]), trim($parts[1])];
        }
        return [$raw, $raw];
    }

}
