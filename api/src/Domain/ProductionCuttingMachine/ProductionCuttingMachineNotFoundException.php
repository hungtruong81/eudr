<?php

declare(strict_types=1);

namespace App\Domain\ProductionCuttingMachine;

use App\Domain\DomainException\DomainRecordNotFoundException;

class ProductionCuttingMachineNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The production cutting machine you requested does not exist.';
}
