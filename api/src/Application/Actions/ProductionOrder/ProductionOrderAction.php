<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionOrder;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\User\UserRepository;
use App\Domain\ProductionOrder\ProductionOrderRepository;
use App\Domain\ProductType\ProductTypeRepository;
use Psr\Log\LoggerInterface;

abstract class ProductionOrderAction extends Action
{
    /**
     * @var SettingsInterface
     */
    protected $settings;
    /**
     * @var ProductionOrderRepository
     */
    protected $productionOrderRepository;
    /**
     * @var UserRepository
     */
    protected $userRepository;
    /**
     * @var ProductTypeRepository
     */
    protected $productTypeRepository;
    /**
     * @param LoggerInterface $logger
     * @param ProductionOrderRepository $productionOrderRepository
     * @param UserRepository $userRepository
     * @param ProductTypeRepository $productTypeRepository
     * @param SettingsInterface $settings
     */
    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        UserRepository $userRepository,
        ProductionOrderRepository $productionOrderRepository,
        ProductTypeRepository $productTypeRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->productionOrderRepository = $productionOrderRepository;
        $this->userRepository = $userRepository;
        $this->productTypeRepository = $productTypeRepository;
        $this->settings = $settings;
    }
}
