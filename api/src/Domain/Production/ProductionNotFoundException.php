<?php

declare(strict_types=1);

namespace App\Domain\Production;

use App\Domain\DomainException\DomainRecordNotFoundException;

class ProductionNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The production you requested does not exist.';
}
