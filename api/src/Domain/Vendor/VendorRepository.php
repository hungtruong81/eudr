<?php

declare(strict_types=1);

namespace App\Domain\Vendor;

interface VendorRepository
{
    /**
     * @param array $params
     * @return array
     */
    public function findAll(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;

    /**
     * @return string
     */
    public function generateCode(): string;

    /**
     * @param int $vendor_id
     * @return Vendor|null
     * @throws VendorNotFoundException
     */
    public function findVendorOfId(int $vendor_id): ?Vendor;

    /**
     * @param int $vendor_id
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return Vendor|null
     */
    public function findVendorOfIdWithPermission(int $vendor_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Vendor;

    /**
     * @param string $code
     * @return Vendor|null
     */
    public function findVendorOfCode(string $code): ?Vendor;

    /**
     * @param string $code
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return Vendor|null
     */
    public function findVendorOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Vendor;

    /**
     * @param string $field
     * @param string $value
     * @param int|null $exclude_vendor_id
     * @return bool
     */
    public function identifierExists(string $field, string $value, ?int $exclude_vendor_id = null): bool;

    /**
     * @param array $data
     * @return Vendor|null
     */
    public function createVendor(array $data): ?Vendor;

    /**
     * @param int $vendor_id
     * @param array $data_update
     * @return Vendor
     */
    public function updateVendor(int $vendor_id, array $data_update): Vendor;

    /**
     * @param int $vendor_id
     * @param array $data_update
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return Vendor
     */
    public function updateVendorWithPermission(int $vendor_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): Vendor;

    /**
     * @param int $vendor_id
     * @param int $deleted_by
     * @return void
     */
    public function deleteVendor(int $vendor_id, int $deleted_by): void;

    /**
     * @param int $vendor_id
     * @param int $deleted_by
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return void
     */
    public function deleteVendorWithPermission(int $vendor_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void;
}
