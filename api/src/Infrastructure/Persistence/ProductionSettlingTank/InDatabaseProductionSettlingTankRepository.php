<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\ProductionSettlingTank;

use App\Application\Utility\CurrentUserContext;
use App\Application\Utility\Utils;
use App\Domain\ProductionSettlingTank\ProductionSettlingTank;
use App\Domain\ProductionSettlingTank\ProductionSettlingTankNotFoundException;
use App\Domain\ProductionSettlingTank\ProductionSettlingTankRepository;

class InDatabaseProductionSettlingTankRepository implements ProductionSettlingTankRepository
{
    /**
     * @var \MysqliDb
     */
    private $db;

    /**
     * @var CurrentUserContext
     */
    private $currentUser;

    /**
     * InDatabaseProductionSettlingTankRepository constructor.
     *
     * @param \MysqliDb $db
     * @param CurrentUserContext $currentUserContext
     */

    public function __construct(\MysqliDb $db, CurrentUserContext $currentUserContext)
    {
        $this->db = $db;
        $this->currentUser = $currentUserContext;
    }
    
    /**
     * Apply scope-based filtering to the database query.
     *
     * @param string $scope The scope of the query (e.g., 'self', 'own', 'all').
     * @param int $authUserId The ID of the authenticated user.
     * @param int $companyId The ID of the company associated with the user.
     * @param int|null $companyIdParam An optional company ID parameter for 'all' scope.
     * @param string $alias An optional table alias for the query.
     */
    private function scopeWhere(string $scope, int $authUserId, int $companyId, ?int $companyIdParam = null, string $alias = 'st'): void
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

    /*
    * Find all production settling tanks based on the given parameters and permissions.
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
        $factory_id = $params['factory_id'] ?? null;
        $status = $params['status'] ?? 'all';
        $companyIdParam = $company_id_param ?? 0;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'st');
        if (!empty($search)) {
            $this->db->where('(st.settling_tank_name LIKE ? OR st.settling_tank_code LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($factory_id)) {
            $this->db->where('st.factory_id', $factory_id);
        }
        if ($status !== 'all') {
            $this->db->where('st.status', $status);
        }
        $total_records = (int)$this->db->getValue('eudr_production_settling_tanks st', 'count(*)');

        $this->db->pageLimit = $page_limit;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'st');
        if (!empty($search)) {
            $this->db->where('(st.settling_tank_name LIKE ? OR st.settling_tank_code LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($factory_id)) {
            $this->db->where('st.factory_id', $factory_id);
        }
        if ($status !== 'all') {
            $this->db->where('st.status', $status);
        }

        $cols = 'st.*, f.factory_name';

        if (!empty($params['order_by'])) {
            $this->db->orderBy('st.' . $params['order_by'], $params['order_type'] ?? 'ASC');
        } else {
            $this->db->orderBy('st.settling_tank_id', 'DESC');
        }
        $this->db->join('eudr_factories f', 'f.factory_id = st.factory_id', 'LEFT');
        $records = $this->db->arrayBuilder()->paginate('eudr_production_settling_tanks st', $page, $cols);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = new ProductionSettlingTank($item['settling_tank_id'], $item);
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
     * Find a production settling tank by its ID.
     *
     * @param int $settling_tank_id
     * @return ProductionSettlingTank|null
     */
    public function findProductionSettlingTankOfId(int $settling_tank_id): ?ProductionSettlingTank
    {
        $this->db->where('st.settling_tank_id', $settling_tank_id);
        $this->db->where('st.deleted_by', 0);
        $this->db->join('eudr_factories f', 'f.factory_id = st.factory_id', 'LEFT');
        $tank = $this->db->getOne('eudr_production_settling_tanks st', 'st.*, f.factory_name');
        if (empty($tank)) {
            return null;
        }
        return new ProductionSettlingTank($tank['settling_tank_id'], $tank);
    }

    /**
     * Find a production settling tank by its ID with permission checks.
     *
     * @param int $settling_tank_id
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return ProductionSettlingTank|null
     */
    public function findProductionSettlingTankOfIdWithPermission(int $settling_tank_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductionSettlingTank
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'st');
        $this->db->where('st.settling_tank_id', $settling_tank_id);
        $this->db->join('eudr_factories f', 'f.factory_id = st.factory_id', 'LEFT');
        $tank = $this->db->getOne('eudr_production_settling_tanks st', 'st.*, f.factory_name');
        if (empty($tank)) {
            return null;
        }
        return new ProductionSettlingTank($tank['settling_tank_id'], $tank);
    }

    /**
     * Find a production settling tank by its code.
     *
     * @param string $code
     * @return ProductionSettlingTank|null
     */
    public function findProductionSettlingTankOfCode(string $code): ?ProductionSettlingTank
    {
        $this->db->where('st.settling_tank_code', $code);
        $this->db->where('st.deleted_by', 0);
        $this->db->join('eudr_factories f', 'f.factory_id = st.factory_id', 'LEFT');
        $tank = $this->db->getOne('eudr_production_settling_tanks st', 'st.*, f.factory_name');
        if (empty($tank)) {
            return null;
        }
        return new ProductionSettlingTank($tank['settling_tank_id'], $tank);
    }

    /**
     * Find a production settling tank by its code with permission checks.
     *
     * @param string $code
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return ProductionSettlingTank|null
     */
    public function findProductionSettlingTankOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductionSettlingTank
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'st');
        $this->db->where('st.settling_tank_code', $code);
        $this->db->join('eudr_factories f', 'f.factory_id = st.factory_id', 'LEFT');
        $tank = $this->db->getOne('eudr_production_settling_tanks st', 'st.*, f.factory_name');
        if (empty($tank)) {
            return null;
        }
        return new ProductionSettlingTank($tank['settling_tank_id'], $tank);
    }

    /**
     * Generate a unique code for a production settling tank.
     *
     * @return string
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = 'stlt-' . date('ymd') . '-' . Utils::generateRandomString(8);
            $tank = $this->findProductionSettlingTankOfCode($code);
            if (!$tank) {
                break;
            }
        }
        return $code;
    }

    /**
     * Create a new production settling tank with the given data.
     *
     * @param array $data
     * @return ProductionSettlingTank|null
     */
    public function createProductionSettlingTank(array $data): ?ProductionSettlingTank
    {
        $this->db->insert('eudr_production_settling_tanks', $data);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }

        $tank_id = $this->db->getInsertId();
        return $this->findProductionSettlingTankOfId($tank_id);
    }

    /**
     * Update a production settling tank with the given data.
     *
     * @param int $settling_tank_id
     * @param array $data_update
     * @return ProductionSettlingTank
     * @throws ProductionSettlingTankNotFoundException
     */
    public function updateProductionSettlingTank(int $settling_tank_id, array $data_update): ProductionSettlingTank
    {
        $this->db->where('settling_tank_id', $settling_tank_id);
        $this->db->update('eudr_production_settling_tanks', $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new ProductionSettlingTankNotFoundException('Production Settling Tank not found with ID: ' . $settling_tank_id);
        }
        return $this->findProductionSettlingTankOfId($settling_tank_id);
    }

    /**
     * Update a production settling tank with the given data and permission checks.
     *
     * @param int $settling_tank_id
     * @param array $data_update
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return ProductionSettlingTank
     * @throws ProductionSettlingTankNotFoundException
     */
    public function updateProductionSettlingTankWithPermission(int $settling_tank_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ProductionSettlingTank
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, '');
        $this->db->where('settling_tank_id', $settling_tank_id);
        $this->db->update('eudr_production_settling_tanks', $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new ProductionSettlingTankNotFoundException('Production Settling Tank not found with ID: ' . $settling_tank_id);
        }

        return $this->findProductionSettlingTankOfId($settling_tank_id);
    }

    /**
     * Delete a production settling tank.
     *
     * @param int $settling_tank_id
     * @param int $deleted_by
     * @return void
     */
    public function deleteProductionSettlingTank(int $settling_tank_id, int $deleted_by): void
    {
        $this->db->where('settling_tank_id', $settling_tank_id);
        $this->db->update('eudr_production_settling_tanks', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

    /**
     * Delete a production settling tank with permission checks.
     *
     * @param int $settling_tank_id
     * @param int $deleted_by
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return void
     */
    public function deleteProductionSettlingTankWithPermission(int $settling_tank_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, '');
        $this->db->where('settling_tank_id', $settling_tank_id);
        $this->db->update('eudr_production_settling_tanks', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }
}
