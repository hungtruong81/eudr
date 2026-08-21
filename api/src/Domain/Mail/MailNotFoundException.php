<?php

declare(strict_types=1);

namespace App\Domain\Mail;

use App\Domain\DomainException\DomainRecordNotFoundException;

class MailNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The mail you requested does not exist.';
}
