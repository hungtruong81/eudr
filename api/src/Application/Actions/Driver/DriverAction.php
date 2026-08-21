<?php

declare(strict_types=1);

namespace App\Application\Actions\Driver;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\User\UserRepository;
use App\Domain\Driver\DriverRepository;
use Psr\Log\LoggerInterface;

abstract class DriverAction extends Action
{
    /**
     * @var SettingsInterface
     */
    protected $settings;
    /**
     * @var DriverRepository
     */
    protected $driverRepository;
    /**
     * @var UserRepository
     */
    protected $userRepository;
    /**
     * @param LoggerInterface $logger
     * @param DriverRepository $driverRepository
     * @param UserRepository $userRepository
     * @param SettingsInterface $settings
     */
    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        UserRepository $userRepository,
        DriverRepository $driverRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->driverRepository = $driverRepository;
        $this->userRepository = $userRepository;
        $this->settings = $settings;
    }
}
