<?php

declare(strict_types=1);

namespace App\Application\Actions\FinishedGoodsReceipt;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;


class ListFinishedGoodsReceiptAction extends FinishedGoodsReceiptAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        // Check Authentication
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);

        // Check permission to view finished goods receipts
        $scope = Utils::resolveScope($permissions, 'finished_goods_receipt', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $formData = $this->request->getQueryParams();

        $validator = new Validator($this->request);

        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('product_type_category', $formData['product_type_category'] ?? null, 'in:scrap_rubber,concentrated_latex,all');
        $validator->validate('production_order_id', $formData['production_order_id'] ?? null, 'integer|min:1');
        $validator->validate('product_tank_id', $formData['product_tank_id'] ?? null, 'integer|min:1');
        $validator->validate('status', $formData['status'] ?? null, 'string|in:draft,verified,approved,completed,all');
        $validator->validate('search', $formData['search'] ?? null, 'string');
        $validator->validate('created_date_from', $formData['created_date_from'] ?? null, 'date');
        $validator->validate('created_date_to', $formData['created_date_to'] ?? null, 'date');


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
            'production_order_id' => 'integer',
            'product_tank_id' => 'integer',
            'status' => 'string',
            'search' => 'string',
            'created_date_from' => 'date',
            'created_date_to' => 'date',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $page = $cleanData['page'];
        $limit = $cleanData['limit'];
        $production_order_id = $cleanData['production_order_id'] ?? 0;
        $product_tank_id = $cleanData['product_tank_id'] ?? 0;
        $product_type_category = $cleanData['product_type_category'] ?? 'all';
        $search = $cleanData['search'] ?? '';
        $status = $cleanData['status'] ?? 'all';
        $created_date_from = $cleanData['created_date_from'] ?? null;
        $created_date_to = $cleanData['created_date_to'] ?? null;
        
        $params = [
            "page" => $page,
            "page_limit" => $limit,
            "user_id" => $this->auth_data['user_id'],
            "product_type_category" => $product_type_category,
            "production_order_id" => $production_order_id,
            "product_tank_id" => $product_tank_id,
            "status" => $status,
            "search" => $search,
            "created_date_from" => $created_date_from,
            "created_date_to" => $created_date_to,
        ];

        $finished_goods_receipts = $this->finishedGoodsReceiptRepository->findAll(
            $params,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        $res_return = ["result" => "success"];
        $res_return['data'] = $finished_goods_receipts;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }

}
