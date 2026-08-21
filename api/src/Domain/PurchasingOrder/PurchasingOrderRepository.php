<?php

declare(strict_types=1);

namespace App\Domain\PurchasingOrder;

use App\Domain\PurchasingOrder\BuyerSubTank\PurchasingOrderBuyerSubTankRepository;
use App\Domain\PurchasingOrder\Item\PurchasingOrderItemRepository;
use App\Domain\PurchasingOrder\Lifecycle\PurchasingOrderLifecycleRepository;
use App\Domain\PurchasingOrder\Order\PurchasingOrderRepository as PurchasingOrderCoreRepository;
use App\Domain\PurchasingOrder\OrderLand\PurchasingOrderLandRepository;
use App\Domain\PurchasingOrder\SellerSubTank\PurchasingOrderSellerSubTankRepository;

interface PurchasingOrderRepository extends
    PurchasingOrderCoreRepository,
    PurchasingOrderItemRepository,
    PurchasingOrderLandRepository,
    PurchasingOrderSellerSubTankRepository,
    PurchasingOrderBuyerSubTankRepository,
    PurchasingOrderLifecycleRepository {}
