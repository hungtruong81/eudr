<?php

declare(strict_types=1);

namespace App\Application\Actions\General;

use Psr\Http\Message\ResponseInterface as Response;

class ClearCacheRedisAction extends GeneralAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $this->predis->flushAll();

        $data_return = array('result' => "success");

        return $this->respondWithData($data_return);
    }
}
