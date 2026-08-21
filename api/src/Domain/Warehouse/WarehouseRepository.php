<?php

declare(strict_types=1);

namespace App\Domain\Warehouse;

interface WarehouseRepository
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
     * @param int $warehouse_id
     * @return Warehouse|null
     */
    public function findWarehouseOfId(int $warehouse_id): ?Warehouse;

    /**
     * @param int $warehouse_id
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return Warehouse|null
     */
    public function findWarehouseOfIdWithPermission(int $warehouse_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Warehouse;

    /**
     * @param string $code
     * @return Warehouse|null
     */
    public function findWarehouseOfCode(string $code): ?Warehouse;

    /**
     * @param string $code
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return Warehouse|null
     */
    public function findWarehouseOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Warehouse;

    /**
     * @param array $data
     * @return Warehouse|null
     */
    public function createWarehouse(array $data): ?Warehouse;

    /**
     * @param int $warehouse_id
     * @param array $data_update
     * @return Warehouse
     */
    public function updateWarehouse(int $warehouse_id, array $data_update): Warehouse;

    /**
     * @param int $warehouse_id
     * @param array $data_update
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return Warehouse
     */
    public function updateWarehouseWithPermission(int $warehouse_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): Warehouse;

    /**
     * @param int $warehouse_id
     * @param int $deleted_by
     * @return void
     */
    public function deleteWarehouse(int $warehouse_id, int $deleted_by): void;
    /**
     * @param int $warehouse_id
     * @param int $deleted_by
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return void
     */
    public function deleteWarehouseWithPermission(int $warehouse_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void;
}
