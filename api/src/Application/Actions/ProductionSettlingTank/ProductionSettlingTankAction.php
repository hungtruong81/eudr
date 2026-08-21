<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionSettlingTank;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\Factory\FactoryRepository;
use App\Domain\ProductionSettlingTank\ProductionSettlingTankRepository;
use App\Domain\User\UserRepository;
use Psr\Log\LoggerInterface;

abstract class ProductionSettlingTankAction extends Action
{
    /**
     * @var SettingsInterface
     */
    protected $settings;
    /**
     * @var FactoryRepository
     */
    protected $factoryRepository;
    /**
     * @var ProductionSettlingTankRepository
     */
    protected $productionSettlingTankRepository;
    /**
     * @var UserRepository
     */
    protected $userRepository;

    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        UserRepository $userRepository,
        ProductionSettlingTankRepository $productionSettlingTankRepository,
        FactoryRepository $factoryRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->productionSettlingTankRepository = $productionSettlingTankRepository;
        $this->userRepository = $userRepository;
        $this->factoryRepository = $factoryRepository;
        $this->settings = $settings;
    }
}
