<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionOven;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\Factory\FactoryRepository;
use App\Domain\ProductionOven\ProductionOvenRepository;
use App\Domain\User\UserRepository;
use Psr\Log\LoggerInterface;

abstract class ProductionOvenAction extends Action
{
    /**
     * @var SettingsInterface
     */
    protected $settings;
    /**
     * @var ProductionOvenRepository
     */
    protected $productionOvenRepository;
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
     * @param ProductionOvenRepository $productionOvenRepository
     * @param SettingsInterface $settings
     */
    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        UserRepository $userRepository,
        FactoryRepository $factoryRepository,
        ProductionOvenRepository $productionOvenRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->productionOvenRepository = $productionOvenRepository;
        $this->userRepository = $userRepository;
        $this->factoryRepository = $factoryRepository;
        $this->settings = $settings;
    }
}
