<?php

declare(strict_types=1);

namespace App\Domain\FinishedGoodsReceipt;

use App\Domain\DomainException\DomainRecordNotFoundException;

class FinishedGoodsReceiptNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The finished goods receipt you requested does not exist.';
}
