<?php

declare(strict_types=1);

namespace App\Domain\ProductionSettlingTank;

use App\Domain\DomainException\DomainRecordNotFoundException;

class ProductionSettlingTankNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The production settling tank you requested does not exist.';
}
