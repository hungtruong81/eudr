<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingOrder\Order;

use App\Application\Actions\PurchasingOrder\PurchasingOrderAction;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

class ListPurchasingOrderAction extends PurchasingOrderAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'purchasing_order', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $query = $this->request->getQueryParams();
        $validator = new Validator($this->request);

        $validator->validate('page', $query['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $query['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('search', $query['search'] ?? null, 'string');
        $validator->validate('status', $query['status'] ?? null, 'string|in:draft,sent_to_seller,seller_confirmed,buyer_reconfirmed,transport_planned,in_transit,arrived_factory,quality_checked,received_closed,cancelled,all');
        $validator->validate('purchase_date_from', $query['purchase_date_from'] ?? null, 'date');
        $validator->validate('purchase_date_to', $query['purchase_date_to'] ?? null, 'date');
        $validator->validate('seller_source_type', $query['seller_source_type'] ?? null, 'string|in:system_user,vendor,all');

        if ($validator->hasErrors()) {
            $errors = [];
            foreach ((array)$validator->getErrors() as $fieldErrors) {
                $errors[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errors));
        }

        $clean = $validator->sanitize($query, [
            'page' => 'integer',
            'limit' => 'integer',
            'search' => 'string',
            'status' => 'string',
            'purchase_date_from' => 'date',
            'purchase_date_to' => 'date',
            'seller_source_type' => 'string',
        ]);

        $data = $this->purchasingOrderRepository->findAll(
            [
                'page' => (int)$clean['page'],
                'page_limit' => (int)$clean['limit'],
                'search' => $clean['search'] ?? '',
                'status' => $clean['status'] ?? 'all',
                'purchase_date_from' => $clean['purchase_date_from'] ?? null,
                'purchase_date_to' => $clean['purchase_date_to'] ?? null,
                'seller_source_type' => $clean['seller_source_type'] ?? 'all',
            ],
            (int)$this->auth_data['user_id'],
            (string)$scope,
            (int)($this->auth_data['company_id'] ?? 0)
        );

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'data' => $data,
        ]);
    }
}
