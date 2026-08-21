<?php

declare(strict_types=1);

namespace App\Domain\ProductionPressing;

interface ProductionPressingRepository
{
    /**
     * List pressing runs with filters/pagination; includes quality details and bale counts per run.
     */
    public function findAllPressingRuns(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;

    /**
     * Find one drying run by id with scope permission.
     */
    public function findDryingRunOfIdWithPermission(int $drying_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array;

    /**
     * Find one pressing run by id with scope permission.
     */
    public function findPressingRunOfIdWithPermission(int $pressing_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array;

    /**
     * Get pressing run detail including quality details and output bales.
     */
    public function getPressingRunDetailWithPermission(int $pressing_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array;

    /**
     * Create pressing run from a drying run.
     */
    public function createPressingRunFromDrying(array $data): ?array;

    /**
     * Update pressing quality details, create output bales, and complete run.
     */
    public function updatePressingRunQualityDetails(array $data): ?array;
}
