<?php

declare(strict_types=1);

namespace App\Application\Actions\Vehicle;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\User\UserRepository;
use App\Domain\Vehicle\VehicleRepository;
use Psr\Log\LoggerInterface;

abstract class VehicleAction extends Action
{
    /**
     * @var SettingsInterface
     */
    protected $settings;
    /**
     * @var VehicleRepository
     */
    protected $vehicleRepository;
    /**
     * @var UserRepository
     */
    protected $userRepository;
    /**
     * @param LoggerInterface $logger
     * @param VehicleRepository $vehicleRepository
     * @param UserRepository $userRepository
     * @param SettingsInterface $settings
     */
    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        UserRepository $userRepository,
        VehicleRepository $vehicleRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->vehicleRepository = $vehicleRepository;
        $this->userRepository = $userRepository;
        $this->settings = $settings;
    }
}
