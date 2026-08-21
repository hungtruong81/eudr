<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionPallet;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\Factory\FactoryRepository;
use App\Domain\ProductionPallet\ProductionPalletRepository;
use App\Domain\User\UserRepository;
use Psr\Log\LoggerInterface;

abstract class ProductionPalletAction extends Action
{
    /**
     * @var SettingsInterface
     */
    protected $settings;

    /**
     * @var ProductionPalletRepository
     */
    protected $productionPalletRepository;

    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @var FactoryRepository
     */
    protected $factoryRepository;

    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        UserRepository $userRepository,
        FactoryRepository $factoryRepository,
        ProductionPalletRepository $productionPalletRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->productionPalletRepository = $productionPalletRepository;
        $this->userRepository = $userRepository;
        $this->factoryRepository = $factoryRepository;
        $this->settings = $settings;
    }
}
