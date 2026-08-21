<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingTransport;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

final class ListPurchasingTransportAction extends PurchasingTransportAction
{
    protected function action(): Response
    {
        $userId = $this->userId();
        $companyId = $this->companyId();
        $scope = $this->scope('view');
        $query = array_merge([
            'page' => 1,
            'limit' => 20,
            'status' => 'all',
        ], $this->request->getQueryParams());

        $validator = new Validator($this->request);
        $validator->validate('page', $query['page'], 'required|integer|min:1');
        $validator->validate('limit', $query['limit'], 'required|integer|min:1|max:100');
        $validator->validate('purchase_order_id', $query['purchase_order_id'] ?? null, 'integer|min:1');
        $validator->validate('destination_factory_id', $query['destination_factory_id'] ?? null, 'integer|min:1');
        $validator->validate('vehicle_id', $query['vehicle_id'] ?? null, 'integer|min:1');
        $validator->validate('status', $query['status'], 'string|in:planned,loading,in_transit,arrived,cancelled,closed,all');
        $validator->validate('date_from', $query['date_from'] ?? null, 'date');
        $validator->validate('date_to', $query['date_to'] ?? null, 'date');
        $validator->validate('search', $query['search'] ?? null, 'string|max:255');
        if ($validator->hasErrors()) {
            throw new HttpBadRequestException($this->request, $this->errorMessages((array)$validator->getErrors()));
        }
        if (
            !empty($query['date_from'])
            && !empty($query['date_to'])
            && strtotime((string)$query['date_from']) > strtotime((string)$query['date_to'])
        ) {
            throw new HttpBadRequestException($this->request, 'date_from không được lớn hơn date_to');
        }

        $clean = $validator->sanitize($query, [
            'page' => 'integer',
            'limit' => 'integer',
            'purchase_order_id' => 'integer',
            'destination_factory_id' => 'integer',
            'vehicle_id' => 'integer',
            'status' => 'string',
            'date_from' => 'string',
            'date_to' => 'string',
            'search' => 'string',
        ]);
        $data = $this->purchasingTransportRepository->findAll($clean, $companyId, $userId, $scope);

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => Utils::generateRandomString(25),
            'data' => $data,
        ]);
    }
}
