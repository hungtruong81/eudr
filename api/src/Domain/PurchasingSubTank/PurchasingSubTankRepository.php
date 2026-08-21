<?php

declare(strict_types=1);

namespace App\Domain\PurchasingSubTank;

interface PurchasingSubTankRepository
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
     * @return string
     */
    public function generateCode(): string;

    /**
     * @param int $sub_tank_id
     * @return PurchasingSubTank|null
     * @throws PurchasingSubTankNotFoundException
     */
    public function findPurchasingSubTankOfId(int $sub_tank_id): ?PurchasingSubTank;

    /**
     * @param int $sub_tank_id
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return PurchasingSubTank|null
     */
    public function findPurchasingSubTankOfIdWithPermission(int $sub_tank_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?PurchasingSubTank;

    /**
     * @param string $code
     * @return PurchasingSubTank|null
     */
    public function findPurchasingSubTankOfCode(string $code): ?PurchasingSubTank;

    /**
     * @param string $code
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return PurchasingSubTank|null
     */
    public function findPurchasingSubTankOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?PurchasingSubTank;

    /**
     * @param array $data
     * @return PurchasingSubTank|null
     */
    public function createPurchasingSubTank(array $data): ?PurchasingSubTank;

    /**
     * @param int $sub_tank_id
     * @param array $data_update
     * @return PurchasingSubTank
     */
    public function updatePurchasingSubTank(int $sub_tank_id, array $data_update): PurchasingSubTank;

    /**
     * @param int $sub_tank_id
     * @param array $data_update
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return PurchasingSubTank
     */
    public function updatePurchasingSubTankWithPermission(int $sub_tank_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): PurchasingSubTank;

    /**
     * @param int $sub_tank_id
     * @param int $deleted_by
     * @return void
     */
    public function deletePurchasingSubTank(int $sub_tank_id, int $deleted_by): void;

    /**
     * @param int $sub_tank_id
     * @param int $deleted_by
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return void
     */
    public function deletePurchasingSubTankWithPermission(int $sub_tank_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void;

    /**
     * @param int $sub_tank_id
     * @param int $company_id
     * @param float $weight_kg
     * @param string $rubber_type
     * @param string $event_time
     * @param string|null $notes
     * @param int $user_id
     * @param string $source_type
     * @return PurchasingSubTank
     */
    public function recordStockMovement(
        int $sub_tank_id,
        int $company_id,
        float $weight_kg,
        string $rubber_type,
        string $event_time,
        ?string $notes,
        int $user_id,
        string $source_type = 'supplier_delivery'
    ): PurchasingSubTank;

    /**
     * @param int $sub_tank_id
     * @param int $company_id
     * @param float $weight_delta_kg
     * @param string $rubber_type
     * @param string $event_time
     * @param string $reason
     * @param int $user_id
     * @return PurchasingSubTank
     */
    public function recordStockAdjustment(
        int $sub_tank_id,
        int $company_id,
        float $weight_delta_kg,
        string $rubber_type,
        string $event_time,
        string $reason,
        int $user_id
    ): PurchasingSubTank;

    /**
     * @param int $sub_tank_id
     * @param array $params
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return array
     */
    public function getHistoryOfPurchasingSubTank(int $sub_tank_id, array $params, ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;
}
