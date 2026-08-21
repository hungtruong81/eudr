<?php

declare(strict_types=1);

namespace App\Application\Actions\FinishedGoodsReceipt;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\User\UserRepository;
use App\Domain\ProductionOrder\ProductionOrderRepository;
use App\Domain\FinishedGoodsReceipt\FinishedGoodsReceiptRepository;
use App\Domain\ProductType\ProductTypeRepository;
use App\Domain\ProductTank\ProductTankRepository;
use App\Domain\RubberBlock\RubberBlockRepository;
use Psr\Log\LoggerInterface;

abstract class FinishedGoodsReceiptAction extends Action
{
    /**
     * @var SettingsInterface
     */
    protected $settings;
    /**
     * @var ProductionOrderRepository
     */
    protected $productionOrderRepository;
    /**
     * @var UserRepository
     */
    protected $userRepository;
    /**
     * @var ProductTypeRepository
     */
    protected $productTypeRepository;
    /**
     * @var FinishedGoodsReceiptRepository
     */
    protected $finishedGoodsReceiptRepository;
    /**
     * @var ProductTankRepository
     */
    protected $productTankRepository;
    /**
     * @var RubberBlockRepository
     */
    protected $rubberBlockRepository;
    /**
     * @param LoggerInterface $logger
     * @param ProductionOrderRepository $productionOrderRepository
     * @param UserRepository $userRepository
     * @param ProductTypeRepository $productTypeRepository
     * @param FinishedGoodsReceiptRepository $finishedGoodsReceiptRepository
     * @param ProductTankRepository $productTankRepository
     * @param RubberBlockRepository $rubberBlockRepository
     * @param SettingsInterface $settings
     */
    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        UserRepository $userRepository,
        ProductionOrderRepository $productionOrderRepository,
        ProductTypeRepository $productTypeRepository,
        FinishedGoodsReceiptRepository $finishedGoodsReceiptRepository,
        ProductTankRepository $productTankRepository,
        RubberBlockRepository $rubberBlockRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->productionOrderRepository = $productionOrderRepository;
        $this->userRepository = $userRepository;
        $this->productTypeRepository = $productTypeRepository;
        $this->finishedGoodsReceiptRepository = $finishedGoodsReceiptRepository;
        $this->productTankRepository = $productTankRepository;
        $this->rubberBlockRepository = $rubberBlockRepository;
        $this->settings = $settings;
    }
}
