<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductLot;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class ImportNonEudrProductLotAction extends ProductLotAction
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

        $validator = new Validator($this->request);
        $validator->validate('supplier_company_name', $formData['supplier_company_name'] ?? null, 'required|string|max:255');

        if ($validator->hasErrors()) {
            throw new HttpBadRequestException($this->request, $this->flattenErrors($validator->getErrors()));
        }

        $productLot = $this->handleNonEudrImport($formData);

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id'     => $trace_id,
            'log_type'     => 'product_lot',
            'action'       => 'import_non_eudr',
            'user_id'      => (string)$this->auth_data['user_id'],
            'extra_1'      => (string)$productLot->getId(),
        ];
        Utils::save_log($this->logger, $log);

        return $this->respondWithData([
            'result'   => 'success',
            'trace_id' => $trace_id,
            'data'     => $productLot->jsonSerialize(),
        ]);
    }

    private function handleNonEudrImport(array $formData): \App\Domain\ProductLot\ProductLot
    {
        // New input model: product_lots[] (1 hoặc nhiều), thay cho items[]
        $rawProductLots = $formData['product_lots'] ?? [];
        if (!is_array($rawProductLots)) {
            throw new HttpBadRequestException($this->request, 'product_lots phải là mảng');
        }
        // Support payload gửi 1 object duy nhất: { product_lots: { ... } }
        if (!empty($rawProductLots) && array_keys($rawProductLots) !== range(0, count($rawProductLots) - 1)) {
            $rawProductLots = [$rawProductLots];
        }
        if (empty($rawProductLots)) {
            throw new HttpBadRequestException($this->request, 'Phải có ít nhất một product lot trong product_lots');
        }

        $non_eudr_items = [];
        $lotCodeList = [];
        foreach ($rawProductLots as $idx => $lot) {
            if (!is_array($lot)) {
                throw new HttpBadRequestException($this->request, 'product_lots #' . ($idx + 1) . ' không hợp lệ');
            }

            // Map product_lots -> non_eudr_items để tương thích schema hiện tại
            $lotCode = trim((string)($lot['product_lot_code'] ?? $lot['original_product_lot_code'] ?? $lot['lot_code'] ?? ''));
            if ($lotCode === '') {
                throw new HttpBadRequestException($this->request, 'product_lots #' . ($idx + 1) . ' thiếu product_lot_code');
            }
            $lotCodeList[] = $lotCode;

            $non_eudr_items[] = [
                'item_name'  => $lotCode,
                'quantity'   => (float)($lot['quantity'] ?? 0),
                'unit'       => trim((string)($lot['unit'] ?? '')),
                'weight'     => (float)($lot['weight'] ?? 0),
                'sort_order' => $idx,
                'notes'      => trim((string)($lot['notes'] ?? '')),
            ];
        }

        $userId = (int)$this->auth_data['user_id'];

        // Files are uploaded via file module first, then this endpoint receives IDs.
        $contractFileIds = $formData['contract_file_ids'] ?? [];
        if (!is_array($contractFileIds)) {
            $contractFileIds = array_filter([(int)$contractFileIds]);
        }

        $attachments = [];
        foreach ($contractFileIds as $rawId) {
            $fileId = (int)$rawId;
            if ($fileId <= 0) {
                continue;
            }

            $file = $this->fileRepository->findFileOfId($fileId);
            if (empty($file)) {
                throw new HttpBadRequestException($this->request, "File hợp đồng ID {$fileId} không tồn tại");
            }

            $attachments[] = [
                'file_id'         => $fileId,
                'attachment_type' => 'contract',
                'label'           => null,
                'created_by'      => $userId,
            ];
        }

        $signatureFileId = (int)($formData['signature_file_id'] ?? 0);
        if ($signatureFileId <= 0) {
            throw new HttpBadRequestException($this->request, 'Vui lòng cung cấp signature_file_id (ID ảnh chữ ký điện tử)');
        }

        $signatureFile = $this->fileRepository->findFileOfId($signatureFileId);
        if (empty($signatureFile)) {
            throw new HttpBadRequestException($this->request, "File chữ ký ID {$signatureFileId} không tồn tại");
        }

        $attachments[] = [
            'file_id'         => $signatureFileId,
            'attachment_type' => 'signature',
            'label'           => null,
            'created_by'      => $userId,
        ];

        $originalProductLotCode = trim((string)($formData['original_product_lot_code'] ?? ''));
        if ($originalProductLotCode === '') {
            $originalProductLotCode = implode(', ', $lotCodeList);
        }

        $lotData = [
            'product_lot_code'          => $this->productLotRepository->generateExternalCode(),
            'lot_type'                  => 'external',
            'eudr_type'                 => 'non_eudr',
            'grade'                     => trim($formData['grade'] ?? ''),
            'factory_id'                => (int)($formData['factory_id'] ?? 0),
            'owner_company_id'          => (int)$this->auth_data['company_id'],
            'owner_id'                  => $userId,
            'supplier_company_name'     => trim($formData['supplier_company_name'] ?? ''),
            'supplier_factory_name'     => trim($formData['supplier_factory_name'] ?? ''),
            'supplier_phone'            => trim($formData['supplier_phone'] ?? ''),
            'supplier_address'          => trim($formData['supplier_address'] ?? ''),
            'original_product_lot_code' => $originalProductLotCode,
            'external_contract_code'    => trim($formData['external_contract_code'] ?? ''),
            'production_date_from'      => $formData['production_date_from'] ?? null,
            'production_date_to'        => $formData['production_date_to'] ?? ($formData['production_date_from'] ?? null),
            'total_blocks'              => (int)($formData['total_blocks'] ?? 0),
            'total_weight'              => (float)($formData['total_weight'] ?? 0),
            'purchase_date'             => $formData['purchase_date'] ?? date('Y-m-d'),
            'purchase_amount'           => (float)($formData['purchase_amount'] ?? 0),
            'notes'                     => trim($formData['notes'] ?? ''),
            'status'                    => 'draft',
            'created_by'                => $userId,
            'non_eudr_items'            => $non_eudr_items,
            'attachments'               => $attachments,
        ];

        $productLot = $this->productLotRepository->createExternalProductLot($lotData);
        if (empty($productLot)) {
            throw new HttpBadRequestException($this->request, 'Tạo lô hàng thất bại');
        }

        return $productLot;
    }

    private function flattenErrors(array $errors): string
    {
        $messages = [];
        foreach ($errors as $fieldErrors) {
            if (is_array($fieldErrors)) {
                foreach ($fieldErrors as $msg) {
                    $messages[] = (string)$msg;
                }
            } else {
                $messages[] = (string)$fieldErrors;
            }
        }

        return implode('; ', $messages);
    }
}
