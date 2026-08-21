<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductType;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\User\UserRepository;
use App\Domain\ProductType\ProductTypeRepository;
use Psr\Log\LoggerInterface;

abstract class ProductTypeAction extends Action
{
    /**
     * @var SettingsInterface
     */
    protected $settings;
    /**
     * @var ProductTypeRepository
     */
    protected $productTypeRepository;
    /**
     * @var UserRepository
     */
    protected $userRepository;
    /**
     * @param LoggerInterface $logger
     * @param ProductTypeRepository $productTypeRepository
     * @param UserRepository $userRepository
     * @param SettingsInterface $settings
     */
    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        UserRepository $userRepository,
        ProductTypeRepository $productTypeRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->productTypeRepository = $productTypeRepository;
        $this->userRepository = $userRepository;
        $this->settings = $settings;
    }
}
