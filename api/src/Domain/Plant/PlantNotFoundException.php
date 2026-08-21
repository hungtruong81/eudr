<?php

declare(strict_types=1);

namespace App\Domain\Plant;

use App\Domain\DomainException\DomainRecordNotFoundException;

class PlantNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The plant you requested does not exist.';
}
