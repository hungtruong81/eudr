<?php

declare(strict_types=1);

namespace App\Domain\File;

use App\Domain\DomainException\DomainRecordNotFoundException;

class FileNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The file you requested does not exist.';
}
