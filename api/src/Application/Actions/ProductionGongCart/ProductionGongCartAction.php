<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionGongCart;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\Factory\FactoryRepository;
use App\Domain\ProductionGongCart\ProductionGongCartRepository;
use App\Domain\User\UserRepository;
use Psr\Log\LoggerInterface;

abstract class ProductionGongCartAction extends Action
{
    /**
     * @var SettingsInterface
     */
    protected $settings;
    /**
     * @var ProductionGongCartRepository
     */
    protected $productionGongCartRepository;
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
     * @param ProductionGongCartRepository $productionGongCartRepository
     * @param SettingsInterface $settings
     */
    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        UserRepository $userRepository,
        FactoryRepository $factoryRepository,
        ProductionGongCartRepository $productionGongCartRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->productionGongCartRepository = $productionGongCartRepository;
        $this->userRepository = $userRepository;
        $this->factoryRepository = $factoryRepository;
        $this->settings = $settings;
    }
}
