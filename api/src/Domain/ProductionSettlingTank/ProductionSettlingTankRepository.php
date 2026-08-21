<?php

declare(strict_types=1);

namespace App\Domain\ProductionSettlingTank;

interface ProductionSettlingTankRepository
{
    /**
     * Find all production settling tanks with optional filters and permissions.
     *
     * @param array $params
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return ProductionSettlingTank[]
     */
    public function findAll(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;

    /**
    * Generate a unique code for a production settling tank.
    *
    * @return string
    */
    public function generateCode(): string;

    /**
     * Find a production settling tank by its ID.
     *
     * @param int $settling_tank_id
     * @return ProductionSettlingTank|null
     * @throws ProductionSettlingTankNotFoundException
     */
    public function findProductionSettlingTankOfId(int $settling_tank_id): ?ProductionSettlingTank;

    /**
     * Find a production settling tank by its ID with permission checks.
     *
     * @param int $settling_tank_id
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return ProductionSettlingTank|null
     */
    public function findProductionSettlingTankOfIdWithPermission(int $settling_tank_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductionSettlingTank;

    /**
     * Find a production settling tank by its code.
     *
     * @param string $code
     * @return ProductionSettlingTank|null
     */
    public function findProductionSettlingTankOfCode(string $code): ?ProductionSettlingTank;

    /**
     * Find a production settling tank by its code with permission checks.
     *
     * @param string $code
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return ProductionSettlingTank|null
     */
    public function findProductionSettlingTankOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductionSettlingTank;

    /**
     * Create a new production settling tank with the given data.
     *
     * @param array $data
     * @return ProductionSettlingTank|null
     */
    public function createProductionSettlingTank(array $data): ?ProductionSettlingTank;

    /**
     * Update a production settling tank with the given data.
     *
     * @param int $settling_tank_id
     * @param array $data_update
     * @return ProductionSettlingTank
     * @throws ProductionSettlingTankNotFoundException
     */
    public function updateProductionSettlingTank(int $settling_tank_id, array $data_update): ProductionSettlingTank;

    /**
     * Update a production settling tank with the given data and permission checks.
     *
     * @param int $settling_tank_id
     * @param array $data_update
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return ProductionSettlingTank
     */
    public function updateProductionSettlingTankWithPermission(int $settling_tank_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ProductionSettlingTank;

    /**
     * Delete a production settling tank.
     *
     * @param int $settling_tank_id
     * @param int $deleted_by
     * @return void
     */
    public function deleteProductionSettlingTank(int $settling_tank_id, int $deleted_by): void;

    /**
     * Delete a production settling tank with permission checks.
     *
     * @param int $settling_tank_id
     * @param int $deleted_by
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return void
     */
    public function deleteProductionSettlingTankWithPermission(int $settling_tank_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void;
}
