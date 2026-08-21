<?php

declare(strict_types=1);

namespace App\Domain\Vehicle;

interface VehicleRepository
{
    /**
     * @param array $params
     * @return Vehicle[]
     */
    public function findAll(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;
    /**
     * @return string
     */
    public function generateCode(): string;
    /**
     * @param int $vehicle_id
     * @return Vehicle
     * @throws VehicleNotFoundException
     */
    public function findVehicleOfId(int $vehicle_id): ?Vehicle;

    /**
     * Find by ID with permission (self/own/all).
     */
    public function findVehicleOfIdWithPermission(int $vehicle_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Vehicle;
    /**
     * @param string $code
     * @return Vehicle|null
     */
    public function findVehicleOfCode(string $code): ?Vehicle;

    /**
     * Find by code with permission (self/own/all).
     */
    public function findVehicleOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Vehicle;
    /**
     * @param string $license_plate
     * @return Vehicle|null
     */
    public function findVehicleOfLicensePlate(string $license_plate): ?Vehicle;

    /**
     * Find by license plate with permission (self/own/all).
     */
    public function findVehicleOfLicensePlateWithPermission(string $license_plate, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Vehicle;
    /**
     * @param array $data
     * @return Vehicle
     */
    public function createVehicle(array $data): ?Vehicle;
    /**
     * @param int $vehicle_id
     * @param array $data_update
     * @return Vehicle
     */
    public function updateVehicle(int $vehicle_id, array $data_update): Vehicle;

    /**
     * Update vehicle with permission (self/own/all).
     */
    public function updateVehicleWithPermission(int $vehicle_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): Vehicle;
    /**
     * @param int $vehicle_id
     * @param int $deleted_by
     * @return void
     */
    public function deleteVehicle(int $vehicle_id, int $deleted_by): void;

    /**
     * Delete (soft) vehicle with permission (self/own/all).
     */
    public function deleteVehicleWithPermission(int $vehicle_id, int $deleted_by, string $scope, ?int $company_id = null, ?int $company_id_param = null): void;
    /**
     * @return array
     */
    public function findVehicleBrands(): array;

}
