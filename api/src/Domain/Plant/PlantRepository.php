<?php

declare(strict_types=1);

namespace App\Domain\Plant;

interface PlantRepository
{
    /**
     * @param array $params
     * @return Plant[]
     */
    public function findAll(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;
    /**
     * @param string $plot_code
     * @param int $user_id
     * @param string $scope
     * @return Land
     * @throws LandNotFoundException
     */
    public function findPlantOfCodeWithPermission(string $plant_code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ? Plant;
    /**
     * @return int
     */
    public function getTotalPlant(): int;
    /**
     * @return array
     */
    public function findAllCropTypes(): array;
    /**
     * @return string
     */
    public function generateCode(): string;
    /**
     * @param int $plot_id
     * @return Plant
     * @throws PlantNotFoundException
     */
    public function findPlantOfId(int $plant_id): ? Plant;

    /**
     * Find by ID with permission (self/own/all).
     */
    public function findPlantOfIdWithPermission(int $plant_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ? Plant;
    /**
     * @param string $plant_code
     * @return Plant
     * @throws PlantNotFoundException
     */
    public function findPlantOfCode(string $plant_code): ? Plant;
    /**
     * @param array $data
     * @return Plant
     */
    public function createPlant(array $data): ? Plant;
    /**
     * @param int $plant_id
     * @param array $data_update
     * @return Plant
     */
    public function updatePlant(int $plant_id, array $data_update): Plant;

    /**
     * Update plant with permission (self/own/all).
     */
    public function updatePlantWithPermission(int $plant_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): Plant;
    /**
     * @param int $plant_id
     * @param int $user_id
     */
    public function deletePlant(int $plant_id, int $user_id): void;

    /**
     * Delete (soft) plant with permission (self/own/all).
     */
    public function deletePlantWithPermission(int $plant_id, int $user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void;

}
