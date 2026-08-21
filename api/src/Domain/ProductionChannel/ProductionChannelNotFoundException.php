<?php

declare(strict_types=1);

namespace App\Domain\ProductionChannel;

use App\Domain\DomainException\DomainRecordNotFoundException;

class ProductionChannelNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The production channel you requested does not exist.';
}
