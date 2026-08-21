<?php

declare(strict_types=1);

namespace App\Domain\TransactionTicket;

use App\Domain\DomainException\DomainRecordNotFoundException;

class TransactionTicketNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The transaction ticket you requested does not exist.';
}
