<?php
declare(strict_types=1);

namespace App\Domain\User;

interface UserRepository
{
    /**
     * @return User[]
     */
    public function findAll(): array;

    /**
     * @param int $user_id
     * @return User
     * @throws UserNotFoundException
     */
    public function findUserOfId(int $user_id): ?User;
    /**
     * @param string $username
     * @return User
     * @throws UserNotFoundException
     */
    public function findUserOfUsername(string $username): ?User;
    /**
     * @param string $code
     * @return User
     * @throws UserNotFoundException
     */
    public function findUserOfCode(string $code): ?User;
    /**
     * @param string $email
     * @return User
     * @throws UserNotFoundException
     */
    public function findUserOfEmail(string $email): ?User;
    /**
     * @param string $email
     * @return User
     * @throws UserNotFoundException
     */
    public function findUserOfPhone(string $phone): ?User;
    /**
     * @param array $data_user
     * @param int $user_id
     * @return User
     */
    public function createUser(array $data_user): ?User;

    /**
     * @param array $data_user
     * @param int $user_id
     * @return User
     */
    public function updateUser(int $user_id, array $data_user): User;
    /**
     * @return string
     */
    public function generateCode();
    /**
     * @param int $user_id
     * @param int $deleted_by
     */
    public function deleteUser(int $user_id, int $deleted_by): void;
    /**
     * @return string
     */
    public function generateUserCode();
    /**
     * @param int $user_id
     * @return User
     */
    public function updateUserLastLogin(int $user_id): User;
    /**
     * @param int $user_id
     * @return Permissions[]
     */
    public function getUserPermissions(int $user_id): array;
    /**
     * @param int $user_id
     * @param string $permission_name
     * @return bool
     */
    public function userHasPermission(int $user_id, string $permission_name): bool;
    /**
     * @param int $user_id
     * @param string $module
     * @param string $action
     * @return bool
     */
    public function getCURDPermissionStatus(int $user_id, string $module, string $action): string;
    /**
     * @param int $user_id
     * @param array $permissions
     * @param bool $use_permission_names
     * @return User
     */
    public function updateUserPermission(int $user_id, array $permissions, bool $use_permission_names = false): ?User;
    /**
     * @return Permissions[]
     */
    public function getAllPermissions(): array;
    /**
     * @param string $role_name
     * @return array
     */
    public function getRolePermissions(string $role_name): array;
    /**
     * @param int $user_id
     * @param string $role_name
     * @return void
     */
    public function assignRoleToUser(int $user_id, string $role_name): void;
    /**
     * @return array
     */
    public function getAllRoles(): array;
    /**
     * @param string $role_name
     * @return int
     */
    public function getRoleIdByName(string $role_name): ?int;
    /**
     * @param int $user_id
     * @return string|null
     */
    public function getUserRole(int $user_id): ?string;
    /**
     * Lấy tất cả roles của user (hỗ trợ multi-role)
     *
     * @param int $user_id
     * @return array
     */
    public function getUserRoles(int $user_id): array;
    /**
     * Xóa một role khỏi user
     *
     * @param int $user_id
     * @param string $role_name
     * @return void
     */
    public function removeRoleFromUser(int $user_id, string $role_name): void;

    /**
     * Xóa tất cả roles khỏi user
     *
     * @param int $user_id
     * @return void
     */
    public function removeAllRolesFromUser(int $user_id): void;

    /**
     * Gán danh sách permissions cho một role (replace toàn bộ).
     *
     * @param int $role_id
     * @param array $permission_ids  Mảng permission_id
     * @return bool
     */
    public function updateRolePermissions(int $role_id, array $permission_ids): bool;

    /**
     * Chuyển đổi danh sách tên permission sang danh sách permission_id.
     *
     * @param array $permissionNames
     * @return array
     */
    public function mapPermissionNamesToIds(array $permissionNames): array;

    /**
     * Lấy danh sách permissions của một role theo role_id.
     *
     * @param int $role_id
     * @return array
     */
    public function getRolePermissionsByRoleId(int $role_id): array;

    /**
     * Tìm role theo ID.
     *
     * @param int $role_id
     * @return array|null
     */
    public function findRoleById(int $role_id): ?array;

    /**
     * @param array $params
     * @return array
     */
    public function findAllWorkerUsers(array $params): array;
    /**
     * @param string $user_code
     * @param int $user_id
     * @param string $permission_status
     * @return User
     */
    public function findWorkerUserOfCodeWithPermission(string $user_code, int $user_id, string $permission_status): ?User;
    /**
     * @param string $phone
     * @param string $purpose
     * @return array
     */
    public function requestOtp(string $phone, string $purpose = 'register'): array;
    /**
     * @param int $otp_request_id
     * @param string $phone
     * @param string $otp_code
     * @param string $purpose
     * @return bool
     */
    public function verifyOtp(int $otp_request_id, string $phone, string $otp_code, string $purpose): bool;
    /**
     * @param int $otp_request_id
     * @param string $phone
     * @param string $purpose
     * @return array|null
     */
    public function findOtpRequest(int $otp_request_id, string $phone, string $purpose): ?array;

}
