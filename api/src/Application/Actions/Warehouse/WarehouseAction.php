<?php

declare(strict_types=1);

namespace App\Application\Actions\Warehouse;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\Factory\FactoryRepository;
use App\Domain\User\UserRepository;
use App\Domain\Warehouse\WarehouseRepository;
use Psr\Log\LoggerInterface;

abstract class WarehouseAction extends Action
{
    /**
     * @var SettingsInterface
     */
    protected $settings;

    /**
     * @var WarehouseRepository
     */
    protected $warehouseRepository;

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
     * @param WarehouseRepository $warehouseRepository
     * @param FactoryRepository $factoryRepository
     * @param SettingsInterface $settings
     */
    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        UserRepository $userRepository,
        WarehouseRepository $warehouseRepository,
        FactoryRepository $factoryRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->warehouseRepository = $warehouseRepository;
        $this->userRepository = $userRepository;
        $this->factoryRepository = $factoryRepository;
        $this->settings = $settings;
    }
}
