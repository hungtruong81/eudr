<?php

declare(strict_types=1);

namespace App\Domain\RawMaterialTank;

use App\Domain\DomainException\DomainRecordNotFoundException;

class RawMaterialTankNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The raw material tank you requested does not exist.';
}
