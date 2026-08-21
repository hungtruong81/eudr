<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingTransport;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;

final class ViewPurchasingTransportAction extends PurchasingTransportAction
{
    protected function action(): Response
    {
        $transport = $this->transport($this->scope('view'));

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => Utils::generateRandomString(25),
            'purchasing_transport' => $transport,
        ]);
    }
}
