<?php

declare(strict_types=1);

namespace App\Domain\RubberBlock;

use App\Domain\DomainException\DomainRecordNotFoundException;

class RubberBlockNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The rubber block you requested does not exist.';
}
