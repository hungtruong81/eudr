<?php

declare(strict_types=1);

namespace App\Application\Actions\VendorLand;

use App\Application\Settings\SettingsInterface;
use App\Application\Actions\Action;
use App\Domain\Land\LandRepository;
use App\Domain\User\UserRepository;
use App\Domain\Vendor\VendorRepository;
use App\Domain\VendorLand\VendorLandRepository;
use Psr\Log\LoggerInterface;

abstract class VendorLandAction extends Action
{
    /**
     * @var SettingsInterface
     */
    protected $settings;
    /**
     * @var UserRepository
     */
    protected UserRepository $userRepository;
    /**
     * @var VendorRepository
     */
    protected VendorRepository $vendorRepository;
    /**
     * @var VendorLandRepository
     */
    protected VendorLandRepository $vendorLandRepository;
    /**
     * @var LandRepository
     */
    protected LandRepository $landRepository;

    /**
     * @param LoggerInterface $logger
     * @param UserRepository $userRepository
     * @param VendorRepository $vendorRepository
     * @param VendorLandRepository $vendorLandRepository
     * @param LandRepository $landRepository
     * @param SettingsInterface $settings
     */
    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        UserRepository $userRepository,
        VendorRepository $vendorRepository,
        VendorLandRepository $vendorLandRepository,
        LandRepository $landRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->userRepository = $userRepository;
        $this->vendorRepository = $vendorRepository;
        $this->vendorLandRepository = $vendorLandRepository;
        $this->landRepository = $landRepository;
        $this->settings = $settings;
    }
}