<?php

declare(strict_types=1);

namespace App\Domain\RawMaterialRelease;

interface RawMaterialReleaseRepository
{
    /**
     * @param array $params
     * @return RawMaterialRelease[]
     */
    public function findAll(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;
    /**
     * @return string
     */
    public function generateCode(): string;
    /**
     * @param int $raw_material_release_id
     * @return RawMaterialRelease|null
     * @throws RawMaterialReleaseNotFoundException
     */
    public function findRawMaterialReleaseOfId(int $raw_material_release_id): ?RawMaterialRelease;
    /**
     * @param int $raw_material_release_id
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return RawMaterialRelease|null
     */
    public function findRawMaterialReleaseOfIdWithPermission(int $raw_material_release_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?RawMaterialRelease;
    /**
     * @param string $code
     * @return RawMaterialRelease|null
     */
    public function findRawMaterialReleaseOfCode(string $code): ?RawMaterialRelease;
    /**
     * @param string $code
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return RawMaterialRelease|null
     */
    public function findRawMaterialReleaseOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?RawMaterialRelease;
    /**
     * @param array $data
     * @return RawMaterialRelease|null
     */
    public function createRawMaterialRelease(array $data): ?RawMaterialRelease;
    /**
     * @param int $raw_material_release_id
     * @param array $data_update
     * @return RawMaterialRelease
     */
    public function updateRawMaterialRelease(int $raw_material_release_id, array $data_update): ?RawMaterialRelease;
    /**
     * @param int $raw_material_release_id
     * @param int $deleted_by
     * @return void
     */
    public function deleteRawMaterialRelease(int $raw_material_release_id, int $deleted_by): void;
}
