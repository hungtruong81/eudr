<?php

declare(strict_types=1);

namespace App\Domain\Warehouse;

use App\Domain\DomainException\DomainRecordErrorException;

class WarehouseErrorException extends DomainRecordErrorException
{
    public $message = 'The warehouse got unknown error.';
    public $code = 0;

    public function __construct(string $message = '', int $code = 0)
    {
        if ($message !== '') {
            $this->message = $message;
        }
        $this->code = $code;
    }
}
