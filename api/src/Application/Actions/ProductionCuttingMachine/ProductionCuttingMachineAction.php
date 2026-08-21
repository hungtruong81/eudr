<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionCuttingMachine;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\Factory\FactoryRepository;
use App\Domain\ProductionCuttingMachine\ProductionCuttingMachineRepository;
use App\Domain\ProductionRoller\ProductionRollerRepository;
use App\Domain\User\UserRepository;
use Psr\Log\LoggerInterface;

abstract class ProductionCuttingMachineAction extends Action
{
    /**
     * @var SettingsInterface
     */
    protected $settings;
    /**
     * @var ProductionCuttingMachineRepository
     */
    protected $productionCuttingMachineRepository;
    /**
     * @var ProductionRollerRepository
     */
    protected $productionRollerRepository;
    /**
     * @var UserRepository
     */
    protected $userRepository;
    /**
     * @var FactoryRepository
     */
    protected $factoryRepository;

    /**
     * @param LoggerInterface $logger
     * @param UserRepository $userRepository
     * @param FactoryRepository $factoryRepository
     * @param ProductionCuttingMachineRepository $productionCuttingMachineRepository
     * @param ProductionRollerRepository $productionRollerRepository
     * @param SettingsInterface $settings
     */
    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        UserRepository $userRepository,
        FactoryRepository $factoryRepository,
        ProductionCuttingMachineRepository $productionCuttingMachineRepository,
        ProductionRollerRepository $productionRollerRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->productionCuttingMachineRepository = $productionCuttingMachineRepository;
        $this->productionRollerRepository = $productionRollerRepository;
        $this->userRepository = $userRepository;
        $this->factoryRepository = $factoryRepository;
        $this->settings = $settings;
    }
}
