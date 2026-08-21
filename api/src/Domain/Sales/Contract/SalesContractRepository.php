<?php

declare(strict_types=1);

namespace App\Domain\Sales\Contract;

interface SalesContractRepository
{
    /**
     * @param array $params
     * @return array{current_page:int,total_pages:int,total_records:int,page_limit:int,records:SalesContract[]}
     */
    public function findAll(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;

    /**
     * @param int $contract_id
     * @return SalesContract|null
     */
    public function findContractOfId(int $contract_id): ?SalesContract;

    /**
     * @param int $contract_id
     * @return SalesContract|null
     */
    public function findContractOfIdWithPermission(int $contract_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?SalesContract;

    /**
     * @param string $contract_code
     * @return SalesContract|null
     */
    public function findContractOfCode(string $contract_code): ?SalesContract;

    /**
     * @param string $contract_code
     * @return SalesContract|null
     */
    public function findContractOfCodeWithPermission(string $contract_code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?SalesContract;

    /**
     * @return string
     */
    public function generateCode(): string;

    /**
     * @param array $data
     * @param array<int,array<string,mixed>> $items
     */
    public function createContract(array $data, array $items): ?SalesContract;

    /**
     * @param int $contract_id
     * @param array $data
     * @param array<int,array<string,mixed>> $items
     */
    public function updateContractWithPermission(int $contract_id, array $data, array $items, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?SalesContract;
}
