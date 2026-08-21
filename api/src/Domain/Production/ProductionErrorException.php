<?php

declare(strict_types=1);

namespace App\Domain\Production;

use App\Domain\DomainException\DomainRecordErrorException;

class ProductionErrorException extends DomainRecordErrorException
{
    public $message = 'The production got unknown error.';
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
