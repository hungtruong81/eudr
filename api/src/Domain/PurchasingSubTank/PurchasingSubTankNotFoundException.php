<?php

declare(strict_types=1);

namespace App\Domain\PurchasingSubTank;

use App\Domain\DomainException\DomainRecordNotFoundException;

class PurchasingSubTankNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The purchasing sub tank you requested does not exist.';
}
