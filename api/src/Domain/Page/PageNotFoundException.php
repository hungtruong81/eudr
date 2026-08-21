<?php

declare(strict_types=1);

namespace App\Domain\Page;

use App\Domain\DomainException\DomainRecordNotFoundException;

class PageNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The page you requested does not exist.';
}
