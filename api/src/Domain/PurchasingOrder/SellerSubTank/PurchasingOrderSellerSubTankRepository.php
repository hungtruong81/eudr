<?php

declare(strict_types=1);

namespace App\Domain\PurchasingOrder\SellerSubTank;

interface PurchasingOrderSellerSubTankRepository
{
    /**
     * @param int $purchase_order_id
     * @param array $data
     * @return array|null
     */
    public function createSellerSubTankByOrderId(int $purchase_order_id, array $data): ?array;

    /**
     * @param int $purchase_order_id
     * @param int $purchase_order_seller_sub_tank_id
     * @param array $data
     * @return array|null
     */
    public function updateSellerSubTankByOrderId(int $purchase_order_id, int $purchase_order_seller_sub_tank_id, array $data): ?array;

    /**
     * @param int $purchase_order_id
     * @param int $purchase_order_seller_sub_tank_id
     * @param int $deleted_by
     * @return bool
     */
    public function deleteSellerSubTankByOrderId(int $purchase_order_id, int $purchase_order_seller_sub_tank_id, int $deleted_by): bool;

    /**
     * @param int $purchase_order_id
     * @param array $params
     * @return array
     */
    public function listSellerSubTanksByOrderId(int $purchase_order_id, array $params = []): array;
}
