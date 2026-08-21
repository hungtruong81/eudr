<?php

declare(strict_types=1);

namespace App\Domain\Company;

interface CompanyRepository
{
    /**
     * @param array $params
     * @return Company[]
     */
    public function findAll(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;
    /**
     * @return string
     */
    public function generateCode(): string;
    /**
     * @param int $company_id
     * @return Company|null
     * @throws CompanyNotFoundException
     */
    public function findCompanyOfId(int $company_id): ?Company;

    /**
     * Find by ID with permission (self/own/all).
     */
    public function findCompanyOfIdWithPermission(int $company_id, ?int $auth_user_id, string $scope, ?int $company_id_param = null): ?Company;
    /**
     * @param string $code
     * @return Company|null
     */
    public function findCompanyOfCode(string $code): ?Company;

    /**
     * Find by code with permission (self/own/all).
     */
    public function findCompanyOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id_param = null): ?Company;
    /**
     * @param array $data
     * @return Company|null
     */
    public function createCompany(array $data): ?Company;
    /**
     * @param int $company_id
     * @param array $data_update
     * @return Company
     */
    public function updateCompany(int $company_id, array $data_update): Company;

    /**
     * Update company with permission (self/own/all).
     */
    public function updateCompanyWithPermission(int $company_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id_param = null): Company;
    /**
     * @param int $company_id
     * @param int $deleted_by
     * @return void
     */
    public function deleteCompany(int $company_id, int $deleted_by): void;

    /**
     * Delete (soft) company with permission (self/own/all).
     */
    public function deleteCompanyWithPermission(int $company_id, int $deleted_by, string $scope, ?int $company_id_param = null): void;
}
