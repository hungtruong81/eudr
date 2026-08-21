<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingTransport;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use RuntimeException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;

final class CreatePurchasingTransportAction extends PurchasingTransportAction
{
    protected function action(): Response
    {
        $userId = $this->userId();
        $companyId = $this->companyId();
        $scope = $this->scope('create');
        $input = $this->getFormData();

        $validator = new Validator($this->request);
        $validator->validate('purchase_order_id', $input['purchase_order_id'] ?? null, 'required|integer|min:1');
        $validator->validate('destination_factory_id', $input['destination_factory_id'] ?? null, 'required|integer|min:1');
        $validator->validate('vehicle_id', $input['vehicle_id'] ?? null, 'required|integer|min:1');
        $validator->validate('source_location', $input['source_location'] ?? null, 'string|max:255');
        $validator->validate('driver_user_id', $input['driver_user_id'] ?? null, 'integer|min:1');
        $validator->validate('driver_name', $input['driver_name'] ?? null, 'string|max:150');
        $validator->validate('driver_phone', $input['driver_phone'] ?? null, 'string|max:20');
        $validator->validate('seal_no', $input['seal_no'] ?? null, 'string|max:50');
        $validator->validate('notes', $input['notes'] ?? null, 'string|max:255');
        if ($validator->hasErrors()) {
            throw new HttpBadRequestException($this->request, $this->errorMessages((array)$validator->getErrors()));
        }

        $clean = $validator->sanitize($input, [
            'purchase_order_id' => 'integer',
            'destination_factory_id' => 'integer',
            'vehicle_id' => 'integer',
            'source_location' => 'string',
            'driver_user_id' => 'integer',
            'driver_name' => 'string',
            'driver_phone' => 'string',
            'seal_no' => 'string',
            'notes' => 'string',
        ]);
        $order = $this->purchasingOrderRepository->findOrderOfIdWithPermission(
            (int)$clean['purchase_order_id'],
            $userId,
            $scope,
            $companyId
        );
        if ($order === null) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy phiếu thu mua');
        }
        $orderData = $order->jsonSerialize();
        if ((int)($orderData['company_id'] ?? 0) !== $companyId) {
            throw new HttpForbiddenException($this->request, 'Chỉ công ty bên mua được tạo chuyến xe');
        }

        try {
            $transport = $this->purchasingTransportRepository->create(array_merge($clean, [
                'company_id' => $companyId,
                'created_by' => $userId,
            ]));
        } catch (RuntimeException $exception) {
            throw new HttpBadRequestException($this->request, $exception->getMessage(), $exception);
        }

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => Utils::generateRandomString(25),
            'purchasing_transport' => $transport,
        ], 201);
    }
}
