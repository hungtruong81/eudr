<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingFactoryReceipt;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use RuntimeException;
use Slim\Exception\HttpBadRequestException;

final class CancelPurchasingFactoryReceiptAction extends PurchasingFactoryReceiptAction
{
    protected function action(): Response
    {
        $userId = $this->userId();
        $companyId = $this->companyId();
        $receipt = $this->receipt($this->scope('update'));
        $input = $this->getFormData();

        $validator = new Validator($this->request);
        $validator->validate('notes', $input['notes'] ?? null, 'string|max:255');
        if ($validator->hasErrors()) {
            throw new HttpBadRequestException($this->request, $this->errorMessages((array)$validator->getErrors()));
        }

        $clean = $validator->sanitize($input, ['notes' => 'string']);
        try {
            $cancelled = $this->purchasingFactoryReceiptRepository->cancel(
                (string)$receipt->jsonSerialize()['factory_receipt_code'],
                $companyId,
                $clean['notes'] ?? null,
                $userId
            );
        } catch (RuntimeException $exception) {
            throw new HttpBadRequestException($this->request, $exception->getMessage(), $exception);
        }

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => Utils::generateRandomString(25),
            'purchasing_factory_receipt' => $cancelled,
        ]);
    }
}
