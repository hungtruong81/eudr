<?php

declare(strict_types=1);

namespace App\Domain\Vendor;

use App\Domain\DomainException\DomainRecordErrorException;

class VendorErrorException extends DomainRecordErrorException
{
    public $message = 'The vendor got unknown error.';
    public $code = 0;

    public function __construct(string $message = '', int $code = 0)
    {
        if ($message !== '') {
            $this->message = $message;
        }
        $this->code = $code;
    }
}
