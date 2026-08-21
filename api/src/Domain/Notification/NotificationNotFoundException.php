<?php

declare(strict_types=1);

namespace App\Domain\Notification;

use App\Domain\DomainException\DomainRecordNotFoundException;

class NotificationNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The notification you requested does not exist.';
}
