<?php

declare(strict_types=1);

namespace App\Domain\ProductionRoller;

use App\Domain\DomainException\DomainRecordNotFoundException;

class ProductionRollerNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The production roller you requested does not exist.';
}
