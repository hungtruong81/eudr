<?php

declare(strict_types=1);

namespace App\Domain\TransportationRoute;

use App\Domain\DomainException\DomainRecordNotFoundException;

class TransportationRouteNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The transportation route you requested does not exist.';
}
