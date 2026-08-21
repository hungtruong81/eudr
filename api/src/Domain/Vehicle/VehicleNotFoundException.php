<?php

declare(strict_types=1);

namespace App\Domain\Vehicle;

use App\Domain\DomainException\DomainRecordNotFoundException;

class VehicleNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The vehicle you requested does not exist.';
}
