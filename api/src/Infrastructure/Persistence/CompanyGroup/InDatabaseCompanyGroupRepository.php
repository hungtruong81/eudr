<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\CompanyGroup;

use App\Application\Utility\CurrentUserContext;
use App\Domain\CompanyGroup\CompanyGroup;
use App\Domain\CompanyGroup\CompanyGroupRepository;
use App\Application\Utility\Utils;

class InDatabaseCompanyGroupRepository implements CompanyGroupRepository
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
     * Áp dụng điều kiện theo scope self/own/all cho bảng group.
     */
    private function scopeWhere(string $scope, int $companyId, ?int $companyIdParam = null, string $alias = 'g'): void
    {
        $prefix = $alias ? $alias . '.' : '';

        if ($scope === 'self' || $scope === 'own') {
            $this->db->where($prefix . 'company_id', $companyId);
        } elseif ($scope === 'all' && !empty($companyIdParam)) {
            $this->db->where($prefix . 'company_id', $companyIdParam);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findAllByCompany(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array
    {
        $company_id = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $search = $params['search'] ?? '';
        $status = $params['status'] ?? 'all';
        $company_id_param = $company_id_param ?? ($params['company_id_param'] ?? null);

        // Count total records
        $this->scopeWhere($scope, (int)$company_id, $company_id_param, 'g');
        if ($status !== 'all') {
            $this->db->where('g.status', $status);
        }
        if (!empty($search)) {
            $this->db->where('(g.name LIKE ? OR g.description LIKE ?)', ["%$search%", "%$search%"]); 
        }
        $this->db->where('g.deleted_by', 0);
        $this->db->join('eudr_companies c', 'c.company_id = g.company_id', 'LEFT');
        $total_records = $this->db->getValue('eudr_company_groups g', 'count(*)');

        // Pagination
        $this->db->pageLimit = $page_limit;
        $this->scopeWhere($scope, (int)$company_id, $company_id_param, 'g');
        if ($status !== 'all') {
            $this->db->where('g.status', $status);
        }
        if (!empty($search)) {
            $this->db->where('(g.name LIKE ? OR g.description LIKE ?)', ["%$search%", "%$search%"]); 
        }
        $this->db->where('g.deleted_by', 0);
        $this->db->orderBy('g.company_group_id', 'DESC');
        $this->db->join('eudr_company_group_members gm', 'gm.company_group_id = g.company_group_id', 'LEFT');
        $this->db->join('eudr_companies c', 'c.company_id = g.company_id', 'LEFT');
        $this->db->groupBy('g.company_group_id');
        $cols = 'g.*, COUNT(DISTINCT gm.user_id) AS member_count, c.company_code, c.company_name, c.short_name';
        $records = $this->db->arraybuilder()->paginate('eudr_company_groups g', $page, $cols);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = new CompanyGroup($item['company_group_id'], $item);
            }
        }

        return [
            'current_page' => $page,
            'total_pages' => $this->db->totalPages,
            'total_records' => $total_records,
            'page_limit' => $this->db->pageLimit,
            'records' => $items,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function findGroupOfId(int $group_id): ?CompanyGroup
    {
        $this->db->where('g.company_group_id', $group_id);
        $this->db->where('g.deleted_by', 0);
        $this->db->join('eudr_companies c', 'c.company_id = g.company_id', 'LEFT');
        $group = $this->db->getOne('eudr_company_groups g', 'g.*, c.company_code, c.company_name, c.short_name');
        if (empty($group)) {
            return null;
        }

        return new CompanyGroup($group['company_group_id'], $group);
    }

    /**
     * {@inheritdoc}
     */
    public function findGroupOfIdWithPermission(int $group_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?CompanyGroup
    {
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, (int)$companyId, $company_id_param, 'g');
        $this->db->where('g.company_group_id', $group_id);
        $this->db->where('g.deleted_by', 0);
        $this->db->join('eudr_companies c', 'c.company_id = g.company_id', 'LEFT');
        $group = $this->db->getOne('eudr_company_groups g', 'g.*, c.company_code, c.company_name, c.short_name');
        if (empty($group)) {
            return null;
        }

        return new CompanyGroup($group['company_group_id'], $group);
    }

    /**
     * {@inheritdoc}
     */
    public function findGroupOfCode(string $group_code): ?CompanyGroup
    {
        $this->db->where('g.company_group_code', $group_code);
        $this->db->where('g.deleted_by', 0);
        $this->db->join('eudr_companies c', 'c.company_id = g.company_id', 'LEFT');
        $group = $this->db->getOne('eudr_company_groups g', 'g.*, c.company_code, c.company_name, c.short_name');
        if (empty($group)) {
            return null;
        }

        return new CompanyGroup((int)$group['company_group_id'], $group);
    }

    /**
     * {@inheritdoc}
     */
    public function findGroupOfCodeWithPermission(string $group_code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?CompanyGroup
    {
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, (int)$companyId, $company_id_param, 'g');
        $this->db->where('g.company_group_code', $group_code);
        $this->db->where('g.deleted_by', 0);
        $this->db->join('eudr_companies c', 'c.company_id = g.company_id', 'LEFT');
        $group = $this->db->getOne('eudr_company_groups g', 'g.*, c.company_code, c.company_name, c.short_name');
        if (empty($group)) {
            return null;
        }

        return new CompanyGroup((int)$group['company_group_id'], $group);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = "cogr-".date("ymd").'-'.Utils::generateRandomString(8);
            $group = $this->findGroupOfCode($code);
            if (!$group) {
                break;
            }
        }
        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function createGroup(array $data): CompanyGroup
    {
        // Always bind group to current company for multi-tenant safety
        $company_id = $this->currentUser->getCompanyId() ?? 0;
        $data['company_id'] = $company_id;

        $this->db->insert('eudr_company_groups', $data);
        $group_id = (int)$this->db->getInsertId();
        return $this->findGroupOfId($group_id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateGroup(int $group_id, array $data): CompanyGroup
    {
        // Ensure we only update groups within the current company
        $company_id = $this->currentUser->getCompanyId() ?? 0;
        $this->db->where('company_group_id', $group_id);
        if (!empty($company_id)) {
            $this->db->where('company_id', $company_id);
        }
        $this->db->update('eudr_company_groups', $data);
        return $this->findGroupOfId($group_id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateGroupWithPermission(int $group_id, array $data, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): CompanyGroup
    {
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, (int)$companyId, $company_id_param, '');
        $this->db->where('company_group_id', $group_id);
        $this->db->update('eudr_company_groups', $data);

        return $this->findGroupOfIdWithPermission($group_id, $auth_user_id, $scope, $company_id, $company_id_param);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteGroup(int $group_id, int $deleted_by): void
    {
        $company_id = $this->currentUser->getCompanyId() ?? 0;
        $this->db->where('company_group_id', $group_id);
        if (!empty($company_id)) {
            $this->db->where('company_id', $company_id);
        }
        $this->db->update('eudr_company_groups', [
            'deleted_by' => $deleted_by,
            'deleted_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteGroupWithPermission(int $group_id, int $deleted_by, string $scope, ?int $company_id = null, ?int $company_id_param = null): void
    {
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, (int)$companyId, $company_id_param, '');
        $this->db->where('company_group_id', $group_id);
        $this->db->update('eudr_company_groups', [
            'deleted_by' => $deleted_by,
            'deleted_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function setGroupPermissionsByNames(int $group_id, array $permissionNames): void
    {
        // Resolve permission IDs from names
        $permissionNames = array_values(array_unique(array_filter($permissionNames))); // clean

        // Clear existing
        $this->db->where('company_group_id', $group_id);
        $this->db->delete('eudr_company_group_permissions');

        if (empty($permissionNames)) {
            return;
        }

        $this->db->where('name', $permissionNames, 'IN');
        $perms = $this->db->get('eudr_permissions', null, ['permission_id']);
        if (empty($perms)) {
            return;
        }

        foreach ($perms as $perm) {
            $this->db->insert('eudr_company_group_permissions', [
                'company_group_id' => $group_id,
                'permission_id' => (int)$perm['permission_id'],
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getGroupPermissions(int $group_id): array
    {
        $this->db->where('gp.company_group_id', $group_id);
        $this->db->join('eudr_permissions p', 'p.permission_id=gp.permission_id', 'LEFT');
        $rows = $this->db->get('eudr_company_group_permissions gp', null, 'p.name');

        $names = [];
        foreach ($rows as $row) {
            if (!empty($row['name'])) {
                $names[] = $row['name'];
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * {@inheritdoc}
     */
    public function assignMembers(int $group_id, array $user_ids, int $assigned_by): void
    {
        $user_ids = array_values(array_unique(array_map('intval', $user_ids)));
        if (empty($user_ids)) {
            return;
        }

        // Remove existing mappings for these users in this group to avoid duplicates
        $this->db->where('company_group_id', $group_id);
        $this->db->where('user_id', $user_ids, 'IN');
        $this->db->delete('eudr_company_group_members');

        $now = date('Y-m-d H:i:s');
        foreach ($user_ids as $uid) {
            $this->db->insert('eudr_company_group_members', [
                'company_group_id' => $group_id,
                'user_id' => $uid,
                'assigned_by' => $assigned_by,
                'assigned_at' => $now,
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeMembers(int $group_id, array $user_ids): void
    {
        $user_ids = array_values(array_unique(array_map('intval', $user_ids)));
        if (empty($user_ids)) {
            return;
        }

        $this->db->where('company_group_id', $group_id);
        $this->db->where('user_id', $user_ids, 'IN');
        $this->db->delete('eudr_company_group_members');
    }

    /**
     * {@inheritdoc}
     */
    public function getGroupMemberIds(int $group_id): array
    {
        $this->db->where('company_group_id', $group_id);
        $rows = $this->db->get('eudr_company_group_members', null, 'user_id');

        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int)$row['user_id'];
        }

        return array_values(array_unique($ids));
    }

    /**
     * {@inheritdoc}
     */
    public function getGroupMembers(int $group_id, array $params = []): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $search = $params['search'] ?? '';

        // Count total
        $this->db->where('gm.company_group_id', $group_id);
        $this->db->where('u.deleted_by', 0);
        if (!empty($search)) {
            $this->db->where('(u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)', ["%$search%", "%$search%", "%$search%"]);
        }
        $this->db->join('eudr_users u', 'u.user_id = gm.user_id', 'LEFT');
        $total_records = (int)$this->db->getValue('eudr_company_group_members gm', 'COUNT(DISTINCT u.user_id)');

        // Pagination
        $this->db->pageLimit = $page_limit;
        $this->db->where('gm.company_group_id', $group_id);
        $this->db->where('u.deleted_by', 0);
        if (!empty($search)) {
            $this->db->where('(u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)', ["%$search%", "%$search%", "%$search%"]);
        }
        $this->db->join('eudr_users u', 'u.user_id = gm.user_id', 'LEFT');
        $this->db->groupBy('u.user_id');
        //$this->db->orderBy('gm.assigned_at', 'DESC');
        $cols = 'u.*';
        $records = $this->db->arraybuilder()->paginate('eudr_company_group_members gm', $page, $cols);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = [
                    'user_id' => (int)($item['user_id'] ?? 0),
                    'user_code' => $item['user_code'] ?? '',
                    'full_name' => $item['full_name'] ?? '',
                    'email' => $item['email'] ?? '',
                    'phone' => $item['phone'] ?? '',
                    'avatar' => $item['avatar'] ?? '',
                    'register_type' => $item['register_type'] ?? '',
                    'company_id' => isset($item['company_id']) ? (int)$item['company_id'] : 0,
                    // 'assigned_at' => $item['assigned_at'] ?? null,
                    // 'assigned_by' => isset($item['assigned_by']) ? (int)$item['assigned_by'] : null,
                ];
            }
        }

        return [
            'current_page' => $page,
            'total_pages' => $this->db->totalPages,
            'total_records' => $total_records,
            'page_limit' => $this->db->pageLimit,
            'records' => $items,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function findGroupByDefaultNameAndCompany(string $default_name, int $companyId): ?CompanyGroup
    {
        $this->db->where('g.default_name', $default_name);
        $this->db->where('g.company_id', $companyId);
        $this->db->where('g.deleted_by', 0);
        $group = $this->db->getOne('eudr_company_groups g', 'g.*');
        if (empty($group)) {
            return null;
        }

        return new CompanyGroup((int)$group['company_group_id'], $group);
    }

    /**
     * {@inheritdoc}
     */
    public function createCompanyGroupDefault(array $dataGroup, int $companyId): CompanyGroup
    {
        $group = $this->findGroupByDefaultNameAndCompany($dataGroup['default_name'], $companyId);
        if ($group) {
            return $group;
        }
        $permissionNames = $dataGroup['permissions'] ?? [];

        unset($dataGroup['permissions']);

        $dataGroup['company_id'] = $companyId;
        $dataGroup['company_group_code'] = $this->generateCode();
        $dataGroup['created_by'] = $this->currentUser->getUserId() ?? 0;
        $dataGroup['created_at'] = date("Y-m-d H:i:s", time());
        
        $group_id = $this->db->insert('eudr_company_groups', $dataGroup);


        $this->setGroupPermissionsByNames((int)$group_id, $permissionNames);
        
        return $this->findGroupOfId((int)$group_id);
    }
}
