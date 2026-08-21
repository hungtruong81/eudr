<?php

declare(strict_types=1);

namespace App\Application\Actions\RawMaterialRelease;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\User\UserRepository;
use App\Domain\RawMaterialRelease\RawMaterialReleaseRepository;
use App\Domain\RawMaterialTank\RawMaterialTankRepository;
use App\Domain\ProductionOrder\ProductionOrderRepository;
use Psr\Log\LoggerInterface;

abstract class RawMaterialReleaseAction extends Action
{
    /**
     * @var SettingsInterface
     */
    protected $settings;
    /**
     * @var RawMaterialReleaseRepository
     */
    protected $rawMaterialReleaseRepository;
    /**
     * @var UserRepository
     */
    protected $userRepository;
    /**
     * @var RawMaterialTankRepository
     */
    protected $rawMaterialTankRepository;
    /**
     * @var ProductionOrderRepository
     */
    protected $productionOrderRepository;
    /**
     * @param LoggerInterface $logger
     * @param RawMaterialReleaseRepository $rawMaterialReleaseRepository
     * @param UserRepository $userRepository
     * @param RawMaterialTankRepository $rawMaterialTankRepository
     * @param ProductionOrderRepository $productionOrderRepository
     * @param SettingsInterface $settings
     */
    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        UserRepository $userRepository,
        RawMaterialReleaseRepository $rawMaterialReleaseRepository,
        RawMaterialTankRepository $rawMaterialTankRepository,
        ProductionOrderRepository $productionOrderRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->rawMaterialReleaseRepository = $rawMaterialReleaseRepository;
        $this->userRepository = $userRepository;
        $this->rawMaterialTankRepository = $rawMaterialTankRepository;
        $this->productionOrderRepository = $productionOrderRepository;
        $this->settings = $settings;
    }
}
