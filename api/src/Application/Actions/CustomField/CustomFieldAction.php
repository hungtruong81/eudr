<?php

declare(strict_types=1);

namespace App\Application\Actions\CustomField;

use App\Application\Actions\Action;
use App\Domain\CustomField\CustomFieldRepository;
use App\Domain\User\UserRepository;
use Psr\Log\LoggerInterface;

abstract class CustomFieldAction extends Action
{
    protected CustomFieldRepository $customFieldRepository;
    protected UserRepository $userRepository;

    public function __construct(
        LoggerInterface $logger,
        CustomFieldRepository $customFieldRepository,
        UserRepository $userRepository
    ) {
        parent::__construct($logger);
        $this->customFieldRepository = $customFieldRepository;
        $this->userRepository        = $userRepository;
    }

    /** Supported entity types */
    protected const ENTITY_TYPES = ['land', 'plant', 'harvest', 'customer', 'product', 'sales_order', 'product_lot_import_none_eudr'];

    /** Supported field types */
    protected const FIELD_TYPES = ['text', 'textarea', 'number', 'date', 'datetime', 'boolean', 'select'];
}
