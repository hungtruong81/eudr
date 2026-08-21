<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionRoller;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\Factory\FactoryRepository;
use App\Domain\ProductionRoller\ProductionRollerRepository;
use App\Domain\User\UserRepository;
use Psr\Log\LoggerInterface;

abstract class ProductionRollerAction extends Action
{
    /**
     * @var SettingsInterface
     */
    protected $settings;
    /**
     * @var ProductionRollerRepository
     */
    protected $productionRollerRepository;
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
     * @param FactoryRepository $factoryRepository
     * @param ProductionRollerRepository $productionRollerRepository
     * @param SettingsInterface $settings
     */
    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        UserRepository $userRepository,
        FactoryRepository $factoryRepository,
        ProductionRollerRepository $productionRollerRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->productionRollerRepository = $productionRollerRepository;
        $this->userRepository = $userRepository;
        $this->factoryRepository = $factoryRepository;
        $this->settings = $settings;
    }
}
