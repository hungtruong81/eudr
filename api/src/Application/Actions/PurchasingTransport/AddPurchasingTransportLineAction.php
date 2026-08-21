<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingTransport;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use RuntimeException;
use Slim\Exception\HttpBadRequestException;

final class AddPurchasingTransportLineAction extends PurchasingTransportAction
{
    protected function action(): Response
    {
        $userId = $this->userId();
        $companyId = $this->companyId();
        $transport = $this->transport($this->scope('update'));
        $input = $this->getFormData();
        $validator = new Validator($this->request);
        $validator->validate('sub_tank_id', $input['sub_tank_id'] ?? null, 'required|integer|min:1');
        $validator->validate('loaded_weight_kg', $input['loaded_weight_kg'] ?? null, 'required|numeric|min:0.01');
        $validator->validate('seller_sub_tank_ref_id', $input['seller_sub_tank_ref_id'] ?? null, 'integer|min:1');
        $validator->validate('buyer_sub_tank_ref_id', $input['buyer_sub_tank_ref_id'] ?? null, 'required|integer|min:1');
        $validator->validate('vehicle_tank_id', $input['vehicle_tank_id'] ?? null, 'integer|min:1');
        $validator->validate('notes', $input['notes'] ?? null, 'string|max:255');
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
        try {
            $updated = $this->purchasingTransportRepository->addLine(
                (string)$transport->jsonSerialize()['purchase_transport_code'],
                $companyId,
                $clean,
                $userId
            );
        } catch (RuntimeException $exception) {
            throw new HttpBadRequestException($this->request, $exception->getMessage(), $exception);
        }

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => Utils::generateRandomString(25),
            'purchasing_transport' => $updated,
        ], 201);
    }
}
