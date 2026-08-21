<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingFactoryReceipt;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;

final class ViewPurchasingFactoryReceiptAction extends PurchasingFactoryReceiptAction
{
    protected function action(): Response
    {
        $receipt = $this->receipt($this->scope('view'));

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => Utils::generateRandomString(25),
            'purchasing_factory_receipt' => $receipt,
        ]);
    }
}