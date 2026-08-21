<?php

declare(strict_types=1);

namespace App\Application\Actions\VehicleTank;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\User\UserRepository;
use App\Domain\Vehicle\VehicleRepository;
use App\Domain\VehicleTank\VehicleTankRepository;
use Psr\Log\LoggerInterface;

abstract class VehicleTankAction extends Action
{
    protected VehicleTankRepository $vehicleTankRepository;
    protected VehicleRepository $vehicleRepository;
    protected UserRepository $userRepository;

    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        UserRepository $userRepository,
        VehicleTankRepository $vehicleTankRepository,
        VehicleRepository $vehicleRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->userRepository = $userRepository;
        $this->vehicleTankRepository = $vehicleTankRepository;
        $this->vehicleRepository = $vehicleRepository;
        $this->settings = $settings;
    }
}
