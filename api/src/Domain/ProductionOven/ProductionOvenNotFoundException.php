<?php

declare(strict_types=1);

namespace App\Domain\ProductionOven;

use App\Domain\DomainException\DomainRecordNotFoundException;

class ProductionOvenNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The production oven you requested does not exist.';
}
