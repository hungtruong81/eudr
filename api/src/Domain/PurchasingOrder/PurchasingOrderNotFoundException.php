<?php

declare(strict_types=1);

namespace App\Domain\PurchasingOrder;

use App\Domain\DomainException\DomainRecordNotFoundException;

class PurchasingOrderNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The purchasing order you requested does not exist.';
}
