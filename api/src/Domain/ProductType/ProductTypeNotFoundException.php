<?php

declare(strict_types=1);

namespace App\Domain\ProductType;

use App\Domain\DomainException\DomainRecordNotFoundException;

class ProductTypeNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The product type you requested does not exist.';
}
