<?php

declare(strict_types=1);

namespace App\Application\Actions\Vendor;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\Vendor\VendorRepository;
use App\Domain\User\UserRepository;
use Psr\Log\LoggerInterface;

abstract class VendorAction extends Action
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
     * @param LoggerInterface $logger
     * @param VendorRepository $vendorRepository
     * @param UserRepository $userRepository
     * @param SettingsInterface $settings
     */
    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        UserRepository $userRepository,
        VendorRepository $vendorRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->userRepository = $userRepository;
        $this->vendorRepository = $vendorRepository;
        $this->settings = $settings;
    }
}
