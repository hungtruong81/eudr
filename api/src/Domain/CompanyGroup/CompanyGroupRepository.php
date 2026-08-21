<?php

declare(strict_types=1);

namespace App\Domain\CompanyGroup;

interface CompanyGroupRepository
{
    /**
     * @param array $params
     * @return array{current_page:int,total_pages:int,total_records:int,page_limit:int,records:CompanyGroup[]}
     */
    public function findAllByCompany(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;

    /**
     * @param int $group_id
     * @return CompanyGroup|null
     */
    public function findGroupOfId(int $group_id): ?CompanyGroup;

    /**
     * Lấy group theo ID với phân quyền (self/own/all).
     */
    public function findGroupOfIdWithPermission(int $group_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?CompanyGroup;

    /**
     * @param string $group_code
     * @return CompanyGroup|null
     */
    public function findGroupOfCode(string $group_code): ?CompanyGroup;

    /**
     * Lấy group theo code với phân quyền (self/own/all).
     */
    public function findGroupOfCodeWithPermission(string $group_code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?CompanyGroup;

    /**
     * @return string
     */
    public function generateCode(): string;

    /**
     * @param array $data
     * @return CompanyGroup
     */
    public function createGroup(array $data): CompanyGroup;

    /**
     * @param int $group_id
     * @param array $data
     * @return CompanyGroup
     */
    public function updateGroup(int $group_id, array $data): CompanyGroup;

    /**
     * Cập nhật group với phân quyền (self/own/all).
     */
    public function updateGroupWithPermission(int $group_id, array $data, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): CompanyGroup;

    /**
     * @param int $group_id
     * @param int $deleted_by
     */
    public function deleteGroup(int $group_id, int $deleted_by): void;

    /**
     * Xóa (soft) group với phân quyền (self/own/all).
     */
    public function deleteGroupWithPermission(int $group_id, int $deleted_by, string $scope, ?int $company_id = null, ?int $company_id_param = null): void;

    /**
     * @param int   $group_id
     * @param array $permissionNames list of permission name strings
     * @return void
     */
    public function setGroupPermissionsByNames(int $group_id, array $permissionNames): void;

    /**
     * @param int   $group_id
     * @return string[] list of permission names
     */
    public function getGroupPermissions(int $group_id): array;

    /**
     * @param int   $group_id
     * @param int[] $user_ids
     * @param int   $assigned_by
     * @return void
     */
    public function assignMembers(int $group_id, array $user_ids, int $assigned_by): void;

    /**
     * @param int   $group_id
     * @param int[] $user_ids
     * @return void
     */
    public function removeMembers(int $group_id, array $user_ids): void;

    /**
     * @param int $group_id
     * @return int[] user_ids
     */
    public function getGroupMemberIds(int $group_id): array;

    /**
     * Lấy danh sách member của group với phân trang.
     *
     * @param int   $group_id
     * @param array $params
     * @return array{current_page:int,total_pages:int,total_records:int,page_limit:int,records:array<int,array<string,mixed>>}
     */
    public function getGroupMembers(int $group_id, array $params = []): array;

    /**
     * Tạo nhóm công ty mặc định và phân quyền.
     *
     * @param array $groupData
     * @param int $company_id
     * @return CompanyGroup
     */
    public function createCompanyGroupDefault(array $groupData, int $company_id): CompanyGroup;
}
