<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductTank;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\User\UserRepository;
use App\Domain\ProductTank\ProductTankRepository;
use App\Domain\Factory\FactoryRepository;
use Psr\Log\LoggerInterface;

abstract class ProductTankAction extends Action
{
    /**
     * @var SettingsInterface
     */
    protected $settings;
    /**
     * @var ProductTankRepository
     */
    protected $productTankRepository;
    /**
     * @var FactoryRepository
     */
    protected $factoryRepository;
    /**
     * @var UserRepository
     */
    protected $userRepository;
    /**
     * @param LoggerInterface $logger
     * @param ProductTankRepository $productTankRepository
     * @param FactoryRepository $factoryRepository
     * @param UserRepository $userRepository
     * @param SettingsInterface $settings
     */
    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        UserRepository $userRepository,
        FactoryRepository $factoryRepository,
        ProductTankRepository $productTankRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->productTankRepository = $productTankRepository;
        $this->userRepository = $userRepository;
        $this->factoryRepository = $factoryRepository;
        $this->settings = $settings;
    }
}
