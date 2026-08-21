<?php

declare(strict_types=1);

namespace App\Domain\ProductionOrder;

use App\Domain\DomainException\DomainRecordNotFoundException;

class ProductionOrderNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The production order you requested does not exist.';
}
