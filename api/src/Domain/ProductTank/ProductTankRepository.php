<?php

declare(strict_types=1);

namespace App\Domain\ProductTank;

interface ProductTankRepository
{
    /**
     * @param array $params
     * @return ProductTank[]
     */
    public function findAll(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;
    /**
     * @return string
     */
    public function generateCode(): string;
    /**
     * @param int $product_tank_id
     * @return ProductTank|null
     * @throws ProductTankNotFoundException
     */
    public function findProductTankOfId(int $product_tank_id): ?ProductTank;
    public function findProductTankOfIdWithPermission(int $product_tank_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductTank;
    /**
     * @param string $code
     * @return ProductTank|null
     */
    public function findProductTankOfCode(string $code): ?ProductTank;
    public function findProductTankOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductTank;
    /**
     * @param array $data
     * @return ProductTank|null
     */
    public function createProductTank(array $data): ?ProductTank;
    /**
     * @param int $product_tank_id
     * @param array $data_update
     * @return ProductTank
     */
    public function updateProductTank(int $product_tank_id, array $data_update): ProductTank;
    public function updateProductTankWithPermission(int $product_tank_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ProductTank;
    /**
     * @param int $product_tank_id
     * @param int $deleted_by
     * @return void
     */
    public function deleteProductTank(int $product_tank_id, int $deleted_by): void;
    public function deleteProductTankWithPermission(int $product_tank_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void;
    /**
     * @param int $product_tank_id
     * @param array $params
     * @return array
     */
    public function getHistoryOfProductTank(int $product_tank_id, array $params, ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;

}
