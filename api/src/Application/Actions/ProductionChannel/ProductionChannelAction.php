<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionChannel;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\User\UserRepository;
use App\Domain\Factory\FactoryRepository;
use App\Domain\ProductionChannel\ProductionChannelRepository;
use App\Domain\ProductionCuttingMachine\ProductionCuttingMachineRepository;
use App\Domain\ProductionOrder\ProductionOrderRepository;
use App\Domain\RawMaterialTank\RawMaterialTankRepository;
use Psr\Log\LoggerInterface;

abstract class ProductionChannelAction extends Action
{
    /**
     * @var SettingsInterface
     */
    protected $settings;
    /**
     * @var ProductionChannelRepository
     */
    protected $productionChannelRepository;
    /**
     * @var UserRepository
     */
    protected $userRepository;
    /**
     * @var FactoryRepository
     */
    protected $factoryRepository;
    /**
     * @var ProductionOrderRepository
     */
    protected $productionOrderRepository;
    /**
     * @var ProductionCuttingMachineRepository
     */
    protected $productionCuttingMachineRepository;
    /**
     * @var RawMaterialTankRepository
     */
    protected $rawMaterialTankRepository;

    /**
     * @param LoggerInterface $logger
     * @param UserRepository $userRepository
     * @param FactoryRepository $factoryRepository
     * @param ProductionChannelRepository $productionChannelRepository
    * @param ProductionOrderRepository $productionOrderRepository
    * @param ProductionCuttingMachineRepository $productionCuttingMachineRepository
     * @param RawMaterialTankRepository $rawMaterialTankRepository
     * @param SettingsInterface $settings
     */
    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        UserRepository $userRepository,
        FactoryRepository $factoryRepository,
        ProductionChannelRepository $productionChannelRepository,
        ProductionOrderRepository $productionOrderRepository,
        ProductionCuttingMachineRepository $productionCuttingMachineRepository,
        RawMaterialTankRepository $rawMaterialTankRepository,
        SettingsInterface $settings
    ) {
        parent::__construct($logger);
        $this->productionChannelRepository = $productionChannelRepository;
        $this->productionOrderRepository = $productionOrderRepository;
        $this->productionCuttingMachineRepository = $productionCuttingMachineRepository;
        $this->rawMaterialTankRepository = $rawMaterialTankRepository;
        $this->userRepository = $userRepository;
        $this->factoryRepository = $factoryRepository;
        $this->settings = $settings;
    }
}
