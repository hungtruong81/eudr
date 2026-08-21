<?php

declare(strict_types=1);

namespace App\Domain\Price;

use App\Domain\DomainException\DomainRecordNotFoundException;

class PriceNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The price you requested does not exist.';
}
