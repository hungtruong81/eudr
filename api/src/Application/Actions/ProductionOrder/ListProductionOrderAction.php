<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionOrder;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class ListProductionOrderAction extends ProductionOrderAction
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

        // Load permissions once and resolve scope
        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);

        // Check permission to view production orders
        $productionOrderScope = Utils::resolveScope($permissions, 'production_order', 'view');
        if (empty($productionOrderScope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $formData = $this->request->getQueryParams();

        $validator = new Validator($this->request);

        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('product_type_category', $formData['product_type_category'] ?? null, 'in:scrap_rubber,concentrated_latex,all');
        $validator->validate('contract_code', $formData['contract_code'] ?? null, 'string');
        $validator->validate('search', $formData['search'] ?? null, 'string');
        $validator->validate('status', $formData['status'] ?? null, 'string|in:approved,in_production,completed,all');
        $validator->validate('production_date_from', $formData['production_date_from'] ?? null, 'date');
        $validator->validate('production_date_to', $formData['production_date_to'] ?? null, 'date');
        $validator->validate('created_date_from', $formData['created_date_from'] ?? null, 'date');
        $validator->validate('created_date_to', $formData['created_date_to'] ?? null, 'date');
        $validator->validate('factory_id', $formData['factory_id'] ?? null, 'integer');


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
            'page' => 'integer',
            'limit' => 'integer',
            'contract_code' => 'string',
            'product_type_category' => 'string',
            'factory_id' => 'integer',
            'search' => 'string',
            'status' => 'string',
            'production_date_from' => 'date',
            'production_date_to' => 'date',
            'created_date_from' => 'date',
            'created_date_to' => 'date',

        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $page = $cleanData['page'];
        $limit = $cleanData['limit'];
        $contract_code = $cleanData['contract_code'] ?? '';
        $product_type_category = $cleanData['product_type_category'] ?? 'all';
        $search = $cleanData['search'] ?? '';
        $status = $cleanData['status'] ?? 'all';
        $production_date_from = $cleanData['production_date_from'] ?? null;
        $production_date_to = $cleanData['production_date_to'] ?? null;
        $created_date_from = $cleanData['created_date_from'] ?? null;
        $created_date_to = $cleanData['created_date_to'] ?? null;
        $factory_id = $cleanData['factory_id'] ?? 0;
        
        $params = [
            "page" => $page,
            "page_limit" => $limit,
            "user_id" => $this->auth_data['user_id'],
            "product_type_category" => $product_type_category,
            "contract_code" => $contract_code,
            "search" => $search,
            "status" => $status,
            "production_date_from" => $production_date_from,
            "production_date_to" => $production_date_to,
            "created_date_from" => $created_date_from,
            "created_date_to" => $created_date_to,
            "factory_id" => $factory_id,
        ];

        $production_orders = $this->productionOrderRepository->findAll(
            $params,
            (int)$this->auth_data['user_id'],
            (string)$productionOrderScope,
            $this->auth_data['company_id'] ?? null
        );

        $res_return = ["result" => "success"];
        $res_return['data'] = $production_orders;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }

}
