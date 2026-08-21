<?php

declare(strict_types=1);

namespace App\Domain\PurchasingOrder\Item;

interface PurchasingOrderItemRepository
{
    /**
     * @return array<int,array<string,mixed>>
     */
    public function listOrderItemsByOrderId(int $purchase_order_id): array;

    /**
     * @param int $purchase_order_id
     * @param array $item
     * @return array|null
     */
    public function addOrderItemWithPermission(int $purchase_order_id, array $item, ?int $auth_user_id, string $scope, ?int $company_id = null): ?array;

    /**
     * @param int $purchase_order_id
     * @param int $purchase_order_item_id
     * @param array $item
     * @return array|null
     */
    public function updateOrderItemWithPermission(int $purchase_order_id, int $purchase_order_item_id, array $item, ?int $auth_user_id, string $scope, ?int $company_id = null): ?array;

    /**
     * @param int $purchase_order_id
     * @param int $purchase_order_item_id
     * @param int $deleted_by
     * @return bool
     */
    public function deleteOrderItemWithPermission(int $purchase_order_id, int $purchase_order_item_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null): bool;
}
