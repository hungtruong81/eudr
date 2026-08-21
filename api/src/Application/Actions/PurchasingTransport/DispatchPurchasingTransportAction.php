<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingTransport;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use RuntimeException;
use Slim\Exception\HttpBadRequestException;

final class DispatchPurchasingTransportAction extends PurchasingTransportAction
{
    protected function action(): Response
    {
        $userId = $this->userId();
        $companyId = $this->companyId();
        $transport = $this->transport($this->scope('update'));
        $input = $this->getFormData();
        $validator = new Validator($this->request);
        $validator->validate('departed_at', $input['departed_at'] ?? null, 'date');
        $validator->validate('seal_no', $input['seal_no'] ?? null, 'string|max:50');
        $validator->validate('notes', $input['notes'] ?? null, 'string|max:255');
        if ($validator->hasErrors()) {
            throw new HttpBadRequestException($this->request, $this->errorMessages((array)$validator->getErrors()));
        }

        $clean = $validator->sanitize($input, [
            'departed_at' => 'string',
            'seal_no' => 'string',
            'notes' => 'string',
        ]);
        try {
            $updated = $this->purchasingTransportRepository->dispatch(
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
        ]);
    }
}
