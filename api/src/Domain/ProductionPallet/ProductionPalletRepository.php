<?php

declare(strict_types=1);

namespace App\Domain\ProductionPallet;

interface ProductionPalletRepository
{
    /**
     * List pallet runs with filters/pagination.
     */
    public function findAllPalletRuns(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;

    /**
     * Find one pressing run by id with scope permission.
     */
    public function findPressingRunOfIdWithPermission(int $pressing_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array;

    /**
     * Find one pallet run by id with scope permission.
     */
    public function findPalletRunOfIdWithPermission(int $pallet_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array;

    /**
     * Get pallet run detail including pallets and pallet items.
     */
    public function getPalletRunDetailWithPermission(int $pallet_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array;

    /**
     * List pallets after close step with filters/pagination.
     */
    public function findAllPallets(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;

    /**
     * Get pallet detail including pallet items after close step.
     */
    public function getPalletDetailByCodeWithPermission(string $pallet_code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array;

    /**
     * List bales with filters/pagination.
     */
    public function findAllBales(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;

    /**
     * Get bale detail with pallet and run traceability.
     */
    public function getBaleDetailWithPermission(int $bale_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array;

    /**
     * Create pallet run from pressing run.
     */
    public function createPalletRunFromPressing(array $data): ?array;

    /**
     * Create one pallet in a pallet run and assign bales.
     */
    public function createPalletWithBales(array $data): ?array;

    /**
     * Update pallet item by replacing bale_id.
     */
    public function updatePalletItem(array $data): ?array;

    /**
     * Soft delete pallet item and recalculate pallet bale_count.
     */
    public function deletePalletItem(array $data): ?array;

    /**
     * Complete pallet run and update output pallet count.
     */
    public function completePalletRun(array $data): ?array;
}
