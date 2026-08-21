<?php

declare(strict_types=1);

namespace App\Domain\PurchasingFactoryReceipt;

use App\Domain\DomainException\DomainRecordNotFoundException;

class PurchasingFactoryReceiptNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The purchasing factory receipt you requested does not exist.';
}
