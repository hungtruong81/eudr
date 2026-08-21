<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingFactoryReceipt;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use RuntimeException;
use Slim\Exception\HttpBadRequestException;

final class CreatePurchasingFactoryReceiptAction extends PurchasingFactoryReceiptAction
{
    protected function action(): Response
    {
        $userId = $this->userId();
        $companyId = $this->companyId();
        $transport = $this->transport($this->scope('update'));
        $input = $this->getFormData();

        $validator = new Validator($this->request);
        $validator->validate('receipt_date', $input['receipt_date'] ?? null, 'date');
        $validator->validate('notes', $input['notes'] ?? null, 'string|max:255');
        $validator->validate('items', $input['items'] ?? null, 'required|array');
        if ($validator->hasErrors()) {
            throw new HttpBadRequestException($this->request, $this->errorMessages((array)$validator->getErrors()));
        }

        $clean = $validator->sanitize($input, [
            'receipt_date' => 'string',
            'notes' => 'string',
        ]);
        $clean['items'] = $input['items'];
        try {
            $receipt = $this->purchasingFactoryReceiptRepository->createForTransport(
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
            'purchasing_factory_receipt' => $receipt,
        ], 201);
    }
}
