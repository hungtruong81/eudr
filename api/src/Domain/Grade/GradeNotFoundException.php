<?php

declare(strict_types=1);

namespace App\Domain\Grade;

use App\Domain\DomainException\DomainRecordNotFoundException;

class GradeNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The grade you requested does not exist.';
}
