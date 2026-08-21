<?php

declare(strict_types=1);

namespace App\Domain\ProductLot;

use App\Domain\DomainException\DomainRecordNotFoundException;

class ProductLotNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The product lot you requested does not exist.';
}
