<?php

declare(strict_types=1);

namespace App\Domain\PurchasingOrder\OrderLand;

interface PurchasingOrderLandRepository
{
    /**
     * @param int $purchaseOrderId
     * @return array
     */
    public function listOrderLandsByOrderId(int $purchaseOrderId): array;

    /**
     * @param int $purchaseOrderId
     * @param int $purchaseOrderLandId
     * @return array|null
     */
    public function findOrderLandById(int $purchaseOrderId, int $purchaseOrderLandId): ?array;

    /**
     * @param int $purchaseOrderId
     * @param array $data
     * @return array|null
     */
    public function createOrderLandByOrderId(int $purchaseOrderId, array $data): ?array;

    /**
     * @param int $purchaseOrderId
     * @param int $purchaseOrderLandId
     * @param array $data
     * @return array|null
     */
    public function updateOrderLandByOrderId(
        int $purchaseOrderId,
        int $purchaseOrderLandId,
        array $data
    ): ?array;

    /**
     * @param int $purchaseOrderId
     * @param int $purchaseOrderLandId
     * @param int $deletedBy
     * @return bool
     */
    public function deleteOrderLandByOrderId(
        int $purchaseOrderId,
        int $purchaseOrderLandId,
        int $deletedBy
    ): bool;

    /**
     * @param int $purchaseOrderId
     * @param int $purchaseOrderItemId
     * @return bool
     */
    public function orderItemBelongsToOrder(int $purchaseOrderId, int $purchaseOrderItemId): bool;
}
