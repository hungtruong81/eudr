<?php

declare(strict_types=1);

namespace App\Domain\PurchasingSubTank;

use App\Domain\DomainException\DomainRecordErrorException;

class PurchasingSubTankErrorException extends DomainRecordErrorException
{
    public $message = 'The purchasing sub tank got unknown error.';
    public $code = 0;

    public function __construct(string $message = '', int $code = 0)
    {
        if ($message !== '') {
            $this->message = $message;
        }
        $this->code = $code;
    }
}
