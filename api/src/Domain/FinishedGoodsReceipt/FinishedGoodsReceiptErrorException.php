<?php

declare(strict_types=1);

namespace App\Domain\FinishedGoodsReceipt;

use App\Domain\DomainException\DomainRecordErrorException;

class FinishedGoodsReceiptErrorException extends DomainRecordErrorException
{
    public $message = 'The finished goods receipt got unknown error.';
    public $code = 0;

    /**
       * @param string $message
       * @param int $code
    */
    public function __construct(string $message="", int $code = 0)
    {
        if ($message!='') {
            $this->message = $message;
        }
        $this->code = $code;
    }
}
