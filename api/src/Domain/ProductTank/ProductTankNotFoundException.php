<?php

declare(strict_types=1);

namespace App\Domain\ProductTank;

use App\Domain\DomainException\DomainRecordNotFoundException;

class ProductTankNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The product tank you requested does not exist.';
}
