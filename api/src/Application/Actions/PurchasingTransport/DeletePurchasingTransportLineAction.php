<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingTransport;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use RuntimeException;
use Slim\Exception\HttpBadRequestException;

final class DeletePurchasingTransportLineAction extends PurchasingTransportAction
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

        try {
            $updated = $this->purchasingTransportRepository->deleteLine(
                (string)$transport->jsonSerialize()['purchase_transport_code'],
                $lineId,
                $companyId,
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
