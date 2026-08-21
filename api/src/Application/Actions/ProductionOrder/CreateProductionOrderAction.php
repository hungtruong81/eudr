<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionOrder;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;


class CreateProductionOrderAction extends ProductionOrderAction
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

        // Check permission to create production orders
        $productionOrderScope = Utils::resolveScope($permissions, 'production_order', 'create');
        if (empty($productionOrderScope)) {
            throw new HttpForbiddenException($this->request, "Không có quyền tạo lệnh sản xuất");
        }

        // Check permission to view product types
        $productTypeScope = Utils::resolveScope($permissions, 'product_type', 'view');
        if (empty($productTypeScope)) {
            throw new HttpForbiddenException($this->request, "Không có quyền truy cập loại sản phẩm");
        }

        $factoryViewScope = Utils::resolveScope($permissions, 'factory', 'view');
        if (empty($factoryViewScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập nhà máy');
        }

        $formData = $this->getFormData();

        $validator = new Validator($this->request);

        $validator->validate('production_order_name', $formData['production_order_name'] ?? null, 'required|string');
        $validator->validate('production_order_code', $formData['production_order_code'] ?? null, 'required|string|max:30');
         $validator->validate('factory_id', $formData['factory_id'] ?? null, 'required|integer|min:1');
        $validator->validate('contract_id', $formData['contract_id'] ?? null, 'integer');
        $validator->validate('contract_code', $formData['contract_code'] ?? null, 'string');
        //$validator->validate('product_type_category', $formData['product_type_category'] ?? null, 'required|in:scrap_rubber,concentrated_latex');
        //$validator->validate('product_type_id', $formData['product_type_id'] ?? null, 'required|integer');
        //$validator->validate('required_quantity', $formData['required_quantity'] ?? null, 'required|integer|min:1');
        $validator->validate('production_date', $formData['production_date'] ?? null, 'required|date');

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
            'production_order_code' => 'string',
            'factory_id' => 'integer',
            'contract_id' => 'integer',
            'contract_code' => 'string',
            // 'product_type_category' => 'string',
            // 'product_type_id' => 'integer',
            // 'required_quantity' => 'integer',
            'production_date' => 'date',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $production_order_name = $cleanData['production_order_name'];
        $production_order_code = $cleanData['production_order_code'];
        $factory_id = $cleanData['factory_id'];
        $contract_id = $cleanData['contract_id'] ?? 0;
        $contract_code = $cleanData['contract_code'] ?? '';
        $product_type_category = $cleanData['product_type_category'] ?? NULL;
        $product_type_id = $cleanData['product_type_id'] ?? 0;
        $required_quantity = $cleanData['required_quantity'] ?? 0;
        $production_date = $cleanData['production_date'];

        // Validate unique production order code
        $existingProductionOrder = $this->productionOrderRepository->findProductionOrderOfCode($production_order_code);
        if (!empty($existingProductionOrder)) {
            $production_order_code = $this->productionOrderRepository->generateCode();
        }

        // Validate unique product type code
        if (!empty($product_type_id)) {
            $existingProductType = $this->productTypeRepository->findProductTypeOfIdWithPermission(
                $product_type_id,
                (int)$this->auth_data['user_id'],
                (string)$productTypeScope,
                $this->auth_data['company_id'] ?? null
            );
            if (empty($existingProductType)) {
                throw new HttpNotFoundException($this->request, "Loại sản phẩm không tồn tại");
            }
        }

        // Data Production Order
        $data_update = [
            'company_id' => $this->auth_data['company_id'] ?? 0,
            'production_order_code' => $production_order_code,
            'product_type_category' => $product_type_category,
            'production_order_name' => $production_order_name,
            'factory_id' => $factory_id,
            'contract_id' => $contract_id,
            'contract_code' => $contract_code,
            'product_type_id' => $product_type_id,
            'required_quantity' => $required_quantity,
            'production_date' => $production_date,
            'status' => 'approved',
            'created_at' => date('Y-m-d H:i:s', time()),
            'created_by' => (int)$this->auth_data['user_id'],
        ];

        $productionOrder = $this->productionOrderRepository->createProductionOrder($data_update);

        $action = 'create';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'production_order',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$productionOrder->getId(),
        );

        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['production_order'] = $productionOrder->jsonSerialize();

        return $this->respondWithData($res_return);
        
    }
}
