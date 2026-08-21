<?php

declare(strict_types=1);

namespace App\Domain\Pallet;

use App\Domain\DomainException\DomainRecordNotFoundException;

class PalletNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The pallet you requested does not exist.';
}
