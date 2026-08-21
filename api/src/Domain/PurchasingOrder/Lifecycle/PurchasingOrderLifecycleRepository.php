<?php

declare(strict_types=1);

namespace App\Domain\PurchasingOrder\Lifecycle;

use App\Domain\PurchasingOrder\PurchasingOrder;

interface PurchasingOrderLifecycleRepository
{
    /**
     * @param int $purchase_order_id
     * @param array $update_data
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @return PurchasingOrder|null
     */
    public function sendOrderWithPermission(int $purchase_order_id, array $update_data, ?int $auth_user_id, string $scope, ?int $company_id = null): ?PurchasingOrder;

    /**
     * @param int $purchase_order_id
     * @param int $actor_user_id
     * @param string|null $notes
     * @return PurchasingOrder|null
     */
    public function confirmSellerById(int $purchase_order_id, int $actor_user_id, ?string $notes = null): ?PurchasingOrder;

    /**
     * @param int $purchase_order_id
     * @param int $actor_user_id
     * @param string|null $notes
     * @return PurchasingOrder|null
     */
    public function reconfirmBuyerById(int $purchase_order_id, int $actor_user_id, ?string $notes = null): ?PurchasingOrder;

    /**
     * @param int $purchase_order_id
     * @param array $update_data
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @return PurchasingOrder|null
     */
    public function cancelDraftOrderWithPermission(int $purchase_order_id, array $update_data, ?int $auth_user_id, string $scope, ?int $company_id = null): ?PurchasingOrder;
}
