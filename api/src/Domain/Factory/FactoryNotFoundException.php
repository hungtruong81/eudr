<?php

declare(strict_types=1);

namespace App\Domain\Factory;

use App\Domain\DomainException\DomainRecordNotFoundException;

class FactoryNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The factory you requested does not exist.';
}
