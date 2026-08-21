<?php

declare(strict_types=1);

namespace App\Domain\ProductionGongCart;

use App\Domain\DomainException\DomainRecordNotFoundException;

class ProductionGongCartNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The production gong cart you requested does not exist.';
}
