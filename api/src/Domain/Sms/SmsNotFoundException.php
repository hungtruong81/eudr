<?php

declare(strict_types=1);

namespace App\Domain\Sms;

use App\Domain\DomainException\DomainRecordNotFoundException;

class SmsNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The SMS you requested does not exist.';
}
