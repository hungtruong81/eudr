<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingOrder;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\PurchasingOrder\PurchasingOrderRepository;
use App\Domain\PurchasingSubTank\PurchasingSubTankRepository;
use App\Domain\Land\LandRepository;
use App\Domain\User\UserRepository;
use App\Domain\Vendor\VendorRepository;
use App\Domain\VendorLand\VendorLandRepository;
use Psr\Log\LoggerInterface;

abstract class PurchasingOrderAction extends Action
{
    protected UserRepository $userRepository;
    protected PurchasingOrderRepository $purchasingOrderRepository;
    protected PurchasingSubTankRepository $purchasingSubTankRepository;
    protected VendorRepository $vendorRepository;
    protected LandRepository $landRepository;
    protected VendorLandRepository $vendorLandRepository;

    /**
     * @param LoggerInterface $logger
     * @param UserRepository $userRepository
     * @param PurchasingOrderRepository $purchasingOrderRepository
     * @param PurchasingSubTankRepository $purchasingSubTankRepository
     * @param SettingsInterface $settings
     */
    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        UserRepository $userRepository,
        PurchasingOrderRepository $purchasingOrderRepository,
        PurchasingSubTankRepository $purchasingSubTankRepository,
        VendorRepository $vendorRepository,
        LandRepository $landRepository,
        VendorLandRepository $vendorLandRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->userRepository = $userRepository;
        $this->purchasingOrderRepository = $purchasingOrderRepository;
        $this->purchasingSubTankRepository = $purchasingSubTankRepository;
        $this->vendorRepository = $vendorRepository;
        $this->landRepository = $landRepository;
        $this->vendorLandRepository = $vendorLandRepository;
        $this->settings = $settings;
    }
}
