<?php

declare(strict_types=1);

namespace App\Domain\RawMaterialRelease;

use App\Domain\DomainException\DomainRecordErrorException;

class RawMaterialReleaseErrorException extends DomainRecordErrorException
{
    public $message = 'The raw material release got unknown error.';
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
