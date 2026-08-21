<?php

declare(strict_types=1);

namespace App\Domain\RawMaterialRelease;

use App\Domain\DomainException\DomainRecordNotFoundException;

class RawMaterialReleaseNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The raw material release you requested does not exist.';
}
