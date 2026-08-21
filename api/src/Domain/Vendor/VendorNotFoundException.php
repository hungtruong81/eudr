<?php

declare(strict_types=1);

namespace App\Domain\Vendor;

use App\Domain\DomainException\DomainRecordNotFoundException;

class VendorNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The vendor you requested does not exist.';
}
