<?php

declare(strict_types=1);

namespace App\Domain\PurchasingOrder\BuyerSubTank;

interface PurchasingOrderBuyerSubTankRepository
{
    /**
     * @param int $purchase_order_id
     * @param array $data
     * @return array|null
     */
    public function createBuyerSubTankByOrderId(int $purchase_order_id, array $data): ?array;

    /**
     * @param int $purchase_order_id
     * @param int $purchase_order_buyer_sub_tank_id
     * @param array $data
     * @return array|null
     */
    public function updateBuyerSubTankByOrderId(int $purchase_order_id, int $purchase_order_buyer_sub_tank_id, array $data): ?array;

    /**
     * @param int $purchase_order_id
     * @param int $purchase_order_buyer_sub_tank_id
     * @param int $deleted_by
     * @return bool
     */
    public function deleteBuyerSubTankByOrderId(int $purchase_order_id, int $purchase_order_buyer_sub_tank_id, int $deleted_by): bool;

    /**
     * @param int $purchase_order_id
     * @param array $params
     * @return array
     */
    public function listBuyerSubTanksByOrderId(int $purchase_order_id, array $params = []): array;
}
