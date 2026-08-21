<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductLot;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class UpdateExternalProductLotAction extends ProductLotAction
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
        $scope = Utils::resolveScope($permissions, 'finished_goods_receipt', 'update');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $product_lot_code = addslashes(trim((string)$this->resolveArg('code')));

        $productLot = $this->productLotRepository->findProductLotOfCode($product_lot_code);
        if (empty($productLot)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy lô hàng");
        }

        if ($productLot->getLotType() !== 'external') {
            throw new HttpBadRequestException($this->request, "Chỉ có thể cập nhật lô hàng bên ngoài");
        }

        if ($productLot->getStatus() !== 'draft') {
            throw new HttpBadRequestException($this->request, "Chỉ có thể cập nhật lô hàng ở trạng thái nháp");
        }

        $formData = $this->getFormData();

        $validator = new Validator($this->request);
        $validator->validate('supplier_company_name', $formData['supplier_company_name'] ?? null, 'string|max:255');
        $validator->validate('total_weight', $formData['total_weight'] ?? null, 'numeric|min:0');

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

        // Validate lands if provided
        $landsData = null;
        if (isset($formData['lands']) && is_array($formData['lands'])) {
            $landsData = [];
            foreach ($formData['lands'] as $land) {
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
        }

        $transport_data = null;
        if (isset($formData['transport'])) {
            $transport_data = is_array($formData['transport']) ? $formData['transport'] : null;
        }

        $now = date('Y-m-d H:i:s');
        $data = [
            'grade' => trim($formData['grade'] ?? $productLot->getGrade() ?? ''),
            'factory_id' => (int)($formData['factory_id'] ?? $productLot->getFactoryId()),
            'supplier_company_name' => trim($formData['supplier_company_name'] ?? ''),
            'supplier_factory_name' => trim($formData['supplier_factory_name'] ?? ''),
            'supplier_phone' => trim($formData['supplier_phone'] ?? ''),
            'supplier_address' => trim($formData['supplier_address'] ?? ''),
            'original_product_lot_code' => trim($formData['original_product_lot_code'] ?? ''),
            'production_date_from' => $formData['production_date_from'] ?? null,
            'production_date_to' => $formData['production_date_to'] ?? null,
            'total_blocks' => (int)($formData['total_blocks'] ?? 0),
            'total_weight' => (float)($formData['total_weight'] ?? 0),
            'purchase_date' => $formData['purchase_date'] ?? null,
            'purchase_amount' => (float)($formData['purchase_amount'] ?? 0),
            'notes' => trim($formData['notes'] ?? ''),
            'updated_by' => (int)$this->auth_data['user_id'],
            'lands' => $landsData,
            'transport' => $transport_data,
        ];

        $productLot = $this->productLotRepository->updateExternalProductLot($productLot->getId(), $data);
        if (empty($productLot)) {
            throw new HttpBadRequestException($this->request, "Cập nhật lô hàng thất bại");
        }

        $log = [
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'product_lot',
            "action" => 'update_external',
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
