<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PurchasingOrder;

use App\Application\Utility\CurrentUserContext;
use App\Domain\PurchasingOrder\PurchasingOrderRepository;
use App\Infrastructure\Persistence\PurchasingOrder\BuyerSubTank\PurchasingOrderBuyerSubTankRepositoryTrait;
use App\Infrastructure\Persistence\PurchasingOrder\Item\PurchasingOrderItemRepositoryTrait;
use App\Infrastructure\Persistence\PurchasingOrder\Lifecycle\PurchasingOrderLifecycleRepositoryTrait;
use App\Infrastructure\Persistence\PurchasingOrder\Order\PurchasingOrderRepositoryTrait;
use App\Infrastructure\Persistence\PurchasingOrder\OrderLand\PurchasingOrderLandRepositoryTrait;
use App\Infrastructure\Persistence\PurchasingOrder\SellerSubTank\PurchasingOrderSellerSubTankRepositoryTrait;
use App\Infrastructure\Persistence\PurchasingOrder\Support\PurchasingOrderRepositorySupport;

class InDatabasePurchasingOrderRepository implements PurchasingOrderRepository
{
    use PurchasingOrderRepositorySupport;
    use PurchasingOrderRepositoryTrait;
    use PurchasingOrderItemRepositoryTrait;
    use PurchasingOrderLandRepositoryTrait;
    use PurchasingOrderSellerSubTankRepositoryTrait;
    use PurchasingOrderBuyerSubTankRepositoryTrait;
    use PurchasingOrderLifecycleRepositoryTrait;

    /** @var \MysqliDb */
    private $db;

    private CurrentUserContext $currentUser;

    public function __construct(\MysqliDb $db, CurrentUserContext $currentUserContext)
    {
        $this->db = $db;
        $this->currentUser = $currentUserContext;
    }
}
