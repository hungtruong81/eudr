<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\CompanyMember;

use App\Application\Utility\CurrentUserContext;
use App\Domain\CompanyMember\CompanyMemberRepository;
use App\Domain\User\User;
use App\Application\Utility\Utils;
class InDatabaseCompanyMemberRepository implements CompanyMemberRepository
{
    /**
     * @var \MysqliDb
     */
    private $db;

    /**
     * @var CurrentUserContext
     */
    private $currentUser;

    public function __construct(\MysqliDb $db, CurrentUserContext $currentUserContext)
    {
        $this->db = $db;
        $this->currentUser = $currentUserContext;
    }
    /**
     * Apply scope-based filtering to the query.
     *
     * @param string $scope
     * @param int $authUserId
     * @param int $companyId
     */
    private function scopeWhere(string $scope, int $authUserId, int $companyId, string $alias = ''): void
    {
        $prefix = $alias ? $alias.'.' : '';
        $this->db->where($prefix.'deleted_by', 0);

        if ($scope === 'self') {
            $this->db->where($prefix.'user_id', $authUserId);
            $this->db->where($prefix.'company_id', $companyId);
        } elseif ($scope === 'own') {
            $this->db->where($prefix.'company_id', $companyId);
        } elseif ($scope === 'all') {
            // optional: nếu muốn vẫn giới hạn tenant
            // $this->db->where($prefix.'company_id', $companyId);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findAllMembers(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null): array
    {
        $company_id = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $search = $params['search'] ?? '';
        $status = $params['status'] ?? null; // reserved for future use
        $company_id_param = $params['company_id_param'] ?? 0;
        $register_type = $params['register_type'] ?? 'all';
        
        // Count total records
        $this->scopeWhere($scope, $authUserId, (int)$company_id, 'u');
        if ($scope === 'all' && !empty($company_id_param)) {
            $this->db->where('u.company_id', $company_id_param);
        }
        $this->db->join('eudr_companies com', 'com.company_id = u.company_id', 'LEFT');
        if (!empty($search)) {
            $this->db->where('(u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)', [
                "%$search%",
                "%$search%",
                "%$search%",
            ]);
        }
        // Optionally filter by status if column exists
        if (!empty($status) && $status !== 'all') {
            $this->db->where('u.status', $status);
        }
        if (!empty($register_type) && $register_type !== 'all') {
            // Multi-role: filter by eudr_user_roles, fallback to register_type for unmapped types
            $roleFilterSQL = Utils::buildRoleFilterSQL('u.user_id', $register_type);
            if (!empty($roleFilterSQL)) {
                $this->db->where($roleFilterSQL);
            } else {
                $this->db->where('u.register_type', $register_type);
            }
        }
        $total_records = (int)$this->db->getValue('eudr_users u', 'count(*)');

        // Set pagination
        $cols = 'u.*, com.company_code, com.company_name, com.short_name';
        $this->db->pageLimit = (int)$page_limit;
        $this->scopeWhere($scope, $authUserId, (int)$company_id, 'u');
        if ($scope === 'all' && !empty($company_id_param)) {
            $this->db->where('u.company_id', $company_id_param);
        }
        if (!empty($search)) {
            $this->db->where('(u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)', [
                "%$search%",
                "%$search%",
                "%$search%",
            ]);
        }
        if (!empty($status) && $status !== 'all') {
            $this->db->where('u.status', $status);
        }
        if (!empty($register_type) && $register_type !== 'all') {
            // Multi-role: filter by eudr_user_roles, fallback to register_type for unmapped types
            $roleFilterSQL = Utils::buildRoleFilterSQL('u.user_id', $register_type);
            if (!empty($roleFilterSQL)) {
                $this->db->where($roleFilterSQL);
            } else {
                $this->db->where('u.register_type', $register_type);
            }
        }
        $this->db->join('eudr_companies com', 'com.company_id = u.company_id', 'LEFT');
        $this->db->orderBy('u.user_id', 'DESC');
        $records = $this->db->arraybuilder()->paginate('eudr_users u', (int)$page, $cols);

        $users = [];
        if ($this->db->count > 0) {
            foreach ($records as $user) {
                $users[] = new User((int)$user['user_id'], $user);
            }
        }

        return [
            'current_page' => (int)$page,
            'total_pages' => (int)$this->db->totalPages,
            'total_records' => $total_records,
            'page_limit' => (int)$this->db->pageLimit,
            'records' => $users,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function findMemberOfId(int $user_id, ?int $company_id = null): ?User
    {
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->db->where('u.user_id', $user_id);
        if (!empty($companyId)) {
            $this->db->where('u.company_id', $companyId);
        }
        $this->db->where('u.deleted_by', 0);
        $this->db->join('eudr_companies com', 'com.company_id = u.company_id', 'LEFT');
        $user = $this->db->getOne('eudr_users u', "u.*, com.company_code, com.company_name, com.short_name");
        if (empty($user)) {
            return null;
        }

        return new User((int)$user['user_id'], $user);
    }

    /**
     * {@inheritdoc}
     */
    public function findMemberOfCode(string $user_code, ?int $company_id = null): ?User
    {
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->db->where('u.user_code', $user_code);
        if (!empty($companyId)) {
            $this->db->where('u.company_id', $companyId);
        }
        $this->db->where('u.deleted_by', 0);
        $this->db->join('eudr_companies com', 'com.company_id = u.company_id', 'LEFT');
        $user = $this->db->getOne('eudr_users u', "u.*, com.company_code, com.company_name, com.short_name");
        if (empty($user)) {
            return null;
        }

        return new User((int)$user['user_id'], $user);
    }

    /**
     * {@inheritdoc}
     */
    public function findMemberOfIdWithPermission(int $user_id, ?int $auth_user_id, string $scope, ?int $company_id = null): ?User
    {
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId);
        $this->db->where('user_id', $user_id);

        $user = $this->db->getOne('eudr_users');
        if (empty($user)) {
            return null;
        }

        return new User((int)$user['user_id'], $user);
    }

    /**
     * {@inheritdoc}
     */
    public function findMemberOfCodeWithPermission(string $user_code, ?int $auth_user_id, string $scope, ?int $company_id = null): ?User
    {
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId);
        $this->db->where('user_code', $user_code);

        $user = $this->db->getOne('eudr_users');
        if (empty($user)) {
            return null;
        }

        return new User((int)$user['user_id'], $user);
    }

    /**
     * {@inheritdoc}
     */
    public function createMember(array $data): User
    {
        // Always bind member to current company for multi-tenant safety
        $company_id = $this->currentUser->getCompanyId() ?? 0;
        if(empty($data['company_id'])) {
            $data['company_id'] = $company_id;
        }

        $this->db->insert('eudr_users', $data);
        $user_id = (int)$this->db->getInsertId();

        $this->db->where("user_id", $user_id);
        $user = $this->db->getOne("eudr_users");
        if (empty($user)) {
            throw new \RuntimeException("Failed to retrieve newly created user with ID $user_id");
        }

        return new User($user['user_id'], $user);
    }

    /**
     * {@inheritdoc}
     */
    public function updateMember(int $user_id, array $data): User
    {
        // Backward-compatible: default to current user context with 'own' scope
        $authUserId = $this->currentUser->getUserId() ?? 0;
        $companyId = $this->currentUser->getCompanyId() ?? 0;

        return $this->updateMemberWithPermission($user_id, $data, $authUserId, 'own', $companyId);
    }

    /**
     * {@inheritdoc}
     */
    public function updateMemberWithPermission(int $user_id, array $data, ?int $auth_user_id, string $scope, ?int $company_id = null): User
    {
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId);
        $this->db->where('user_id', $user_id);

        $updated = $this->db->update('eudr_users', $data);
        if ($updated === false) {
            throw new \RuntimeException("Failed to update user with ID $user_id");
        }

        // Fetch again with the same scoped filters to ensure visibility
        $this->scopeWhere($scope, $authUserId, (int)$companyId);
        $this->db->where('user_id', $user_id);
        $user = $this->db->getOne('eudr_users');
        if (empty($user)) {
            throw new \RuntimeException("Failed to retrieve updated user with ID $user_id");
        }

        return new User((int)$user['user_id'], $user);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMemberWithPermission(int $user_id, ?int $auth_user_id, string $scope, ?int $company_id = null): void
    {
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);

        // Ensure visibility and permission
        $this->scopeWhere($scope, $authUserId, (int)$companyId);
        $this->db->where('user_id', $user_id);

        // Soft delete
        $deleted = $this->db->update('eudr_users', [
            'deleted_by' => $authUserId,
            'deleted_at' => date('Y-m-d H:i:s', time()),
        ]);

        if ($deleted === false) {
            throw new \RuntimeException("Failed to delete user with ID $user_id");
        }
    }
}
