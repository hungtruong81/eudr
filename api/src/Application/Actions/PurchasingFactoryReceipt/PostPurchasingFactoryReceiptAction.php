<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingFactoryReceipt;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use RuntimeException;
use Slim\Exception\HttpBadRequestException;

final class PostPurchasingFactoryReceiptAction extends PurchasingFactoryReceiptAction
{
    protected function action(): Response
    {
        $userId = $this->userId();
        $companyId = $this->companyId();
        $receipt = $this->receipt($this->scope('update'));

        try {
            $posted = $this->purchasingFactoryReceiptRepository->post(
                (string)$receipt->jsonSerialize()['factory_receipt_code'],
                $companyId,
                $userId
            );
        } catch (RuntimeException $exception) {
            throw new HttpBadRequestException($this->request, $exception->getMessage(), $exception);
        }

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => Utils::generateRandomString(25),
            'purchasing_factory_receipt' => $posted,
        ]);
    }
}