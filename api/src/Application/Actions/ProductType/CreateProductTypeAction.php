<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductType;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;


class CreateProductTypeAction extends ProductTypeAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        // Check user authentication
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);

        // Check permission to create product types
        $productTypeScope = Utils::resolveScope($permissions, 'product_type', 'create');
        if (empty($productTypeScope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $formData = $this->getFormData();

        $validator = new Validator($this->request);

        $validator->validate('product_type_name', $formData['product_type_name'] ?? null, 'required|string');
        $validator->validate('product_type_code', $formData['product_type_code'] ?? null, 'required|string');
        $validator->validate('product_type_category', $formData['product_type_category'] ?? null, 'required|in:scrap_rubber,concentrated_latex');
        $validator->validate('product_weight', $formData['product_weight'] ?? null, 'required|numeric|min:1');
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
            'product_type_name' => 'string',
            'product_type_code' => 'string',
            'product_type_category' => 'string',
            'product_weight' => 'numeric',
            'description' => 'string'
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $product_type_name = $cleanData['product_type_name'];
        $product_type_code = $cleanData['product_type_code'];
        $product_type_category = $cleanData['product_type_category'];
        $product_weight = $cleanData['product_weight'];
        $description = $cleanData['description'];

        // Check product type code uniqueness
        $existingProductType = $this->productTypeRepository->findProductTypeOfCode($product_type_code);
        if ($existingProductType) {
            throw new HttpBadRequestException($this->request, "Mã loại sản phẩm đã tồn tại");
        }

        // Data Product Type
        $data_update = [
            'company_id' => $this->auth_data['company_id'] ?? 0,
            'product_type_name' => $product_type_name,
            'product_type_code' => $product_type_code,
            'product_type_category' => $product_type_category,
            'product_weight' => $product_weight,
            'description' => $description,
            'created_at' => date('Y-m-d H:i:s', time()),
            'created_by' => (int)$this->auth_data['user_id'],
        ];

        $productType = $this->productTypeRepository->createProductType($data_update);

        $action = 'create';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'product_type',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$productType->getId(),
        );

        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['product_type'] = $productType->jsonSerialize();

        return $this->respondWithData($res_return);
        
    }
}
