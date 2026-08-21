<?php

declare(strict_types=1);

namespace App\Domain\Vehicle;

use App\Domain\DomainException\DomainRecordErrorException;

class VehicleErrorException extends DomainRecordErrorException
{
    public $message = 'The vehicle got unknown error.';
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
