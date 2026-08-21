<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionPressing;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\Factory\FactoryRepository;
use App\Domain\ProductionPressing\ProductionPressingRepository;
use App\Domain\User\UserRepository;
use Psr\Log\LoggerInterface;

abstract class ProductionPressingAction extends Action
{
    /**
     * @var SettingsInterface
     */
    protected $settings;

    /**
     * @var ProductionPressingRepository
     */
    protected $productionPressingRepository;

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
        ProductionPressingRepository $productionPressingRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->productionPressingRepository = $productionPressingRepository;
        $this->userRepository = $userRepository;
        $this->factoryRepository = $factoryRepository;
        $this->settings = $settings;
    }
}
