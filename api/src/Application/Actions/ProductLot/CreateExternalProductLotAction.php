<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductLot;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class CreateExternalProductLotAction extends ProductLotAction
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
        $validator->validate('grade', $formData['grade'] ?? null, 'string|max:50');
        $validator->validate('total_blocks', $formData['total_blocks'] ?? null, 'integer|min:0');
        $validator->validate('total_weight', $formData['total_weight'] ?? null, 'required|numeric|min:0.01');
        $validator->validate('purchase_date', $formData['purchase_date'] ?? null, 'required|date');
        $validator->validate('lands', $formData['lands'] ?? null, 'required|array');

        if ($validator->hasErrors()) {
            $errorMessages = [];
            $errors = $validator->getErrors();
            if (is_array($errors)) {
                foreach ($errors as $field => $fieldErrors) {
                    if (is_array($fieldErrors)) {
                        $errorMessages[] = implode(', ', $fieldErrors);
                    } else {
                        $errorMessages[] = (string)$fieldErrors;
                    }
                }
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errorMessages));
        }

        $lands = $formData['lands'] ?? [];
        if (empty($lands) || !is_array($lands)) {
            throw new HttpBadRequestException($this->request, "Phải có ít nhất một vườn");
        }

        // Validate each land plot_id exists
        $landsData = [];
        foreach ($lands as $land) {
            $plotId = (int)($land['plot_id'] ?? 0);
            if ($plotId <= 0) {
                throw new HttpBadRequestException($this->request, "plot_id không hợp lệ");
            }
            $existingLand = $this->landRepository->findLandOfId($plotId);
            if (empty($existingLand)) {
                throw new HttpBadRequestException($this->request, "Vườn ID $plotId không tồn tại");
            }
            $landsData[] = [
                'plot_id' => $plotId,
                'harvest_weight' => (float)($land['harvest_weight'] ?? 0),
                'notes' => $land['notes'] ?? '',
            ];
        }

        $transport_data = null;
        if (!empty($formData['transport']) && is_array($formData['transport'])) {
            $transport_data = $formData['transport'];
        }

        $now = date('Y-m-d H:i:s');
        $product_lot_code = $this->productLotRepository->generateExternalCode();

        $data = [
            'product_lot_code' => $product_lot_code,
            'lot_type' => 'external',
            'grade' => trim($formData['grade'] ?? ''),
            'factory_id' => (int)($formData['factory_id'] ?? 0),
            'owner_company_id' => (int)$this->auth_data['company_id'],
            'owner_id' => (int)$this->auth_data['user_id'],
            'supplier_company_name' => trim($formData['supplier_company_name']),
            'supplier_factory_name' => trim($formData['supplier_factory_name'] ?? ''),
            'supplier_phone' => trim($formData['supplier_phone'] ?? ''),
            'supplier_address' => trim($formData['supplier_address'] ?? ''),
            'original_product_lot_code' => trim($formData['original_product_lot_code'] ?? ''),
            'production_date_from' => $formData['production_date_from'] ?? null,
            'production_date_to' => $formData['production_date_to'] ?? null,
            'total_blocks' => (int)($formData['total_blocks'] ?? 0),
            'total_weight' => (float)$formData['total_weight'],
            'purchase_date' => $formData['purchase_date'],
            'purchase_amount' => (float)($formData['purchase_amount'] ?? 0),
            'notes' => trim($formData['notes'] ?? ''),
            'status' => 'draft',
            'created_by' => (int)$this->auth_data['user_id'],
            'lands' => $landsData,
            'transport' => $transport_data,
        ];

        $productLot = $this->productLotRepository->createExternalProductLot($data);
        if (empty($productLot)) {
            throw new HttpBadRequestException($this->request, "Tạo lô hàng thất bại");
        }

        $log = [
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'product_lot',
            "action" => 'create_external',
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$productLot->getId(),
        ];
        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['data'] = $productLot->jsonSerialize();

        return $this->respondWithData($res_return);
    }
}
