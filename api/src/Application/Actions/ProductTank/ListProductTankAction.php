<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductTank;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;


class ListProductTankAction extends ProductTankAction
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

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);

        // Check permission to view product tanks
        $scope = Utils::resolveScope($permissions, 'product_tank', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }
        
        $formData = $this->request->getQueryParams();

        $validator = new Validator($this->request);

        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('factory_id', $formData['factory_id'] ?? null, 'integer|min:1');
        $validator->validate('product_type', $formData['product_type'] ?? null, 'string');
        $validator->validate('status', $formData['status'] ?? null, 'in:active,inactive,all');
        $validator->validate('search', $formData['search'] ?? null, 'string');
        $validator->validate('product_tank_code', $formData['product_tank_code'] ?? null, 'string');
        $validator->validate('product_tank_id', $formData['product_tank_id'] ?? null, 'integer|min:1');


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
            'factory_id' => 'integer',
            'product_type' => 'string',
            'status' => 'string',
            'search' => 'string',
            'product_tank_code' => 'string',
            'product_tank_id' => 'integer',

        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $page = $cleanData['page'];
        $limit = $cleanData['limit'];
        $factory_id = $cleanData['factory_id'] ?? null;
        $product_type = $cleanData['product_type'] ?? 'all';
        $status = $cleanData['status'] ?? 'all';
        $search = $cleanData['search'] ?? '';
        $product_tank_code = $cleanData['product_tank_code'] ?? '';
        $product_tank_id = $cleanData['product_tank_id'] ?? 0;
        
        $params = [
            "page" => $page,
            "page_limit" => $limit,
            "user_id" => $this->auth_data['user_id'],
            "factory_id" => $factory_id,
            "product_type" => $product_type,
            "status" => $status,
            "search" => $search,
            "product_tank_code" => $product_tank_code,
            "product_tank_id" => $product_tank_id,
        ];

        $product_tanks = $this->productTankRepository->findAll(
            $params,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        $res_return = ["result" => "success"];
        $res_return['data'] = $product_tanks;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }

}
