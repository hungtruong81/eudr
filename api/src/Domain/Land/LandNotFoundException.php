<?php

declare(strict_types=1);

namespace App\Domain\Land;

use App\Domain\DomainException\DomainRecordNotFoundException;

class LandNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The land you requested does not exist.';
}
