<?php

declare(strict_types=1);

namespace App\Domain\Driver;

use App\Domain\DomainException\DomainRecordNotFoundException;

class DriverNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The driver you requested does not exist.';
}
