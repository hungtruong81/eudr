<?php

declare(strict_types=1);

namespace App\Domain\ProductionOven;

interface ProductionOvenRepository
{
    /**
     * @param array $params
     * @return ProductionOven[]
     */
    public function findAll(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;

    /**
     * List drying runs with filters/pagination; includes quality details per run.
     */
    public function findAllDryingRuns(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;

    /**
     * @return string
     */
    public function generateCode(): string;

    /**
     * @param int $oven_id
     * @return ProductionOven|null
     * @throws ProductionOvenNotFoundException
     */
    public function findProductionOvenOfId(int $oven_id): ?ProductionOven;

    public function findProductionOvenOfIdWithPermission(int $oven_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductionOven;

    /**
     * @param string $code
     * @return ProductionOven|null
     */
    public function findProductionOvenOfCode(string $code): ?ProductionOven;

    public function findProductionOvenOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductionOven;

    /**
     * @param array $data
     * @return ProductionOven|null
     */
    public function createProductionOven(array $data): ?ProductionOven;

    /**
     * @param int $oven_id
     * @param array $data_update
     * @return ProductionOven
     */
    public function updateProductionOven(int $oven_id, array $data_update): ProductionOven;

    public function updateProductionOvenWithPermission(int $oven_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ProductionOven;

    /**
     * @param int $oven_id
     * @param int $deleted_by
     * @return void
     */
    public function deleteProductionOven(int $oven_id, int $deleted_by): void;

    public function deleteProductionOvenWithPermission(int $oven_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void;

    /**
     * Find one hanging run by id with scope permission.
     */
    public function findHangingRunOfIdWithPermission(int $hanging_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array;

    /**
     * Find one drying run by id with scope permission.
     */
    public function findDryingRunOfIdWithPermission(int $drying_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array;

    /**
     * Get drying run detail including quality details.
     */
    public function getDryingRunDetailWithPermission(int $drying_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array;

    /**
     * Create drying run from a hanging run.
     */
    public function createDryingRunFromHanging(array $data): ?array;

    /**
     * Update per-quality outputs for a drying run and mark run completed.
     */
    public function updateDryingRunQualityDetails(array $data): ?array;
}
