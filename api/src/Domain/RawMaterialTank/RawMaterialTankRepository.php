<?php

declare(strict_types=1);

namespace App\Domain\RawMaterialTank;

interface RawMaterialTankRepository
{
    /**
     * @param array $params
     * @return RawMaterialTank[]
     */
    public function findAll(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;
    /**
     * @return string
     */
    public function generateCode(): string;
    /**
     * @param int $raw_material_tank_id
     * @return RawMaterialTank|null
     * @throws RawMaterialTankNotFoundException
     */
    public function findRawMaterialTankOfId(int $raw_material_tank_id): ?RawMaterialTank;
    public function findRawMaterialTankOfIdWithPermission(int $raw_material_tank_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?RawMaterialTank;
    /**
     * @param string $code
     * @return RawMaterialTank|null
     */
    public function findRawMaterialTankOfCode(string $code): ?RawMaterialTank;
    public function findRawMaterialTankOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?RawMaterialTank;
    /**
     * @param array $data
     * @return RawMaterialTank|null
     */
    public function createRawMaterialTank(array $data): ?RawMaterialTank;
    /**
     * @param int $raw_material_tank_id
     * @param array $data_update
     * @return RawMaterialTank
     */
    public function updateRawMaterialTank(int $raw_material_tank_id, array $data_update): RawMaterialTank;
    public function updateRawMaterialTankWithPermission(int $raw_material_tank_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): RawMaterialTank;
    /**
     * @param int $raw_material_tank_id
     * @param int $deleted_by
     * @return void
     */
    public function deleteRawMaterialTank(int $raw_material_tank_id, int $deleted_by): void;
    public function deleteRawMaterialTankWithPermission(int $raw_material_tank_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void;
    /**
     * @param int $raw_material_tank_id
     * @param array $params
     * @return array
     */
    public function getHistoryOfRawMaterialTank(int $raw_material_tank_id, array $params, ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;

}
