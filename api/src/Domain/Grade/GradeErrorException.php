<?php

declare(strict_types=1);

namespace App\Domain\Grade;

use App\Domain\DomainException\DomainRecordErrorException;

class GradeErrorException extends DomainRecordErrorException
{
    public $message = 'The grade got unknown error.';
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
