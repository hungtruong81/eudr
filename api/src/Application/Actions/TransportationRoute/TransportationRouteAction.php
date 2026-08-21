<?php

declare(strict_types=1);

namespace App\Application\Actions\TransportationRoute;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\User\UserRepository;
use App\Domain\TransportationRoute\TransportationRouteRepository;
use App\Domain\Factory\FactoryRepository;
use App\Domain\Vehicle\VehicleRepository;
use App\Domain\TransactionTicket\TransactionTicketRepository;
use App\Domain\RawMaterialTank\RawMaterialTankRepository;
use Psr\Log\LoggerInterface;

abstract class TransportationRouteAction extends Action
{
    /**
     * @var SettingsInterface
     */
    protected $settings;
    /**
     * @var TransportationRouteRepository
     */
    protected $transportationRouteRepository;
    /**
     * @var FactoryRepository
     */
    protected $factoryRepository;
    /**
     * @var VehicleRepository
     */
    protected $vehicleRepository;
    /**
     * @var TransactionTicketRepository
     */
    protected $transactionTicketRepository;
    /**
     * @var RawMaterialTankRepository
     */
    protected $rawMaterialTankRepository;
    /**
     * @var UserRepository
     */
    protected $userRepository;
    /**
     * @param LoggerInterface $logger
     * @param TransportationRouteRepository $transportationRouteRepository
     * @param FactoryRepository $factoryRepository
     * @param VehicleRepository $vehicleRepository
     * @param TransactionTicketRepository $transactionTicketRepository
     * @param UserRepository $userRepository
     * @param RawMaterialTankRepository $rawMaterialTankRepository
     * @param SettingsInterface $settings
     */
    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        UserRepository $userRepository,
        FactoryRepository $factoryRepository,
        VehicleRepository $vehicleRepository,
        TransactionTicketRepository $transactionTicketRepository,
        TransportationRouteRepository $transportationRouteRepository,
        RawMaterialTankRepository $rawMaterialTankRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->transportationRouteRepository = $transportationRouteRepository;
        $this->userRepository = $userRepository;
        $this->factoryRepository = $factoryRepository;
        $this->vehicleRepository = $vehicleRepository;
        $this->transactionTicketRepository = $transactionTicketRepository;
        $this->rawMaterialTankRepository = $rawMaterialTankRepository;
        $this->settings = $settings;
    }
}
