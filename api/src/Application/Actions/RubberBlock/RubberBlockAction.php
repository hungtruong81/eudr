<?php

declare(strict_types=1);

namespace App\Application\Actions\RubberBlock;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\User\UserRepository;
use App\Domain\RubberBlock\RubberBlockRepository;
use App\Domain\ProductType\ProductTypeRepository;
use App\Domain\ProductionOrder\ProductionOrderRepository;
use Psr\Log\LoggerInterface;

abstract class RubberBlockAction extends Action
{
    /**
     * @var SettingsInterface
     */
    protected $settings;
    /**
     * @var UserRepository
     */
    protected $userRepository;
    /**
     * @var RubberBlockRepository
     */
    protected $rubberBlockRepository;
    /**
     * @var ProductTypeRepository
     */
    protected $productTypeRepository;
    /**
     * @var ProductionOrderRepository
     */
    protected $productionOrderRepository;

    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        UserRepository $userRepository,
        RubberBlockRepository $rubberBlockRepository,
        ProductTypeRepository $productTypeRepository,
        ProductionOrderRepository $productionOrderRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->userRepository = $userRepository;
        $this->rubberBlockRepository = $rubberBlockRepository;
        $this->productTypeRepository = $productTypeRepository;
        $this->productionOrderRepository = $productionOrderRepository;
        $this->settings = $settings;
    }
}
