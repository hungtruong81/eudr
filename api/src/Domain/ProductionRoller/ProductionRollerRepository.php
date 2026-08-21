<?php

declare(strict_types=1);

namespace App\Domain\ProductionRoller;

interface ProductionRollerRepository
{
    /**
     * @param array $params
     * @return ProductionRoller[]
     */
    public function findAll(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;

    /**
     * List rolling runs with filters/pagination; includes quality details per run.
     */
    public function findAllRollingRuns(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;

    /**
     * @return string
     */
    public function generateCode(): string;

    /**
     * @param int $roller_id
     * @return ProductionRoller|null
     * @throws ProductionRollerNotFoundException
     */
    public function findProductionRollerOfId(int $roller_id): ?ProductionRoller;

    public function findProductionRollerOfIdWithPermission(int $roller_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductionRoller;

    /**
     * @param string $code
     * @return ProductionRoller|null
     */
    public function findProductionRollerOfCode(string $code): ?ProductionRoller;

    public function findProductionRollerOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductionRoller;

    /**
     * @param array $data
     * @return ProductionRoller|null
     */
    public function createProductionRoller(array $data): ?ProductionRoller;

    /**
     * @param int $roller_id
     * @param array $data_update
     * @return ProductionRoller
     */
    public function updateProductionRoller(int $roller_id, array $data_update): ProductionRoller;

    public function updateProductionRollerWithPermission(int $roller_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ProductionRoller;

    /**
     * @param int $roller_id
     * @param int $deleted_by
     * @return void
     */
    public function deleteProductionRoller(int $roller_id, int $deleted_by): void;

    public function deleteProductionRollerWithPermission(int $roller_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void;

    /**
     * Find one rolling run by id with scope permission.
     *
     * @param int $rolling_run_id
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return array|null
     */
    public function findRollingRunOfIdWithPermission(int $rolling_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array;

    /**
     * Get rolling run detail including quality details and related entities.
     *
     * @param int $rolling_run_id
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return array|null
     */
    public function getRollingRunDetailWithPermission(int $rolling_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array;

    /**
     * Update per-quality details for a rolling run and mark run completed.
     *
     * @param array $data
     * @return array|null
     */
    public function updateRollingRunQualityDetails(array $data): ?array;
}
