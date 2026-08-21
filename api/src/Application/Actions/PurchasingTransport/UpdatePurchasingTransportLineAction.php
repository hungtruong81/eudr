<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingTransport;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use RuntimeException;
use Slim\Exception\HttpBadRequestException;

final class UpdatePurchasingTransportLineAction extends PurchasingTransportAction
{
    protected function action(): Response
    {
        $userId = $this->userId();
        $companyId = $this->companyId();
        $transport = $this->transport($this->scope('update'));
        $lineId = (int)$this->resolveArg('lineId');
        if ($lineId <= 0) {
            throw new HttpBadRequestException($this->request, 'lineId không hợp lệ');
        }

        $input = $this->getFormData();
        $fields = [
            'sub_tank_id' => 'integer|min:1',
            'loaded_weight_kg' => 'numeric|min:0.01',
            'seller_sub_tank_ref_id' => 'integer|min:1',
            'buyer_sub_tank_ref_id' => 'integer|min:1',
            'vehicle_tank_id' => 'integer|min:1',
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
            'sub_tank_id' => 'integer',
            'loaded_weight_kg' => 'float',
            'seller_sub_tank_ref_id' => 'integer',
            'buyer_sub_tank_ref_id' => 'integer',
            'vehicle_tank_id' => 'integer',
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
            $updated = $this->purchasingTransportRepository->updateLine(
                (string)$transport->jsonSerialize()['purchase_transport_code'],
                $lineId,
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
