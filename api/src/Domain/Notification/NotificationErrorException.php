<?php

declare(strict_types=1);

namespace App\Domain\Notification;

use App\Domain\DomainException\DomainRecordErrorException;

class NotificationErrorException extends DomainRecordErrorException
{
    public $message = 'The notification got unknown error.';
    public $code = 0;

    /**
       * @param string $message
       * @param int $code
    */
    public function __construct(string $message="", int $code = 0)
    {
        if ($message!='') {
            $this->message = $message;
        }
        $this->code = $code;
    }
}
