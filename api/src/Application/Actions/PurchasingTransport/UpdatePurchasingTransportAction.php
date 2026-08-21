<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingTransport;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use RuntimeException;
use Slim\Exception\HttpBadRequestException;

final class UpdatePurchasingTransportAction extends PurchasingTransportAction
{
    protected function action(): Response
    {
        $userId = $this->userId();
        $companyId = $this->companyId();
        $transport = $this->transport($this->scope('update'));
        $input = $this->getFormData();
        $fields = [
            'destination_factory_id' => 'integer|min:1',
            'vehicle_id' => 'integer|min:1',
            'source_location' => 'string|max:255',
            'driver_user_id' => 'integer|min:1',
            'driver_name' => 'string|max:150',
            'driver_phone' => 'string|max:20',
            'seal_no' => 'string|max:50',
            'notes' => 'string|max:255',
        ];
        $validator = new Validator($this->request);
        foreach ($fields as $field => $rules) {
            $validator->validate($field, $input[$field] ?? null, $rules);
        }
        if ($validator->hasErrors()) {
            throw new HttpBadRequestException($this->request, $this->errorMessages((array)$validator->getErrors()));
        }

        $clean = $validator->sanitize($input, [
            'destination_factory_id' => 'integer',
            'vehicle_id' => 'integer',
            'source_location' => 'string',
            'driver_user_id' => 'integer',
            'driver_name' => 'string',
            'driver_phone' => 'string',
            'seal_no' => 'string',
            'notes' => 'string',
        ]);
        $update = [];
        foreach (array_keys($fields) as $field) {
            if (array_key_exists($field, $input)) {
                $update[$field] = $clean[$field] ?? null;
            }
        }
        if ($update === []) {
            throw new HttpBadRequestException($this->request, 'Không có thông tin cập nhật');
        }

        try {
            $updated = $this->purchasingTransportRepository->update(
                (string)$transport->jsonSerialize()['purchase_transport_code'],
                $companyId,
                $update,
                $userId
            );
        } catch (RuntimeException $exception) {
            throw new HttpBadRequestException($this->request, $exception->getMessage(), $exception);
        }

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => Utils::generateRandomString(25),
            'purchasing_transport' => $updated,
        ]);
    }
}
