<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\RawMaterialTank;

use App\Application\Utility\CurrentUserContext;
use App\Domain\RawMaterialTank\RawMaterialTank;
use App\Domain\RawMaterialTank\RawMaterialTankNotFoundException;
use App\Domain\RawMaterialTank\RawMaterialTankRepository;
use App\Application\Utility\Utils;

class InDatabaseRawMaterialTankRepository implements RawMaterialTankRepository
{
    /**
     * @var MysqliDb
     */
    private $db;

    /**
     * @var CurrentUserContext
     */
    private $currentUser;

    /**
     * InDatabaseRawMaterialTankRepository constructor.
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
     * Apply scope-based filtering (self/own/all).
     */
    private function scopeWhere(string $scope, int $authUserId, int $companyId, ?int $companyIdParam = null, string $alias = 't'): void
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
     * {@inheritdoc}
     */
    public function findAll($params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $search = $params['search'] ?? '';
        $factory_id = $params['factory_id'] ?? null;
        $tank_type = $params['tank_type'] ?? 'all';
        $status = $params['status'] ?? 'all';
        $companyIdParam = $company_id_param ?? 0;

       // Count total records
        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 't');
        if (!empty($search)) {
            $this->db->where('(t.raw_material_tank_name LIKE ? OR t.raw_material_tank_code LIKE ?)', ["%$search%", "%$search%"]); 
        }
        if (!empty($factory_id)) {
            $this->db->where('t.factory_id', $factory_id);
        }
        if ($tank_type !== 'all') {
            $this->db->where('t.tank_type', $tank_type);
        }
        if ($status !== 'all') {
            $this->db->where('t.status', $status);
        }
        $total_records = (int)$this->db->getValue("eudr_tanks_raw_material t", "count(*)");


        // Set pagination
        $this->db->pageLimit = $page_limit;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 't');
        if (!empty($search)) {
            $this->db->where('(t.raw_material_tank_name LIKE ? OR t.raw_material_tank_code LIKE ?)', ["%$search%", "%$search%"]); 
        }
        if (!empty($factory_id)) {
            $this->db->where('t.factory_id', $factory_id);
        }
        if ($tank_type !== 'all') {
            $this->db->where('t.tank_type', $tank_type);
        }
        if ($status !== 'all') {
            $this->db->where('t.status', $status);
        }

        $cols = "t.*, f.factory_name";

        if (!empty($params['order_by'])) {
            $this->db->orderBy('t.'.$params['order_by'], $params['order_type']??'ASC');
        } else {
            $this->db->orderBy("t.raw_material_tank_id", "DESC");
        }
        $this->db->join("eudr_factories f", "f.factory_id = t.factory_id", "LEFT");
        $records = $this->db->arrayBuilder()->paginate("eudr_tanks_raw_material t", $page, $cols);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = new RawMaterialTank($item['raw_material_tank_id'], $item);
            }
        }

        $return_data = [
            "current_page" => $page,
            "total_pages" => $this->db->totalPages,
            "total_records" => $total_records,
            "page_limit" => $this->db->pageLimit,
            "records" => $items,
        ];

        return $return_data;
    }

    /**
     * {@inheritdoc}
     */
    public function findRawMaterialTankOfId(int $raw_material_tank_id): ?RawMaterialTank
    {
        $this->db->where("t.raw_material_tank_id", $raw_material_tank_id);
        $this->db->where("t.deleted_by", 0);
        $this->db->join("eudr_factories f", "f.factory_id = t.factory_id", "LEFT");
        $tank = $this->db->getOne("eudr_tanks_raw_material t", "t.*, f.factory_name");
        if (empty($tank)) {
            return null;
        }
        return new RawMaterialTank($tank['raw_material_tank_id'], $tank);
    }

    /**
     * {@inheritdoc}
     */
    public function findRawMaterialTankOfIdWithPermission(int $raw_material_tank_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?RawMaterialTank
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 't');
        $this->db->where('t.raw_material_tank_id', $raw_material_tank_id);
        $this->db->join('eudr_factories f', 'f.factory_id = t.factory_id', 'LEFT');

        $tank = $this->db->getOne('eudr_tanks_raw_material t', 't.*, f.factory_name');
        if (empty($tank)) {
            return null;
        }

        return new RawMaterialTank($tank['raw_material_tank_id'], $tank);
    }

    /**
     * {@inheritdoc}
     */
    public function findRawMaterialTankOfCode(string $code): ?RawMaterialTank
    {
        $this->db->where("t.raw_material_tank_code", $code);
        $this->db->where("t.deleted_by", 0);
        $this->db->join("eudr_factories f", "f.factory_id = t.factory_id", "LEFT");
        $tank = $this->db->getOne("eudr_tanks_raw_material t", "t.*, f.factory_name");
        if (empty($tank)) {
            return null;
        }
        return new RawMaterialTank($tank['raw_material_tank_id'], $tank);
    }

    /**
     * {@inheritdoc}
     */
    public function findRawMaterialTankOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?RawMaterialTank
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 't');
        $this->db->where('t.raw_material_tank_code', $code);
        $this->db->join('eudr_factories f', 'f.factory_id = t.factory_id', 'LEFT');

        $tank = $this->db->getOne('eudr_tanks_raw_material t', 't.*, f.factory_name');
        if (empty($tank)) {
            return null;
        }

        return new RawMaterialTank($tank['raw_material_tank_id'], $tank);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = "rmtk-".date("ymd").'-'.Utils::generateRandomString(8);
            $tank = $this->findRawMaterialTankOfCode($code);
            if (!$tank) {
                break;
            }
        }
        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function createRawMaterialTank(array $data): ?RawMaterialTank
    {
        $this->db->insert("eudr_tanks_raw_material", $data);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }

        $tank_id = $this->db->getInsertId();

        return $this->findRawMaterialTankOfId($tank_id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateRawMaterialTank(int $tank_id, array $data_update): RawMaterialTank
    {
        $this->db->where("raw_material_tank_id", $tank_id);
        $this->db->update("eudr_tanks_raw_material", $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new RawMaterialTankNotFoundException("Raw Material Tank not found with ID: $tank_id");
        }
        return $this->findRawMaterialTankOfId($tank_id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateRawMaterialTankWithPermission(int $raw_material_tank_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): RawMaterialTank
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, '');
        $this->db->where('raw_material_tank_id', $raw_material_tank_id);
        $this->db->update('eudr_tanks_raw_material', $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new RawMaterialTankNotFoundException("Raw Material Tank not found with ID: $raw_material_tank_id");
        }

        return $this->findRawMaterialTankOfId($raw_material_tank_id);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteRawMaterialTank(int $tank_id, int $deleted_by): void
    {
        $this->db->where("raw_material_tank_id", $tank_id);
        $this->db->update('eudr_tanks_raw_material', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteRawMaterialTankWithPermission(int $raw_material_tank_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, '');
        $this->db->where('raw_material_tank_id', $raw_material_tank_id);
        $this->db->update('eudr_tanks_raw_material', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

    /**
     * {@inheritdoc}
     */
    public function getHistoryOfRawMaterialTank(int $raw_material_tank_id, array $params, ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $action_type = $params['action_type'] ?? 'all';
        $rubber_type = $params['rubber_type'] ?? 'all';
        $companyIdParam = $company_id_param ?? 0;

        // Count total records
        $this->db->join('eudr_tanks_raw_material t', 't.raw_material_tank_id = rmt_history.raw_material_tank_id', 'LEFT');
        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 't');
        $this->db->where("rmt_history.raw_material_tank_id", $raw_material_tank_id);
        if ($action_type !== 'all') {
            $this->db->where("rmt_history.action_type", $action_type);
        }
        if ($rubber_type !== 'all') {
            $this->db->where("rmt_history.rubber_type", $rubber_type);
        }
        $total_records = (int)$this->db->getValue("eudr_tanks_raw_material_history rmt_history", "count(*)");

        // Set pagination
        $this->db->pageLimit = $page_limit;

        $this->db->join('eudr_tanks_raw_material t', 't.raw_material_tank_id = rmt_history.raw_material_tank_id', 'LEFT');
        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 't');
        $this->db->where("rmt_history.raw_material_tank_id", $raw_material_tank_id);
        if ($action_type !== 'all') {
            $this->db->where("rmt_history.action_type", $action_type);
        }
        if ($rubber_type !== 'all') {
            $this->db->where("rmt_history.rubber_type", $rubber_type);
        }

        $cols = "rmt_history.*";

        $this->db->orderBy("rmt_history.created_at", "DESC");
        $records = $this->db->arrayBuilder()->paginate("eudr_tanks_raw_material_history rmt_history", $page, $cols);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = $item;
            }
        }

        $return_data = [
            "current_page" => $page,
            "total_pages" => $this->db->totalPages,
            "total_records" => $total_records,
            "page_limit" => $this->db->pageLimit,
            "records" => $items,
        ];

        return $return_data;
    }

}
