<?php

declare(strict_types=1);

namespace App\Domain\PurchasingOrder\Order;

use App\Domain\PurchasingOrder\PurchasingOrder;

interface PurchasingOrderRepository
{
    /**
     * @param array $params
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return array
     */
    public function findAll(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;

    /**
     * @param int $purchase_order_id
     * @return PurchasingOrder|null
     */
    public function findOrderOfId(int $purchase_order_id): ?PurchasingOrder;

    /**
     * @param int $purchase_order_id
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return PurchasingOrder|null
     */
    public function findOrderOfIdWithPermission(int $purchase_order_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?PurchasingOrder;

    /**
     * @param string $purchase_order_code
     * @return PurchasingOrder|null
     */
    public function findOrderOfCode(string $purchase_order_code): ?PurchasingOrder;

    /**
     * @param string $purchase_order_code
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return PurchasingOrder|null
     */
    public function findOrderOfCodeWithPermission(string $purchase_order_code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?PurchasingOrder;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getStatusHistory(int $purchase_order_id): array;

    /**
     * @return array<string, mixed>
     */
    public function getReconciliation(int $purchase_order_id): array;

    /**
     * @return string
     */
    public function generateCode(): string;

    /**
     * @param array $data
     * @return PurchasingOrder|null
     */
    public function createOrder(array $data): ?PurchasingOrder;

    /**
     * @param int $purchase_order_id
     * @param array $update_data
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @return PurchasingOrder|null
     */
    public function updateDraftOrderWithPermission(int $purchase_order_id, array $update_data, ?int $auth_user_id, string $scope, ?int $company_id = null): ?PurchasingOrder;

    /**
     * @param int $purchase_order_id
     * @param int $deleted_by
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @return bool
     */
    public function deleteDraftOrderWithPermission(int $purchase_order_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null): bool;
}
