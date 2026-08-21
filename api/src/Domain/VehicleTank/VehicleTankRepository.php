<?php

declare(strict_types=1);

namespace App\Domain\VehicleTank;

interface VehicleTankRepository
{
    /**
     * @param array $params
     * @param int $auth_user_id
     * @param string $scope
     * @param int $company_id
     * @param int|null $company_id_param
     * @return array
     */
    public function findAll(
        array $params,
        int $auth_user_id,
        string $scope,
        int $company_id,
        ?int $company_id_param = null
    ): array;

    /**
     * @param string $code
     * @return VehicleTank|null
     */
    public function findByCode(string $code): ?VehicleTank;

    /**
     * @param string $code
     * @param int $auth_user_id
     * @param string $scope
     * @param int $company_id
     * @param int|null $company_id_param
     * @return VehicleTank|null
     */
    public function findByCodeWithPermission(
        string $code,
        int $auth_user_id,
        string $scope,
        int $company_id,
        ?int $company_id_param = null
    ): ?VehicleTank;

    /**
     * @return string
     */
    public function generateCode(): string;

    /**
     * @param array $data
     * @return VehicleTank|null
     */
    public function create(array $data): ?VehicleTank;

    /**
     * @param int $vehicle_tank_id
     * @param array $data
     * @return VehicleTank|null
     */
    public function updateWithPermission(
        int $vehicle_tank_id,
        array $data,
        int $auth_user_id,
        string $scope,
        int $company_id,
        ?int $company_id_param = null
    ): ?VehicleTank;

    /**
     * @param int $vehicle_tank_id
     * @param int $deleted_by
     * @param string $scope
     * @param int $company_id
     * @param int|null $company_id_param
     * @return bool
     */
    public function deleteWithPermission(
        int $vehicle_tank_id,
        int $deleted_by,
        string $scope,
        int $company_id,
        ?int $company_id_param = null
    ): bool;

    /**
     * @param int $vehicle_tank_id
     * @param float $weight_kg
     * @param int $updated_by
     * @return VehicleTank|null
     */
    public function setCurrentWeight(int $vehicle_tank_id, float $weight_kg, int $updated_by): ?VehicleTank;
}
