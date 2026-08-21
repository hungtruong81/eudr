<?php

declare(strict_types=1);

namespace App\Domain\Connection;

use App\Domain\DomainException\DomainRecordNotFoundException;

class ConnectionNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The connection you requested does not exist.';
}
