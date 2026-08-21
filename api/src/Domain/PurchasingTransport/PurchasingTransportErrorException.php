<?php

declare(strict_types=1);

namespace App\Domain\PurchasingTransport;

use App\Domain\DomainException\DomainRecordErrorException;

class PurchasingTransportErrorException extends DomainRecordErrorException
{
    public $message = 'The purchasing transport got an unknown error.';
    public $code = 0;

    /**
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message = "", int $code = 0)
    {
        if ($message != '') {
            $this->message = $message;
        }
        $this->code = $code;
    }
}
