<?php

declare(strict_types=1);

namespace App\Domain\Sales\Customer;

interface SalesCustomerRepository
{
    /**
     * @param array $params
     * @return array{current_page:int,total_pages:int,total_records:int,page_limit:int,records:SalesCustomer[]}
     */
    public function findAll(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;

    /**
     * @param int $customer_id
     * @return SalesCustomer|null
     */
    public function findCustomerOfId(int $customer_id): ?SalesCustomer;

    /**
     * @param int $customer_id
     * @return SalesCustomer|null
     */
    public function findCustomerOfIdWithPermission(int $customer_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?SalesCustomer;
    /**
     * @param string $customer_code
     * @return SalesCustomer|null
     */
    public function findCustomerOfCode(string $customer_code): ?SalesCustomer;

    /**
     * @param string $customer_code
     * @return SalesCustomer|null
     */
    public function findCustomerOfCodeWithPermission(string $customer_code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?SalesCustomer;

    /**
     * @return string
     */
    public function generateCode(): string;

    /**
     * @param array $data
     * @return SalesCustomer
     */
    public function createCustomer(array $data): ?SalesCustomer;

    /**
     * @param int $customer_id
     * @param array $data
     * @return SalesCustomer
     */
    public function updateCustomerWithPermission(int $customer_id, array $data, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): SalesCustomer;

    /**
     * @param int $customer_id
     * @param array $data
     * @return bool
     */
    public function deleteCustomerWithPermission(int $customer_id, array $data, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): bool;
}
