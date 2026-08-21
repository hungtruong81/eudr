<?php

declare(strict_types=1);

namespace App\Domain\Sales\Issue;

interface SalesIssueRepository
{
    /**
     * @param array $params
     * @return array{current_page:int,total_pages:int,total_records:int,page_limit:int,records:SalesIssue[]}
     */
    public function findAll(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;

    /**
     * @param string $issue_code
     * @return SalesIssue|null
     */
    public function findIssueOfCode(string $issue_code): ?SalesIssue;

    public function findIssueOfCodeWithPermission(string $issue_code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?SalesIssue;

    public function generateCode(): string;

    /**
     * @param array $data
     * @param array<int,array<string,mixed>> $items
     */
    public function createIssue(array $data, array $items): ?SalesIssue;

    /**
     * @param int $issue_id
     * @param array $data
     * @param array<int,array<string,mixed>> $items
     */
    public function updateIssueWithPermission(int $issue_id, array $data, array $items, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?SalesIssue;

    public function confirmIssueWithPermission(int $issue_id, array $data, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?SalesIssue;

    public function cancelIssueWithPermission(int $issue_id, array $data, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?SalesIssue;

    public function deleteIssueWithPermission(int $issue_id, array $data, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): bool;

    /**
     * @param array<int> $saleOrderItemIds
     * @return array<int,float>
     */
    public function getIssuedTotalsForOrderItems(array $saleOrderItemIds, ?int $excludeIssueId = null): array;
}
