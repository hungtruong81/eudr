<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingSubTank;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\Factory\FactoryRepository;
use App\Domain\PurchasingSubTank\PurchasingSubTankRepository;
use App\Domain\User\UserRepository;
use Psr\Log\LoggerInterface;

abstract class PurchasingSubTankAction extends Action
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
     * @var PurchasingSubTankRepository
     */
    protected $purchasingSubTankRepository;
    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @param LoggerInterface $logger
     * @param PurchasingSubTankRepository $purchasingSubTankRepository
     * @param FactoryRepository $factoryRepository
     * @param UserRepository $userRepository
     * @param SettingsInterface $settings
     */
    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        UserRepository $userRepository,
        PurchasingSubTankRepository $purchasingSubTankRepository,
        FactoryRepository $factoryRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->purchasingSubTankRepository = $purchasingSubTankRepository;
        $this->userRepository = $userRepository;
        $this->factoryRepository = $factoryRepository;
        $this->settings = $settings;
    }
}
