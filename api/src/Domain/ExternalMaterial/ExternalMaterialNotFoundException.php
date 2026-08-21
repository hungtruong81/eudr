<?php

declare(strict_types=1);

namespace App\Domain\ExternalMaterial;

use App\Domain\DomainException\DomainRecordNotFoundException;

class ExternalMaterialNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'Không tìm thấy nguyên liệu ngoài';
}
