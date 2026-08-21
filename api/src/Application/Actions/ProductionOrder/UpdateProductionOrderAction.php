<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionOrder;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Validator;
use App\Application\Utility\Utils;


class UpdateProductionOrderAction extends ProductionOrderAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        // Check authentication
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        $user_role = $this->userRepository->getUserRole($this->auth_data['user_id']);

        // Load permissions once and resolve scopes
        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);

        // Check permission to update production orders
		$updateScope = Utils::resolveScope($permissions, 'production_order', 'update');
		if (empty($updateScope)) {
			throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
		}

        // Check permission to view product types (for validation)
        $productTypeScope = Utils::resolveScope($permissions, 'product_type', 'view');
        if (empty($productTypeScope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập loại sản phẩm");
        }

        $production_order_code = addslashes(trim((string)$this->resolveArg('code')));

        $production_order = $this->productionOrderRepository->findProductionOrderOfCodeWithPermission(
            $production_order_code,
            (int)$this->auth_data['user_id'],
            (string)$updateScope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($production_order)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy phiếu sản xuất");
        }

        $formData = $this->getFormData();

        $validator = new Validator($this->request);

        $validator->validate('production_order_name', $formData['production_order_name'] ?? null, 'required|string');
        $validator->validate('contract_id', $formData['contract_id'] ?? null, 'integer');
        $validator->validate('contract_code', $formData['contract_code'] ?? null, 'string');
        $validator->validate('product_type_category', $formData['product_type_category'] ?? null, 'required|in:scrap_rubber,concentrated_latex');
        $validator->validate('product_type_id', $formData['product_type_id'] ?? null, 'required|integer');
        $validator->validate('required_quantity', $formData['required_quantity'] ?? null, 'required|integer|min:1');
        $validator->validate('description', $formData['description'] ?? null, 'string');


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

        // Sanitize and extract data
        $sanitizeRules = [
            'production_order_name' => 'string',
            'contract_id' => 'integer',
            'contract_code' => 'string',
            'product_type_category' => 'string',
            'product_type_id' => 'integer',
            'required_quantity' => 'integer',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $production_order_name = $cleanData['production_order_name'];
        $contract_id = $cleanData['contract_id'] ?? 0;
        $contract_code = $cleanData['contract_code'] ?? '';
        $product_type_category = $cleanData['product_type_category'];
        $product_type_id = $cleanData['product_type_id'];
        $required_quantity = $cleanData['required_quantity'];

        // Validate product type id
        $existingProductType = $this->productTypeRepository->findProductTypeOfIdWithPermission(
            $product_type_id,
            (int)$this->auth_data['user_id'],
            (string)$productTypeScope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($existingProductType)) {
            throw new HttpNotFoundException($this->request, "Loại sản phẩm không tồn tại");
        }

        // Data Production Order
        $data_update = [
            'product_type_category' => $product_type_category,
            'production_order_name' => $production_order_name,
            'contract_id' => $contract_id,
            'contract_code' => $contract_code,
            'product_type_id' => $product_type_id,
            'required_quantity' => $required_quantity,
            'updated_at' => date("Y-m-d H:i:s", time()),
            'updated_by' => $this->auth_data['user_id'],
        ];

        $production_order = $this->productionOrderRepository->updateProductionOrderWithPermission(
            $production_order->getId(),
            $data_update,
            (int)$this->auth_data['user_id'],
            (string)$updateScope,
            $this->auth_data['company_id'] ?? null
        );

        $action = 'update';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'production_order',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$production_order->getId(),
        );

        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['production_order'] = $production_order->jsonSerialize();

        return $this->respondWithData($res_return);
        
    }
}
