<?php

declare(strict_types=1);

namespace App\Application\Actions\Sales\Orders;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

class ListOrderAction extends OrderAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'sales_order', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $formData = $this->request->getQueryParams();
        $validator = new Validator($this->request);

        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('search', $formData['search'] ?? null, 'string');
        $validator->validate('status', $formData['status'] ?? null, 'string|in:draft,approved,allocated,shipping,closed,cancelled,all');
        $validator->validate('customer_id', $formData['customer_id'] ?? null, 'integer');
        $validator->validate('contract_id', $formData['contract_id'] ?? null, 'integer');
        $validator->validate('order_date_from', $formData['order_date_from'] ?? null, 'date');
        $validator->validate('order_date_to', $formData['order_date_to'] ?? null, 'date');
        $validator->validate('order_source_type', $formData['order_source_type'] ?? null, 'string|in:warehouse,transaction_ticket,product_lot');
        $validator->validate('buyer_type', $formData['buyer_type'] ?? null, 'string|in:customer,trader');
        $validator->validate('transaction_ticket_id', $formData['transaction_ticket_id'] ?? null, 'integer');
        $validator->validate('company_id', $formData['company_id'] ?? null, 'integer');

        if ($validator->hasErrors()) {
            $errors = [];
            foreach ((array)$validator->getErrors() as $fieldErrors) {
                $errors[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errors));
        }

        $clean = $validator->sanitize($formData, [
            'page' => 'integer',
            'limit' => 'integer',
            'search' => 'string',
            'status' => 'string',
            'customer_id' => 'integer',
            'contract_id' => 'integer',
            'order_date_from' => 'date',
            'order_date_to' => 'date',
            'order_source_type' => 'string',
            'buyer_type' => 'string',
            'transaction_ticket_id' => 'integer',
            'company_id' => 'integer',
        ]);

        $params = [
            'page' => $clean['page'],
            'page_limit' => $clean['limit'],
            'search' => $clean['search'] ?? '',
            'status' => $clean['status'] ?? 'all',
            'customer_id' => $clean['customer_id'] ?? 0,
            'contract_id' => $clean['contract_id'] ?? 0,
            'order_date_from' => $clean['order_date_from'] ?? null,
            'order_date_to' => $clean['order_date_to'] ?? null,
            'order_source_type' => $clean['order_source_type'] ?? null,
            'buyer_type' => $clean['buyer_type'] ?? null,
            'transaction_ticket_id' => $clean['transaction_ticket_id'] ?? null,
            'company_id_param' => $clean['company_id'] ?? null,
        ];

        $data = $this->salesOrderRepository->findAll(
            $params,
            (int)$this->auth_data['user_id'],
            $scope,
            $this->auth_data['company_id'] ?? null,
            $clean['company_id'] ?? null
        );

        $res = ['result' => 'success', 'data' => $data, 'trace_id' => $trace_id];
        return $this->respondWithData($res);
    }
}
