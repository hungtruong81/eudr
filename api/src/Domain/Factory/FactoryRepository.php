<?php

declare(strict_types=1);

namespace App\Domain\Factory;

interface FactoryRepository
{
    /**
     * @param array $params
     * @return Factory[]
     */
    public function findAll(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;
    /**
     * @return string
     */
    public function generateCode(): string;
    /**
     * @param int $factory_id
     * @return Factory|null
     * @throws FactoryNotFoundException
     */
    public function findFactoryOfId(int $factory_id): ?Factory;
    public function findFactoryOfIdWithPermission(int $factory_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Factory;
    /**
     * @param string $code
     * @return Factory|null
     */
    public function findFactoryOfCode(string $code): ?Factory;
    public function findFactoryOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Factory;
    /**
     * @param array $data
     * @return Factory|null
     */
    public function createFactory(array $data): ?Factory;
    /**
     * @param int $factory_id
     * @param array $data_update
     * @return Factory
     */
    public function updateFactory(int $factory_id, array $data_update): Factory;
    public function updateFactoryWithPermission(int $factory_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): Factory;
    /**
     * @param int $factory_id
     * @param int $deleted_by
     * @return void
     */
    public function deleteFactory(int $factory_id, int $deleted_by): void;
    public function deleteFactoryWithPermission(int $factory_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void;

}
