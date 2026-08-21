<?php

declare(strict_types=1);

namespace App\Domain\ProductionCuttingMachine;

interface ProductionCuttingMachineRepository
{
    /**
     * @param array $params
     * @return ProductionCuttingMachine[]
     */
    public function findAll(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;

    /**
     * List cutting runs with filters and include quality outputs when available.
     *
     * @param array $params
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return array
     */
    public function findAllCuttingRuns(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;

    /**
     * @return string
     */
    public function generateCode(): string;

    /**
     * @param int $cutting_machine_id
     * @return ProductionCuttingMachine|null
     * @throws ProductionCuttingMachineNotFoundException
     */
    public function findProductionCuttingMachineOfId(int $cutting_machine_id): ?ProductionCuttingMachine;

    public function findProductionCuttingMachineOfIdWithPermission(int $cutting_machine_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductionCuttingMachine;

    /**
     * @param string $code
     * @return ProductionCuttingMachine|null
     */
    public function findProductionCuttingMachineOfCode(string $code): ?ProductionCuttingMachine;

    public function findProductionCuttingMachineOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductionCuttingMachine;

    /**
     * @param array $data
     * @return ProductionCuttingMachine|null
     */
    public function createProductionCuttingMachine(array $data): ?ProductionCuttingMachine;

    /**
     * @param int $cutting_machine_id
     * @param array $data_update
     * @return ProductionCuttingMachine
     */
    public function updateProductionCuttingMachine(int $cutting_machine_id, array $data_update): ProductionCuttingMachine;

    public function updateProductionCuttingMachineWithPermission(int $cutting_machine_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ProductionCuttingMachine;

    /**
     * @param int $cutting_machine_id
     * @param int $deleted_by
     * @return void
     */
    public function deleteProductionCuttingMachine(int $cutting_machine_id, int $deleted_by): void;

    public function deleteProductionCuttingMachineWithPermission(int $cutting_machine_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void;

    /**
     * Find one cutting run by id with scope permission.
     *
     * @param int $cutting_run_id
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return array|null
     */
    public function findCuttingRunOfIdWithPermission(int $cutting_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array;

    /**
     * Get cutting run detail including quality outputs and related entities.
     *
     * @param int $cutting_run_id
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return array|null
     */
    public function getCuttingRunDetailWithPermission(int $cutting_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array;

    /**
     * Update per-quality outputs for a cutting run and mark run completed.
     *
     * @param array $data
     * @return array|null
     */
    public function updateCuttingRunQualityOutputs(array $data): ?array;

    /**
     * Create rolling run from a cutting run.
     *
     * @param array $data
     * @return array|null
     */
    public function createRollingRunFromCutting(array $data): ?array;
}
