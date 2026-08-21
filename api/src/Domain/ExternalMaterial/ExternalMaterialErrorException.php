<?php

declare(strict_types=1);

namespace App\Domain\ExternalMaterial;

use App\Domain\DomainException\DomainRecordErrorException;

class ExternalMaterialErrorException extends DomainRecordErrorException
{
    public $message = 'Lỗi nguyên liệu ngoài';
}
