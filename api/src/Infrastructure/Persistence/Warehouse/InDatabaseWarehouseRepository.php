<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Warehouse;

use App\Application\Utility\CurrentUserContext;
use App\Application\Utility\Utils;
use App\Domain\Warehouse\Warehouse;
use App\Domain\Warehouse\WarehouseNotFoundException;
use App\Domain\Warehouse\WarehouseRepository;

class InDatabaseWarehouseRepository implements WarehouseRepository
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
     * Apply scope-based filtering (self/own/all).
     */
    private function scopeWhere(string $scope, int $authUserId, int $companyId, ?int $companyIdParam = null, string $alias = 'w'): void
    {
        $prefix = $alias ? $alias . '.' : '';
        $this->db->where($prefix . 'deleted_by', 0);

        if ($scope === 'self') {
            $this->db->where($prefix . 'created_by', $authUserId);
            $this->db->where($prefix . 'company_id', $companyId);
        } elseif ($scope === 'own') {
            $this->db->where($prefix . 'company_id', $companyId);
        } elseif ($scope === 'all' && !empty($companyIdParam)) {
            $this->db->where($prefix . 'company_id', $companyIdParam);
        }
    }

    /**
     * Find all warehouses with optional filtering and pagination.
     *
     * @param array $params
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return array
     */
    public function findAll($params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $search = $params['search'] ?? '';
        $factory_id = $params['factory_id'] ?? 0;
        $warehouse_type = $params['warehouse_type'] ?? 'all';
        $status = $params['status'] ?? 'all';
        $companyIdParam = $company_id_param ?? 0;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'w');
        if (!empty($search)) {
            $this->db->where('(w.warehouse_name LIKE ? OR w.warehouse_code LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($factory_id)) {
            $this->db->where('w.factory_id', $factory_id);
        }
        if ($warehouse_type !== 'all') {
            $this->db->where('w.warehouse_type', $warehouse_type);
        }
        if ($status !== 'all') {
            $this->db->where('w.status', $status);
        }
        $total_records = (int)$this->db->getValue('eudr_warehouses w', 'count(*)');

        $this->db->pageLimit = $page_limit;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'w');
        if (!empty($search)) {
            $this->db->where('(w.warehouse_name LIKE ? OR w.warehouse_code LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($factory_id)) {
            $this->db->where('w.factory_id', $factory_id);
        }
        if ($warehouse_type !== 'all') {
            $this->db->where('w.warehouse_type', $warehouse_type);
        }
        if ($status !== 'all') {
            $this->db->where('w.status', $status);
        }

        $cols = 'w.*, f.factory_name';
        if (!empty($params['order_by'])) {
            $this->db->orderBy('w.' . $params['order_by'], $params['order_type'] ?? 'ASC');
        } else {
            $this->db->orderBy('w.warehouse_id', 'DESC');
        }

        $this->db->join('eudr_factories f', 'f.factory_id = w.factory_id', 'LEFT');
        $records = $this->db->arrayBuilder()->paginate('eudr_warehouses w', $page, $cols);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = new Warehouse((int)$item['warehouse_id'], $item);
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
     * Find a warehouse by its ID.
     *
     * @param int $warehouse_id
     * @return Warehouse|null
     */
    public function findWarehouseOfId(int $warehouse_id): ?Warehouse
    {
        $this->db->where('w.warehouse_id', $warehouse_id);
        $this->db->where('w.deleted_by', 0);
        $this->db->join('eudr_factories f', 'f.factory_id = w.factory_id', 'LEFT');
        $record = $this->db->getOne('eudr_warehouses w', 'w.*, f.factory_name');
        if (empty($record)) {
            return null;
        }

        return new Warehouse((int)$record['warehouse_id'], $record);
    }

    /**
     * Find a warehouse by its ID with permission checks based on scope.
     *
     * @param int $warehouse_id
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return Warehouse|null
     */
    public function findWarehouseOfIdWithPermission(int $warehouse_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Warehouse
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'w');
        $this->db->where('w.warehouse_id', $warehouse_id);
        $this->db->join('eudr_factories f', 'f.factory_id = w.factory_id', 'LEFT');
        $record = $this->db->getOne('eudr_warehouses w', 'w.*, f.factory_name');
        if (empty($record)) {
            return null;
        }

        return new Warehouse((int)$record['warehouse_id'], $record);
    }

    /**
     * Find a warehouse by its code.
     *
     * @param string $code
     * @return Warehouse|null
     */
    public function findWarehouseOfCode(string $code): ?Warehouse
    {
        $this->db->where('w.warehouse_code', $code);
        $this->db->where('w.deleted_by', 0);
        $this->db->join('eudr_factories f', 'f.factory_id = w.factory_id', 'LEFT');
        $record = $this->db->getOne('eudr_warehouses w', 'w.*, f.factory_name');
        if (empty($record)) {
            return null;
        }

        return new Warehouse((int)$record['warehouse_id'], $record);
    }

    /**
     * Find a warehouse by its code with permission checks based on scope.
     *
     * @param string $code
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return Warehouse|null
     */
    public function findWarehouseOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Warehouse
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'w');
        $this->db->where('w.warehouse_code', $code);
        $this->db->join('eudr_factories f', 'f.factory_id = w.factory_id', 'LEFT');
        $record = $this->db->getOne('eudr_warehouses w', 'w.*, f.factory_name');
        if (empty($record)) {
            return null;
        }

        return new Warehouse((int)$record['warehouse_id'], $record);
    }

    /**
     * Generate a unique warehouse code.
     *
     * @return string
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = 'whse-' . date('ymd') . '-' . Utils::generateRandomString(8);
            $record = $this->findWarehouseOfCode($code);
            if (!$record) {
                break;
            }
        }

        return $code;
    }

    /**
     * Create a new warehouse.
     *
     * @param array $data
     * @return Warehouse|null
     */
    public function createWarehouse(array $data): ?Warehouse
    {
        $this->db->insert('eudr_warehouses', $data);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }

        return $this->findWarehouseOfId((int)$this->db->getInsertId());
    }

    /**
     * Update an existing warehouse.
     *
     * @param int $warehouse_id
     * @param array $data_update
     * @return Warehouse
     */
    public function updateWarehouse(int $warehouse_id, array $data_update): Warehouse
    {
        $this->db->where('warehouse_id', $warehouse_id);
        $this->db->update('eudr_warehouses', $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new WarehouseNotFoundException("Warehouse not found with ID: $warehouse_id");
        }

        return $this->findWarehouseOfId($warehouse_id);
    }

    /**
     * Update an existing warehouse with permission checks based on scope.
     *
     * @param int $warehouse_id
     * @param array $data_update
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return Warehouse
     */
    public function updateWarehouseWithPermission(int $warehouse_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): Warehouse
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, '');
        $this->db->where('warehouse_id', $warehouse_id);
        $this->db->update('eudr_warehouses', $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new WarehouseNotFoundException("Warehouse not found with ID: $warehouse_id");
        }

        return $this->findWarehouseOfId($warehouse_id);
    }

    /**
     * Delete a warehouse by its ID.
     *
     * @param int $warehouse_id
     * @param int $deleted_by
     * @return void
     */
    public function deleteWarehouse(int $warehouse_id, int $deleted_by): void
    {
        $this->db->where('warehouse_id', $warehouse_id);
        $this->db->update('eudr_warehouses', [
            'deleted_by' => $deleted_by,
            'deleted_at' => date('Y-m-d H:i:s', time()),
        ]);
    }

    /**
     * Delete a warehouse by its ID with permission checks based on scope.
     *
     * @param int $warehouse_id
     * @param int $deleted_by
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return void
     */
    public function deleteWarehouseWithPermission(int $warehouse_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, '');
        $this->db->where('warehouse_id', $warehouse_id);
        $this->db->update('eudr_warehouses', [
            'deleted_by' => $deleted_by,
            'deleted_at' => date('Y-m-d H:i:s', time()),
        ]);
    }
}
