<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\User;

use App\Domain\User\User;
use App\Domain\User\UserNotFoundException;
use App\Domain\User\UserRepository;
use App\Application\Utility\Utils;
use App\Application\Utility\CurrentUserContext;

class InDatabaseUserRepository implements UserRepository
{
    /**
     * @var MysqliDb
     */
    private $db;

    /**
     * @var CurrentUserContext
     */
    private $currentUser;

    /*  OTP Settings */
    private $OTP_EXPIRY_MINUTES = 5; // OTP hết hạn sau 5 phút
    private $MAX_ATTEMPTS = 5; // Số lần nhập sai tối đa
    private $MAX_RESEND_PER_HOUR = 3; // Số lần gửi lại OTP tối đa trong 1 giờ

    /**
     * InDatabaseUserRepository constructor.
     *
     * @param MysqliDb $db
     * @param CurrentUserContext $currentUserContext
     */
    public function __construct(\MysqliDb $db, CurrentUserContext $currentUserContext)
    {
        $this->db = $db;
        $this->currentUser = $currentUserContext;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll($params = []): array
    {
        $simple = false;
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $is_approved = $params['is_approved'];
        $register_type = $params['register_type'] ?? '';
        $search = $params['search'] ?? '';
        $permission_status = $params['permission_status'] ?? '';
        $user_id = $params['user_id'] ?? 0;

        // Count total records
        $total_records = 0;
        $this->db->where("u.deleted_by", 0);
        if ($is_approved >= 0) {
            $this->db->where("u.is_approved", $is_approved);
        }
        if (!empty($register_type)) {
            // Multi-role: filter by eudr_user_roles, fallback to register_type for unmapped types (e.g. 'worker')
            $roleFilterSQL = Utils::buildRoleFilterSQL('u.user_id', $register_type);
            if (!empty($roleFilterSQL)) {
                $this->db->where($roleFilterSQL);
            } else {
                $this->db->where("u.register_type", $register_type);
            }
        }
        if (!empty($search)) {
            $this->db->where("(full_name LIKE ? OR email LIKE ? OR phone LIKE ?)", 
            ["%$search%", "%$search%", "%$search%"]);
        }
        $total_records = $this->db->getValue("eudr_users u", "count(*)");

        // Set pagination
        if (!empty($page_limit)) {
            $this->db->pageLimit = intval($page_limit);
        }
        if ($is_approved >= 0) {
            $this->db->where("u.is_approved", $is_approved);
        }
        if (!empty($register_type)) {
            // Multi-role: filter by eudr_user_roles, fallback to register_type for unmapped types (e.g. 'worker')
            $roleFilterSQL = Utils::buildRoleFilterSQL('u.user_id', $register_type);
            if (!empty($roleFilterSQL)) {
                $this->db->where($roleFilterSQL);
            } else {
                $this->db->where("u.register_type", $register_type);
            }
        }
        if (!empty($search)) {
            $this->db->where("(full_name LIKE ? OR email LIKE ? OR phone LIKE ?)", 
            ["%$search%", "%$search%", "%$search%"]);
        }

        $cols = null;
        $this->db->where("u.deleted_by", 0);
        $this->db->orderBy("u.user_id", "DESC");
        $records = $this->db->arraybuilder()->paginate("eudr_users u", $page, $cols);

        $users = [];
        if ($this->db->count > 0) {
            foreach ($records as $user) {
                $users[] = new User($user['user_id'], $user, $simple);
            }
        }
        $return_data = [
            "current_page" => $page,
            "total_pages" => $this->db->totalPages,
            "total_records" => $total_records,
            "page_limit" => $this->db->pageLimit,
            "records" => $users,
        ];
        return $return_data;
    }
    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = "user-".date("ymd").'-'.Utils::generateRandomString(8);
            $user = $this->findUserOfCode($code);
            if (!$user) {
                break;
            }
        }

        return $code;
    }
    /**
     * {@inheritdoc}
     */
    public function findUserOfId(int $user_id): ?User
    {
        $this->db->where("user_id", $user_id);
        $this->db->where("deleted_by", 0);

        $user = $this->db->getOne("eudr_users");
        if (empty($user)) {
            // throw new UserNotFoundException("The user you requested does not exist.", 102);
            return null;
        }
        return new User($user['user_id'], $user);
    }

    /**
     * {@inheritdoc}
     */
    public function findUserOfUsername(string $username): ?User
    {
        $this->db->where("u.username", $username);
        $this->db->where("u.deleted_by", 0);
        $this->db->join('eudr_companies com', 'com.company_id = u.company_id', 'LEFT');
        $user = $this->db->getOne("eudr_users u", "u.*, com.company_code, com.company_name, com.short_name");
        if (empty($user)) {
            // throw new UserNotFoundException("The user you requested does not exist.", 102);
            return null;
        }
        return new User($user['user_id'], $user);
    }
    /**
     * {@inheritdoc}
     */
    public function findUserOfCode(string $code): ?User
    {
        $this->db->where("u.user_code", $code);
        $this->db->where("u.deleted_by", 0);
        $this->db->join('eudr_companies com', 'com.company_id = u.company_id', 'LEFT');
        $user = $this->db->getOne("eudr_users u", "u.*, com.company_code, com.company_name, com.short_name");
        if (empty($user)) {
            // throw new UserNotFoundException("The user you requested does not exist.", 102);
            return null;
        }
        return new User($user['user_id'], $user);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findUserOfEmail(string $email): ?User
    {
        $this->db->where("u.email", $email);
        $this->db->where("u.deleted_by", 0);
        //$this->db->where("is_approved", 1);
        $this->db->join('eudr_companies com', 'com.company_id = u.company_id', 'LEFT');
        $user = $this->db->getOne("eudr_users u", "u.*, com.company_code, com.company_name, com.short_name");
        if (empty($user)) {
            // throw new UserNotFoundException("The user you requested does not exist.", 102);
            return null;
        }
        return new User($user['user_id'], $user);
    }

    /**
     * {@inheritdoc}
     */
    public function findUserOfPhone(string $phone): ?User
    {
        $this->db->where("u.phone", $phone);
        $this->db->where("u.deleted_by", 0);
        //$this->db->where("is_approved", 1);
        $this->db->join('eudr_companies com', 'com.company_id = u.company_id', 'LEFT');
        $user = $this->db->getOne("eudr_users u", "u.*, com.company_code, com.company_name, com.short_name");
        if (empty($user)) {
            return null;
        }
        return new User($user['user_id'], $user);
    }

    /**
     * {@inheritdoc}
     */
    public function createUser(array $data_item): User
    {
        foreach ($data_item as $key => $str) {
            $data_item[$key] = $this->db->escape($str);
        }

        $id = $this->db->insert('eudr_users', $data_item);

        $this->db->where("u.user_id", $id);
        $this->db->join('eudr_companies com', 'com.company_id = u.company_id', 'LEFT');
        $user = $this->db->getOne("eudr_users u", "u.*, com.company_code, com.company_name, com.short_name");
        if (empty($user)) {
            throw new UserNotFoundException();
        }
        return new User($user['user_id'], $user);
    }

    /**
     * {@inheritdoc}
     */
    public function updateUser(int $user_id, array $data_item): User
    {
        foreach ($data_item as $key => $str) {
            $data_item[$key] = $this->db->escape($str);
        }
        $this->db->where("user_id", $user_id);
        $this->db->update('eudr_users', $data_item);

        // Fetch updated user
        $this->db->where("u.user_id", $user_id);
        $this->db->join('eudr_companies com', 'com.company_id = u.company_id', 'LEFT');
        $user = $this->db->getOne("eudr_users u", "u.*, com.company_code, com.company_name, com.short_name");
        if (empty($user)) {
            throw new UserNotFoundException();
        }
        return new User($user['user_id'], $user);
    }

    /**
     * {@inheritdoc}
     */
    public function mapPermissionNamesToIds(array $permissionNames): array
    {
        if (empty($permissionNames)) {
            return [];
        }

        // Loại bỏ trùng, tránh query thừa
        $permissionNames = array_values(array_unique($permissionNames));

        $this->db->where('name', $permissionNames, 'IN');
        $rows = $this->db->get('eudr_permissions', null, 'permission_id, name');

        if (empty($rows)) {
            return [];
        }

        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int)$row['permission_id'];
        }

        return $ids;
    }

    /**
     * {@inheritdoc}
     */
    public function updateUserPermission(int $user_id, array $permissions, $use_permission_names = false): ?User
    {
        $permissionIds = array_values(array_unique($permissions));
        if($use_permission_names === true) {
            $permissionIds = $this->mapPermissionNamesToIds($permissions);
        }
        
        $this->db->startTransaction();

        // Delete old permissions
        $this->db->where("user_id", $user_id);
        $this->db->delete('eudr_user_permissions');
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }
        // Insert new permissions
        $insert_data = [];
        foreach ($permissionIds as $permission_id) {
            $insert_data[] = [
                'user_id' => $user_id,
                'permission_id' => $permission_id,
            ];
        }

        $ids = $this->db->insertMulti('eudr_user_permissions', $insert_data);
        
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->commit();

        $this->db->where("user_id", $user_id);
        $user = $this->db->getOne("eudr_users");
        if (empty($user)) {
            throw new UserNotFoundException();
        }
        return new User($user['user_id'], $user);
    }

    /**
     * {@inheritdoc}
     */
    public function updateUserLastLogin(int $user_id): User
    {
        $this->db->where("deleted_by", 0);
        $this->db->where("is_approved", 1);
        $this->db->where("user_id", $user_id);
        $this->db->update('eudr_users', ['last_login_at' => date('Y-m-d H:i:s', time())]);

        $this->db->where("user_id", $user_id);
        $user = $this->db->getOne("eudr_users");
        if (empty($user)) {
            throw new UserNotFoundException();
        }
        return new User($user['user_id'], $user);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteUser(int $user_id, int $deleted_by): void
    {
        $this->db->where("user_id", $user_id);
        $this->db->update('eudr_users', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

    /**
     * {@inheritdoc}
     */
    public function generateUserCode(): string
    {
        $code = '';
        while (true) {
            $code = "user-".date("ymd").'-'.Utils::generateRandomString(8);
            $user = $this->findUserOfCode($code);
            if (!$user) {
                break;
            }
        }
        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserPermissions(int $user_id): array
    {
        $permissions = [];

        // 1. Quyền gán trực tiếp
        $this->db->orderBy("p.name", "ASC");
        $this->db->where("u_p.user_id", $user_id);
        $this->db->join("eudr_permissions p", "p.permission_id=u_p.permission_id", "LEFT");
        $user_permissions = $this->db->get("eudr_user_permissions u_p", null, "p.name");
        foreach ($user_permissions as $perm) {
            if (!empty($perm['name'])) {
                $permissions[] = $perm['name'];
            }
        }
        

        // 2. Quyền theo role (system role)
        $this->db->orderBy("p.name", "ASC");
        $this->db->where("ur.user_id", $user_id);
        $this->db->join("eudr_role_permissions rp", "ur.role_id=rp.role_id", "LEFT");
        $this->db->join("eudr_permissions p", "p.permission_id=rp.permission_id", "LEFT");
        $role_permissions = $this->db->get("eudr_user_roles ur", null, "p.name");
        
        foreach ($role_permissions as $perm) {
            if (!empty($perm['name'])) {
                $permissions[] = $perm['name'];
            }
        }

        // 3. Quyền theo group nội bộ công ty
        //    - User là member của 1 hoặc nhiều group trong cùng company
        //    - Mỗi group được gán nhiều permission
        $companyId = $this->currentUser->getCompanyId();
        if (!empty($companyId)) {
            $this->db->orderBy("p.name", "ASC");
            $this->db->where("gm.user_id", $user_id);
            // Only groups that belong to the current company and are not deleted
            // Tạm bỏ filter company_id để có thể lấy quyền nhóm cho user thuộc công ty nào cũng được
            //$this->db->where("g.company_id", $companyId);
            $this->db->where("g.deleted_by", 0);
            $this->db->join("eudr_company_groups g", "g.company_group_id=gm.company_group_id", "LEFT");
            $this->db->join("eudr_company_group_permissions gp", "gp.company_group_id=g.company_group_id", "LEFT");
            $this->db->join("eudr_permissions p", "p.permission_id=gp.permission_id", "LEFT");
            $group_permissions = $this->db->get("eudr_company_group_members gm", null, "p.name");
            if (!empty($group_permissions)) {
                foreach ($group_permissions as $perm) {
                    if (!empty($perm['name'])) {
                        $permissions[] = $perm['name'];
                    }
                }
            }
        }

        // Normalize to a dense, zero-based array to keep JSON output as a list
        return array_values(array_unique($permissions));
    }

    /**
     * {@inheritdoc}
     */
    public function getRolePermissions(string $role_name): array
    {
        $permissions = [];

        $this->db->orderBy("p.name", "ASC");
        $this->db->where("r.name", $role_name);
        $this->db->join("eudr_role_permissions rp", "r.role_id=rp.role_id", "LEFT");
        $this->db->join("eudr_permissions p", "p.permission_id=rp.permission_id", "LEFT");
        $role_permissions = $this->db->get("eudr_roles r", null, "p.name");
        
        foreach ($role_permissions as $perm) {
            if (!empty($perm['name'])) {
                $permissions[] = $perm['name'];
            }
        }

        return array_values(array_unique($permissions));
    }

    /**
     * {@inheritdoc}
     */
    public function userHasPermission(int $user_id, string $permission_name): bool
    {
        $permissions = $this->getUserPermissions($user_id);
        if (in_array($permission_name, $permissions)) {
            return true;
        }

        // Check for wildcard permissions
        foreach ($permissions as $perm) {
            if (str_ends_with((string)$perm, '.*')) {
                $prefix = rtrim((string)$perm, '.*');
                if (str_starts_with($permission_name, $prefix . '.')) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getCURDPermissionStatus(int $user_id, string $module, string $action): string
    {
        $permissions = $this->getUserPermissions($user_id);

        // Với action 'create' thì chỉ cần có quyền là true
        if ($action === 'create') {
            if (in_array("$module.$action", $permissions) || in_array("$module.*", $permissions)) {
                return 'allowed';
            }
            return '';
        }

        // Với các action khác: view/update/delete
        if (in_array("$module.$action.all", $permissions)) {
            return 'all';
        }
        if (in_array("$module.*", $permissions)) {
            return 'all';
        }
        if (in_array("$module.$action.own", $permissions)) {
            return 'own';
        }
        if (in_array("$module.$action.self", $permissions)) {
            return 'self';
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getAllPermissions(): array
    {
        $user_id = $this->currentUser->getUserId() ?? 0;
        if(empty($user_id) || $user_id !== 43250) {
            $this->db->where("module", ["company", "company_group", "company_member"], "NOT IN");
            $this->db->where("is_system", 0);
            $this->db->where("scope", ["all"], "NOT IN");
        }
        $this->db->where("is_active", 1);
        $this->db->orderBy("module", "ASC");
        $permissions = $this->db->get("eudr_permissions", null, ["permission_id", "name", "display_name", "module", "description", "scope", "action"]);

        if (empty($permissions)) {
            return [];
        }

        return $permissions;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllRoles(): array
    {   
        $this->db->where('name', Array('admin', 'inspector'), 'NOT IN');
        $this->db->orderBy("sort_order", "ASC");
        $roles = $this->db->get("eudr_roles", null, ["role_id", "name", "description"]);

        if (empty($roles)) {
            return [];
        }

        return $roles;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoleIdByName(string $role_name): ?int
    {

        $this->db->where("name", $role_name);
        $role = $this->db->getOne("eudr_roles", ["role_id"]);

        return $role ? (int)$role['role_id'] : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserRole(int $user_id): ?string
    {
        $this->db->where("user_id", $user_id);
        $this->db->join("eudr_roles r", "r.role_id=ur.role_id", "LEFT");
        $role = $this->db->getOne("eudr_user_roles ur", "r.name");

        return $role ? $role['name'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserRoles(int $user_id): array
    {
        $this->db->where("ur.user_id", $user_id);
        $this->db->join("eudr_roles r", "r.role_id=ur.role_id", "LEFT");
        $this->db->orderBy("r.sort_order", "ASC");
        $rows = $this->db->get("eudr_user_roles ur", null, "r.role_id, r.name, r.description");

        if (empty($rows)) {
            return [];
        }

        $roles = [];
        foreach ($rows as $row) {
            if (!empty($row['name'])) {
                $roles[] = [
                    'role_id' => (int)$row['role_id'],
                    'name' => $row['name'],
                    'description' => $row['description'] ?? '',
                ];
            }
        }

        return $roles;
    }

    /**
     * {@inheritdoc}
     */
    public function removeRoleFromUser(int $user_id, string $role_name): void
    {
        $role_id = $this->getRoleIdByName($role_name);
        if (!$role_id) {
            return;
        }

        $this->db->where("user_id", $user_id);
        $this->db->where("role_id", $role_id);
        $this->db->delete("eudr_user_roles");
    }

    /**
     * {@inheritdoc}
     */
    public function removeAllRolesFromUser(int $user_id): void
    {
        $this->db->where("user_id", $user_id);
        $this->db->delete("eudr_user_roles");
    }

    /**
     * {@inheritdoc}
     */
    public function assignRoleToUser($user_id, $role_name): void 
    {

        $role_id = $this->getRoleIdByName($role_name);
        if (!$role_id) {
            throw new UserNotFoundException("Role not found: $role_name", 104);
        }
        // Check if the user already has this role
        $this->db->where("user_id", $user_id);
        $this->db->where("role_id", $role_id);
        $existing = $this->db->getOne("eudr_user_roles");
        if (empty($existing)) {
            // Assign role to user
            $this->db->insert("eudr_user_roles", [
                "user_id" => $user_id,
                "role_id" => $role_id
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateRolePermissions(int $role_id, array $permission_ids): bool
    {
        $this->db->startTransaction();

        // Xóa toàn bộ permissions cũ của role
        $this->db->where("role_id", $role_id);
        $this->db->delete('eudr_role_permissions');
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return false;
        }

        // Insert permissions mới
        if (!empty($permission_ids)) {
            $insert_data = [];
            foreach ($permission_ids as $permission_id) {
                $insert_data[] = [
                    'role_id' => $role_id,
                    'permission_id' => (int)$permission_id,
                ];
            }
            $this->db->insertMulti('eudr_role_permissions', $insert_data);
            if ($this->db->getLastErrno() !== 0) {
                $this->db->rollback();
                return false;
            }
        }

        $this->db->commit();
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getRolePermissionsByRoleId(int $role_id): array
    {
        $this->db->orderBy("p.name", "ASC");
        $this->db->where("rp.role_id", $role_id);
        $this->db->join("eudr_permissions p", "p.permission_id=rp.permission_id", "LEFT");
        $permissions = $this->db->get("eudr_role_permissions rp", null, "p.permission_id, p.name, p.display_name, p.module, p.description, p.scope, p.action");

        return $permissions ?: [];
    }

    /**
     * {@inheritdoc}
     */
    public function findRoleById(int $role_id): ?array
    {
        $this->db->where("role_id", $role_id);
        $role = $this->db->getOne("eudr_roles", ["role_id", "name", "description", "sort_order"]);

        return $role ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function findAllWorkerUsers(array $params): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $search = $params['search'] ?? '';
        $permission_status = $params['permission_status'] ?? '';
        $user_id = $params['user_id'] ?? 0;

        // Count total records
        $total_records = 0;
        $this->db->where("u.parent_user_id", 0, "!=");
        $this->db->where("u.deleted_by", 0);
        if (!empty($search)) {
            $this->db->where("(full_name LIKE ? OR email LIKE ? OR phone LIKE ?)", 
            ["%$search%", "%$search%", "%$search%"]);
        }

        if(!empty($permission_status) && ($permission_status === 'own')) {
            $this->db->where("parent_user_id", $user_id);
            $this->db->where("created_by", $user_id);
        }
        $total_records = $this->db->getValue("eudr_users u", "count(*)");

        // Set pagination
        if (!empty($page_limit)) {
            $this->db->pageLimit = intval($page_limit);
        }
        if (!empty($search)) {
            $this->db->where("(full_name LIKE ? OR email LIKE ? OR phone LIKE ?)", 
            ["%$search%", "%$search%", "%$search%"]);
        }

        if(!empty($permission_status) && ($permission_status === 'own')) {
            $this->db->where("parent_user_id", $user_id);
            $this->db->where("created_by", $user_id);
        }

        $cols = null;
        $this->db->where("u.parent_user_id", 0, "!=");
        $this->db->where("u.deleted_by", 0);
        $this->db->orderBy("u.user_id", "DESC");
        $records = $this->db->arraybuilder()->paginate("eudr_users u", $page, $cols);

        $users = [];
        if ($this->db->count > 0) {
            foreach ($records as $user) {
                $users[] = new User($user['user_id'], $user);
            }
        }
        
        return [
            "current_page" => $page,
            "total_pages" => $this->db->totalPages,
            "total_records" => $total_records,
            "page_limit" => $this->db->pageLimit,
            "records" => $users,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function findWorkerUserOfCodeWithPermission(string $user_code, int $user_id, string $permission_status): ?User
    {
        $this->db->where("user_code", $user_code);
        $this->db->where("deleted_by", 0);

        if ($permission_status === 'own') {
            $this->db->where("created_by", $user_id);
        }

        $user = $this->db->getOne("eudr_users");
        if (empty($user)) {
            return null;
        }
        
        return new User($user['user_id'], $user);
    }

    /**
     * {@inheritdoc}
     */
    public function requestOtp(string $phone, string $purpose = 'register'): array
    {
        $now = time();
        
        // Kiểm tra rate limit
        $this->db->where("phone", $phone);
        $rate_limit = $this->db->getOne("eudr_sms_otp_rate_limits");
        if ($rate_limit && $rate_limit['locked_until'] && $now < strtotime($rate_limit['locked_until'])) {
            throw new UserNotFoundException("Bạn đã gửi quá nhiều OTP. Vui lòng thử lại sau.");
        }

        if(empty($rate_limit)) {
            $this->db->insert("eudr_sms_otp_rate_limits", [
                "phone" => $phone,
                "purpose" => $purpose,
                "request_count" => 1,
                "last_request_at" => date('Y-m-d H:i:s', $now),
                "created_at" => date('Y-m-d H:i:s', $now),
                "updated_at" => date('Y-m-d H:i:s', $now),
                "locked_until" => null,
            ]);
        }

        if(!empty($rate_limit)) {
            // Kiểm tra số lần gửi trong 1 giờ
            $last_request = $rate_limit['last_request_at'];
            if ((strtotime($last_request) + 3600) > $now && $rate_limit['request_count'] >= $this->MAX_RESEND_PER_HOUR) {
                // Lock trong 1 giờ
                $locked_until = date("Y-m-d H:i:s", strtotime("+1 hour"));
                $this->db->where("otp_rate_limit_id", $rate_limit['otp_rate_limit_id']);
                $this->db->update("eudr_sms_otp_rate_limits", ["locked_until" => $locked_until]);
                throw new UserNotFoundException("Quá giới hạn gửi OTP trong 1 giờ.");
            }
            // Reset counter nếu đã qua 1h
            if ((strtotime($last_request) + 3600) < $now) {
                $rate_limit['request_count'] = 0;
            }
            $this->db->where("otp_rate_limit_id", $rate_limit['otp_rate_limit_id']);
            $this->db->update("eudr_sms_otp_rate_limits", [
                "request_count" => $rate_limit['request_count'] + 1,
                "last_request_at" => date("Y-m-d H:i:s"),
                "locked_until" => null
            ]);
        }

        $otp_code = Utils::generateRandomNumber(6);

        $otp_request_insert = [
            'phone' => $phone,
            'otp_code' => $otp_code,
            'purpose' => $purpose,
            'attempt_count' => 0,
            'is_verified' => 0,
            'expires_at' => date("Y-m-d H:i:s", strtotime("+5 minutes")),
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s"),
        ];

        $otp_request_id = $this->db->insert("eudr_sms_otp_requests", $otp_request_insert);

        if (empty($otp_request_id)) {
            throw new UserNotFoundException("Could not create OTP request: " . $this->db->getLastError());
        }

        $this->db->where("otp_request_id", $otp_request_id);
        $request_output = $this->db->getOne("eudr_sms_otp_requests");

        return $request_output;
    }

    /**
     * {@inheritdoc}
     */
    public function verifyOtp(int $otp_request_id, string $phone, string $otp_code, string $purpose): bool
    {
        $now = time();
        
        $this->db->where("otp_request_id", $otp_request_id);
        $this->db->where("phone", $phone);
        $this->db->where("purpose", $purpose);
        //$this->db->where("otp_code", $otp_code);
        //$this->db->where("is_verified", 0);
        //$this->db->where("expires_at", date("Y-m-d H:i:s"), ">");
        $this->db->orderBy("otp_request_id", "DESC");
        $otp = $this->db->getOne("eudr_sms_otp_requests");
        if (empty($otp)) {
            throw new UserNotFoundException("OTP không tồn tại.");
        }

        if ($now > strtotime($otp['expires_at'])) {
            throw new UserNotFoundException("OTP đã hết hạn.");
        }

        if ($otp['attempt_count'] >= $this->MAX_ATTEMPTS) {
            throw new UserNotFoundException("Bạn đã nhập sai quá số lần cho phép.");
        }

        if ($otp['otp_code'] != $otp_code) {
            $this->db->where("otp_request_id", $otp['otp_request_id']);
            $this->db->update("eudr_sms_otp_requests", ["attempt_count" => $otp['attempt_count'] + 1]);

            throw new UserNotFoundException("OTP không đúng.");
        }

        // Mark OTP as verified
        $this->db->where("otp_request_id", $otp_request_id);
        $this->db->update("eudr_sms_otp_requests", [
            "is_verified" => 1,
            "verified_at" => date("Y-m-d H:i:s"),
        ]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function findOtpRequest(int $otp_request_id, string $phone, string $purpose): ?array
    {
        $this->db->where("otp_request_id", $otp_request_id);
        $this->db->where("phone", $phone);
        $this->db->where("purpose", $purpose);
        return $this->db->getOne("eudr_sms_otp_requests");
    }
}
