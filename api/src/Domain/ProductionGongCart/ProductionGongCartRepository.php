<?php

declare(strict_types=1);

namespace App\Domain\ProductionGongCart;

interface ProductionGongCartRepository
{
    /**
     * @param array $params
     * @return ProductionGongCart[]
     */
    public function findAll(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;

    /**
     * List hanging runs with filters/pagination; includes quality details per run.
     */
    public function findAllHangingRuns(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;

    /**
     * @return string
     */
    public function generateCode(): string;

    /**
     * @param int $gong_cart_id
     * @return ProductionGongCart|null
     * @throws ProductionGongCartNotFoundException
     */
    public function findProductionGongCartOfId(int $gong_cart_id): ?ProductionGongCart;

    public function findProductionGongCartOfIdWithPermission(int $gong_cart_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductionGongCart;

    /**
     * @param string $code
     * @return ProductionGongCart|null
     */
    public function findProductionGongCartOfCode(string $code): ?ProductionGongCart;

    public function findProductionGongCartOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductionGongCart;

    /**
     * @param array $data
     * @return ProductionGongCart|null
     */
    public function createProductionGongCart(array $data): ?ProductionGongCart;

    /**
     * @param int $gong_cart_id
     * @param array $data_update
     * @return ProductionGongCart
     */
    public function updateProductionGongCart(int $gong_cart_id, array $data_update): ProductionGongCart;

    public function updateProductionGongCartWithPermission(int $gong_cart_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ProductionGongCart;

    /**
     * @param int $gong_cart_id
     * @param int $deleted_by
     * @return void
     */
    public function deleteProductionGongCart(int $gong_cart_id, int $deleted_by): void;

    public function deleteProductionGongCartWithPermission(int $gong_cart_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void;

    /**
     * Find one rolling run by id with scope permission.
     */
    public function findRollingRunOfIdWithPermission(int $rolling_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array;

    /**
     * Find one hanging run by id with scope permission.
     */
    public function findHangingRunOfIdWithPermission(int $hanging_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array;

    /**
     * Get hanging run detail including quality details, poles and assignments.
     */
    public function getHangingRunDetailWithPermission(int $hanging_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array;

    /**
     * Create/update hanging run from rolling run and assign poles by quality.
     */
    public function assignRollingSheetsToHangingPoles(array $data): ?array;

    /**
     * Record final output sheet count by quality and complete hanging run.
     */
    public function completeHangingRunQualityDetails(array $data): ?array;
}
