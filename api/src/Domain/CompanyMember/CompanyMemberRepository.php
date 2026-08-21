<?php

declare(strict_types=1);

namespace App\Domain\CompanyMember;

use App\Domain\User\User;

interface CompanyMemberRepository
{
    /**
     * Danh sách thành viên trong company hiện tại (hoặc company truyền vào),
     * có phân trang + search.
     *
     * @param array{
     *   page?: int,
     *   page_limit?: int,
     *   search?: string|null,
    *   status?: string|null
     * } $params
    * @param int|null $auth_user_id
    * @param string $scope self|own|all
    * @param int|null $company_id
     *
     * @return array{
     *   current_page:int,
     *   total_pages:int,
     *   total_records:int,
     *   page_limit:int,
     *   records: User[]
     * }
     */
    public function findAllMembers(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null): array;

    /**
     * Lấy 1 member theo user_id, scope theo company.
     * Nếu $company_id = null thì implementation nên dùng company hiện tại
     * từ CurrentUserContext.
     */
    public function findMemberOfId(int $user_id, ?int $company_id = null): ?User;

    /**
     * Lấy 1 member theo user_code, scope theo company.
     * Nếu $company_id = null thì implementation nên dùng company hiện tại
     * từ CurrentUserContext.
     */
    public function findMemberOfCode(string $user_code, ?int $company_id = null): ?User;

    /**
     * Lấy 1 member theo user_id với phân quyền (self/own/all).
     */
    public function findMemberOfIdWithPermission(int $user_id, ?int $auth_user_id, string $scope, ?int $company_id = null): ?User;

    /**
     * Lấy 1 member theo user_code với phân quyền (self/own/all).
     */
    public function findMemberOfCodeWithPermission(string $user_code, ?int $auth_user_id, string $scope, ?int $company_id = null): ?User;

    /**
     * Tạo mới member trong company hiện tại.
     *
     * @param array $data
     * @return User
     */    
    public function createMember(array $data): User;

    /**
     * Cập nhật member với phân quyền (self/own/all).
     */
    public function updateMemberWithPermission(int $user_id, array $data, ?int $auth_user_id, string $scope, ?int $company_id = null): User;

    /**
     * Xoá (soft-delete) member với phân quyền (self/own/all).
     */
    public function deleteMemberWithPermission(int $user_id, ?int $auth_user_id, string $scope, ?int $company_id = null): void;
}
