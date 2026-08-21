<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductType;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;


class ListProductTypeAction extends ProductTypeAction
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

        // Check permission to list product types
        $productTypeScope = Utils::resolveScope($permissions, 'product_type', 'view');
        if (empty($productTypeScope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $formData = $this->request->getQueryParams();

        $validator = new Validator($this->request);

        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('product_type_category', $formData['product_type_category'] ?? null, 'in:scrap_rubber,concentrated_latex,all');
        $validator->validate('search', $formData['search'] ?? null, 'string');
        $validator->validate('product_type_code', $formData['product_type_code'] ?? null, 'string');
        $validator->validate('product_type_id', $formData['product_type_id'] ?? null, 'integer|min:1');

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
            'product_type_category' => 'string',
            'search' => 'string',
            'product_type_code' => 'string',
            'product_type_id' => 'integer',

        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $page = $cleanData['page'];
        $limit = $cleanData['limit'];
        $product_type_category = $cleanData['product_type_category'] ?? 'all';
        $search = $cleanData['search'] ?? '';
        
        $params = [
            "page" => $page,
            "page_limit" => $limit,
            "user_id" => $this->auth_data['user_id'],
            "product_type_category" => $product_type_category,
            "search" => $search,
            "product_type_code" => $cleanData['product_type_code'] ?? '',
            "product_type_id" => $cleanData['product_type_id'] ?? 0,
        ];

        $product_types = $this->productTypeRepository->findAll(
            $params,
            (int)$this->auth_data['user_id'],
            (string)$productTypeScope,
            $this->auth_data['company_id'] ?? null
        );

        $res_return = ["result" => "success"];
        $res_return['data'] = $product_types;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }

}
