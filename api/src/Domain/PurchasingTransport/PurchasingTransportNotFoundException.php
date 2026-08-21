<?php

declare(strict_types=1);

namespace App\Domain\PurchasingTransport;

use App\Domain\DomainException\DomainRecordNotFoundException;

class PurchasingTransportNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The purchasing transport you requested does not exist.';
}
