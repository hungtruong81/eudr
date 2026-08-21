<?php

declare(strict_types=1);

namespace App\Domain\Harvest;

use App\Domain\DomainException\DomainRecordNotFoundException;

class HarvestNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The harvest you requested does not exist.';
}
