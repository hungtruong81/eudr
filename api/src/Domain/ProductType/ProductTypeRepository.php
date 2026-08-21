<?php

declare(strict_types=1);

namespace App\Domain\ProductType;

interface ProductTypeRepository
{
    /**
     * @param array $params
     * @return ProductType[]
     */
    public function findAll(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;
    /**
     * @return string
     */
    public function generateCode(): string;
    /**
     * @param int $product_type_id
     * @return ProductType|null
     * @throws ProductTypeNotFoundException
     */
    public function findProductTypeOfId(int $product_type_id): ?ProductType;
    public function findProductTypeOfIdWithPermission(int $product_type_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductType;
    /**
     * @param string $code
     * @return ProductType|null
     */
    public function findProductTypeOfCode(string $code): ?ProductType;
    public function findProductTypeOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductType;
    /**
     * @param array $data
     * @return ProductType|null
     */
    public function createProductType(array $data): ?ProductType;
    /**
     * @param int $product_type_id
     * @param array $data_update
     * @return ProductType
     */
    public function updateProductType(int $product_type_id, array $data_update): ProductType;
    public function updateProductTypeWithPermission(int $product_type_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ProductType;
    /**
     * @param int $product_type_id
     * @param int $deleted_by
     * @return void
     */
    public function deleteProductType(int $product_type_id, int $deleted_by): void;
    public function deleteProductTypeWithPermission(int $product_type_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void;
}
