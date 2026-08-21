<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\ProductionOven;

use App\Application\Utility\CurrentUserContext;
use App\Application\Utility\Utils;
use App\Domain\ProductionOven\ProductionOven;
use App\Domain\ProductionOven\ProductionOvenNotFoundException;
use App\Domain\ProductionOven\ProductionOvenRepository;

class InDatabaseProductionOvenRepository implements ProductionOvenRepository
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
     * @param int|null $companyIdParam
     * @param string $alias
     * @return void
     */
    private function scopeWhere(string $scope, int $authUserId, int $companyId, ?int $companyIdParam = null, string $alias = 'po'): void
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
     * Find all production ovens with optional filtering and pagination.
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
        $ovenCode = $params['oven_code'] ?? '';
        $ovenName = $params['oven_name'] ?? '';
        $status = $params['status'] ?? 'all';
        $factoryId = $params['factory_id'] ?? 0;
        $companyIdParam = $company_id_param ?? 0;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'po');
        if (!empty($search)) {
            $this->db->where('(po.oven_code LIKE ? OR po.oven_name LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($ovenCode)) {
            $this->db->where('po.oven_code', $ovenCode);
        }
        if (!empty($ovenName)) {
            $this->db->where('po.oven_name', $ovenName);
        }
        if ($status !== 'all') {
            $this->db->where('po.status', $status);
        }
        if (!empty($factoryId)) {
            $this->db->where('po.factory_id', $factoryId);
        }
        $total_records = (int)$this->db->getValue('eudr_production_ovens po', 'count(*)');

        $this->db->pageLimit = $page_limit;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'po');
        if (!empty($search)) {
            $this->db->where('(po.oven_code LIKE ? OR po.oven_name LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($ovenCode)) {
            $this->db->where('po.oven_code', $ovenCode);
        }
        if (!empty($ovenName)) {
            $this->db->where('po.oven_name', $ovenName);
        }
        if ($status !== 'all') {
            $this->db->where('po.status', $status);
        }
        if (!empty($factoryId)) {
            $this->db->where('po.factory_id', $factoryId);
        }

        $cols = 'po.*, f.factory_name';

        if (!empty($params['order_by'])) {
            $this->db->orderBy('po.' . $params['order_by'], $params['order_type'] ?? 'ASC');
        } else {
            $this->db->orderBy('po.oven_id', 'DESC');
        }

        $this->db->join('eudr_factories f', 'f.factory_id = po.factory_id', 'LEFT');
        $records = $this->db->arraybuilder()->paginate('eudr_production_ovens po', $page, $cols);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = new ProductionOven($item['oven_id'], $item);
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

    public function findAllDryingRuns(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $companyIdParam = $company_id_param ?? 0;
        $productionOrderId = $params['production_order_id'] ?? 0;
        $factoryId = $params['factory_id'] ?? 0;
        $ovenId = $params['oven_id'] ?? 0;
        $status = $params['status'] ?? 'all';

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'dr');
        if (!empty($productionOrderId)) {
            $this->db->where('dr.production_order_id', (int)$productionOrderId);
        }
        if (!empty($factoryId)) {
            $this->db->where('dr.factory_id', (int)$factoryId);
        }
        if (!empty($ovenId)) {
            $this->db->where('dr.oven_id', (int)$ovenId);
        }
        if ($status !== 'all') {
            $this->db->where('dr.status', $status);
        }
        $total_records = (int)$this->db->getValue('eudr_production_drying_runs dr', 'count(*)');

        $this->db->pageLimit = $page_limit;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'dr');
        if (!empty($productionOrderId)) {
            $this->db->where('dr.production_order_id', (int)$productionOrderId);
        }
        if (!empty($factoryId)) {
            $this->db->where('dr.factory_id', (int)$factoryId);
        }
        if (!empty($ovenId)) {
            $this->db->where('dr.oven_id', (int)$ovenId);
        }
        if ($status !== 'all') {
            $this->db->where('dr.status', $status);
        }

        $cols = 'dr.*, o.oven_code, o.oven_name, po.production_order_code';
        $this->db->join('eudr_production_ovens o', 'o.oven_id = dr.oven_id', 'LEFT');
        $this->db->join('eudr_production_orders po', 'po.production_order_id = dr.production_order_id', 'LEFT');
        $this->db->orderBy('dr.drying_run_id', 'DESC');
        $records = $this->db->arraybuilder()->paginate('eudr_production_drying_runs dr', $page, $cols);

        $items = [];
        $runIds = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $item['quality_details'] = [];
                $items[] = $item;
                $runIds[] = (int)($item['drying_run_id'] ?? 0);
            }
        }

        if (!empty($runIds)) {
            $this->db->where('qd.drying_run_id', $runIds, 'IN');
            $this->db->where('qd.deleted_by', 0);
            $this->db->orderBy('qd.drying_quality_detail_id', 'ASC');
            $this->db->join('eudr_production_grades g', 'g.grade_id = qd.grade_id', 'LEFT');
            $qualityRows = $this->db->arraybuilder()->get('eudr_production_drying_run_quality_details qd', null, 'qd.*, g.grade_code, g.name AS grade_name') ?? [];

            $qualityMap = [];
            foreach ($qualityRows as $row) {
                $rid = (int)($row['drying_run_id'] ?? 0);
                $qualityMap[$rid][] = $row;
            }
            foreach ($items as $index => $run) {
                $rid = (int)($run['drying_run_id'] ?? 0);
                $items[$index]['quality_details'] = $qualityMap[$rid] ?? [];
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
     * Find a production oven by its ID.
     *
     * @param int $oven_id
     * @return ProductionOven|null
     */
    public function findProductionOvenOfId(int $oven_id): ?ProductionOven
    {
        $this->db->where('po.oven_id', $oven_id);
        $this->db->where('po.deleted_by', 0);
        $this->db->join('eudr_factories f', 'f.factory_id = po.factory_id', 'LEFT');
        $production_oven = $this->db->getOne('eudr_production_ovens po', 'po.*, f.factory_name');
        if (empty($production_oven)) {
            return null;
        }
        return new ProductionOven($production_oven['oven_id'], $production_oven);
    }

    /**
     * Find a production oven by its ID with permission check.
     *
     * @param int $oven_id
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return ProductionOven|null
     */
    public function findProductionOvenOfIdWithPermission(int $oven_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductionOven
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'po');
        $this->db->where('po.oven_id', $oven_id);
        $this->db->join('eudr_factories f', 'f.factory_id = po.factory_id', 'LEFT');
        $production_oven = $this->db->getOne('eudr_production_ovens po', 'po.*, f.factory_name');
        if (empty($production_oven)) {
            return null;
        }
        return new ProductionOven($production_oven['oven_id'], $production_oven);
    }

    /**
     * Find a production oven by its code.
     *
     * @param string $code
     * @return ProductionOven|null
     */
    public function findProductionOvenOfCode(string $code): ?ProductionOven
    {
        $this->db->where('po.oven_code', $code);
        $this->db->where('po.deleted_by', 0);
        $this->db->join('eudr_factories f', 'f.factory_id = po.factory_id', 'LEFT');
        $production_oven = $this->db->getOne('eudr_production_ovens po', 'po.*, f.factory_name');
        if (empty($production_oven)) {
            return null;
        }
        return new ProductionOven($production_oven['oven_id'], $production_oven);
    }

    /**
     * Find a production oven by its code with permission check.
     *
     * @param string $code
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return ProductionOven|null
     */
    public function findProductionOvenOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductionOven
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'po');
        $this->db->where('po.oven_code', $code);
        $this->db->join('eudr_factories f', 'f.factory_id = po.factory_id', 'LEFT');
        $production_oven = $this->db->getOne('eudr_production_ovens po', 'po.*, f.factory_name');
        if (empty($production_oven)) {
            return null;
        }
        return new ProductionOven($production_oven['oven_id'], $production_oven);
    }

    /**
     * Generate a unique code for a production oven.
     *
     * @return string
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = 'povn-' . date('ymd') . '-' . Utils::generateRandomString(8);
            $production_oven = $this->findProductionOvenOfCode($code);
            if (!$production_oven) {
                break;
            }
        }
        return $code;
    }

    /**
     * Create a new production oven with the given data.
     *
     * @param array $data
     * @return ProductionOven|null
     */
    public function createProductionOven(array $data): ?ProductionOven
    {
        $this->db->insert('eudr_production_ovens', $data);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }

        $oven_id = $this->db->getInsertId();
        return $this->findProductionOvenOfId($oven_id);
    }

    /**
     * Update a production oven with the given data.
     *
     * @param int $oven_id
     * @param array $data_update
     * @return ProductionOven
     * @throws ProductionOvenNotFoundException
     */
    public function updateProductionOven(int $oven_id, array $data_update): ProductionOven
    {
        $this->db->where('oven_id', $oven_id);
        $this->db->update('eudr_production_ovens', $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new ProductionOvenNotFoundException("Production Oven not found with ID: $oven_id");
        }
        return $this->findProductionOvenOfId($oven_id);
    }

    /**
     * Update a production oven with the given data and permission check.
     *
     * @param int $oven_id
     * @param array $data_update
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return ProductionOven
     * @throws ProductionOvenNotFoundException
     */
    public function updateProductionOvenWithPermission(int $oven_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ProductionOven
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, '');
        $this->db->where('oven_id', $oven_id);
        $this->db->update('eudr_production_ovens', $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new ProductionOvenNotFoundException("Production Oven not found with ID: $oven_id");
        }
        return $this->findProductionOvenOfId($oven_id);
    }

    /**
     * Delete a production oven.
     *
     * @param int $oven_id
     * @param int $deleted_by
     * @return void
     */
    public function deleteProductionOven(int $oven_id, int $deleted_by): void
    {
        $this->db->where('oven_id', $oven_id);
        $this->db->update('eudr_production_ovens', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

    /**
     * Delete a production oven with permission check.
     *
     * @param int $oven_id
     * @param int $deleted_by
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return void
     */
    public function deleteProductionOvenWithPermission(int $oven_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, '');
        $this->db->where('oven_id', $oven_id);
        $this->db->update('eudr_production_ovens', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

    /**
     * Find one hanging run by id with scope permission.
     * 
     * @param int $hanging_run_id
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return array|null
     */
    public function findHangingRunOfIdWithPermission(int $hanging_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'hr');
        $this->db->where('hr.hanging_run_id', $hanging_run_id);
        $record = $this->db->getOne('eudr_production_hanging_runs hr', 'hr.*');

        return !empty($record) ? $record : null;
    }

    /**
     * Find one drying run by id with scope permission.
     * 
     * @param int $drying_run_id
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return array|null
     */
    public function findDryingRunOfIdWithPermission(int $drying_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'dr');
        $this->db->where('dr.drying_run_id', $drying_run_id);
        $record = $this->db->getOne('eudr_production_drying_runs dr', 'dr.*');

        return !empty($record) ? $record : null;
    }

    public function getDryingRunDetailWithPermission(int $drying_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array
    {
        $run = $this->findDryingRunOfIdWithPermission($drying_run_id, $auth_user_id, $scope, $company_id, $company_id_param);
        if (empty($run)) {
            return null;
        }

        $this->db->where('dr.drying_run_id', $drying_run_id);
        $this->db->join('eudr_production_ovens o', 'o.oven_id = dr.oven_id', 'LEFT');
        $this->db->join('eudr_production_hanging_runs hr', 'hr.hanging_run_id = dr.hanging_run_id', 'LEFT');
        $runDetail = $this->db->getOne(
            'eudr_production_drying_runs dr',
            'dr.*, o.oven_code, o.oven_name, hr.hanging_run_id AS source_hanging_run_id, hr.status AS source_hanging_run_status'
        );
        if (empty($runDetail)) {
            return null;
        }

        $this->db->where('qd.drying_run_id', $drying_run_id);
        $this->db->where('qd.deleted_by', 0);
        $this->db->orderBy('qd.quality_type', 'ASC');
        $this->db->join('eudr_production_grades g', 'g.grade_id = qd.grade_id', 'LEFT');
        $qualityDetails = $this->db->get('eudr_production_drying_run_quality_details qd', null, 'qd.*, g.grade_code, g.name AS grade_name') ?? [];

        $runDetail['quality_details'] = $qualityDetails;

        return $runDetail;
    }

    /**
     * Create a drying run from a hanging run.
     * 
     * @param array $data
     * @return array|null
     */
    public function createDryingRunFromHanging(array $data): ?array
    {
        $hangingRunId = (int)($data['hanging_run_id'] ?? 0);
        $ovenId = (int)($data['oven_id'] ?? 0);
        $notes = $data['notes'] ?? null;
        $updatedBy = (int)($data['updated_by'] ?? 0);

        if ($hangingRunId <= 0 || $ovenId <= 0 || $updatedBy <= 0) {
            return null;
        }

        $this->db->startTransaction();

        $this->db->where('hanging_run_id', $hangingRunId);
        $this->db->where('deleted_by', 0);
        $hangingRun = $this->db->getOne('eudr_production_hanging_runs', '*');
        if (empty($hangingRun)) {
            $this->db->rollback();
            return null;
        }
        if ((string)($hangingRun['status'] ?? '') !== 'completed') {
            $this->db->rollback();
            return null;
        }

        $this->db->where('oven_id', $ovenId);
        $this->db->where('deleted_by', 0);
        $oven = $this->db->getOne('eudr_production_ovens', 'oven_id, factory_id, company_id, status');
        if (empty($oven)) {
            $this->db->rollback();
            return null;
        }

        if ((int)($oven['factory_id'] ?? 0) !== (int)($hangingRun['factory_id'] ?? 0) || (int)($oven['company_id'] ?? 0) !== (int)($hangingRun['company_id'] ?? 0)) {
            $this->db->rollback();
            return null;
        }
        if ((string)($oven['status'] ?? '') !== 'available') {
            $this->db->rollback();
            return null;
        }

        $this->db->where('hanging_run_id', $hangingRunId);
        $this->db->where('deleted_by', 0);
        $hangingDetails = $this->db->get('eudr_production_hanging_run_quality_details', null, 'quality_type, grade_id, output_sheet_count, notes');
        if (empty($hangingDetails)) {
            $this->db->rollback();
            return null;
        }

        $now = date('Y-m-d H:i:s', time());

        $this->db->where('hanging_run_id', $hangingRunId);
        $this->db->where('deleted_by', 0);
        $existingDryingRun = $this->db->getOne('eudr_production_drying_runs', '*');
        if (!empty($existingDryingRun)) {
            $this->db->rollback();
            return null;
        }

        $dryingData = [
            'production_order_id' => (int)($hangingRun['production_order_id'] ?? 0),
            'hanging_run_id' => $hangingRunId,
            'company_id' => (int)($hangingRun['company_id'] ?? 0),
            'factory_id' => (int)($hangingRun['factory_id'] ?? 0),
            'oven_id' => $ovenId,
            'started_at' => $now,
            'ended_at' => null,
            'status' => 'in_progress',
            'notes' => $notes,
            'created_by' => $updatedBy,
            'created_at' => $now,
            'updated_by' => 0,
            'updated_at' => null,
            'deleted_by' => 0,
            'deleted_at' => null,
        ];

        $this->db->insert('eudr_production_drying_runs', $dryingData);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }
        $dryingRunId = (int)$this->db->getInsertId();

        $qualityDetails = [];
        foreach ($hangingDetails as $detail) {
            $qualityType = (string)($detail['quality_type'] ?? 'NA');
            $gradeId = (int)($detail['grade_id'] ?? 0);
            $inputSheetCount = (int)($detail['output_sheet_count'] ?? 0);
            $detailNotes = $detail['notes'] ?? null;

            $this->db->insert('eudr_production_drying_run_quality_details', [
                'drying_run_id' => $dryingRunId,
                'grade_id' => $gradeId,
                'quality_type' => $qualityType,
                'input_sheet_count' => $inputSheetCount,
                'output_sheet_count' => 0,
                'notes' => $detailNotes,
                'created_by' => $updatedBy,
                'created_at' => $now,
                'updated_by' => 0,
                'updated_at' => null,
                'deleted_by' => 0,
                'deleted_at' => null,
            ]);
            if ($this->db->getLastErrno() !== 0) {
                $this->db->rollback();
                return null;
            }

            $qualityDetails[] = [
                'quality_type' => $qualityType,
                'grade_id' => $gradeId,
                'input_sheet_count' => $inputSheetCount,
            ];
        }

        $this->db->where('oven_id', $ovenId);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_production_ovens', [
            'status' => 'in_use',
            'updated_by' => $updatedBy,
            'updated_at' => $now,
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->commit();

        return [
            'drying_run_id' => $dryingRunId,
            'hanging_run_id' => $hangingRunId,
            'oven_id' => $ovenId,
            'status' => 'in_progress',
            'started_at' => $now,
            'quality_details' => $qualityDetails,
        ];
    }

    /**
     * Update drying run quality details.
     * 
     * @param array $data
     * @return array|null
     */
    public function updateDryingRunQualityDetails(array $data): ?array
    {
        $dryingRunId = (int)($data['drying_run_id'] ?? 0);
        $details = $data['details'] ?? [];
        $startedAt = !empty($data['started_at']) ? (string)$data['started_at'] : null;
        $endedAt = !empty($data['ended_at']) ? (string)$data['ended_at'] : null;
        $updatedBy = (int)($data['updated_by'] ?? 0);

        if ($dryingRunId <= 0 || !is_array($details) || count($details) === 0 || $updatedBy <= 0) {
            return null;
        }

        $this->db->where('drying_run_id', $dryingRunId);
        $this->db->where('deleted_by', 0);
        $dryingRun = $this->db->getOne('eudr_production_drying_runs', '*');
        if (empty($dryingRun)) {
            return null;
        }

        $now = date('Y-m-d H:i:s', time());
        $runEndedAt = $endedAt ?? $now;
        $updatedDetails = [];
        $allowedQualityTypes = Utils::getAllowedQualityTypes();

        $this->db->startTransaction();

        foreach ($details as $item) {
            $gradeId = (int)($item['grade_id'] ?? 0);
            $qualityType = (string)($item['quality_type'] ?? '');
            $outputSheetCount = (int)($item['output_sheet_count'] ?? -1);
            $notes = $item['notes'] ?? null;

            if (!in_array($qualityType, $allowedQualityTypes, true) || $outputSheetCount < 0) {
                $this->db->rollback();
                return null;
            }

            $this->db->where('drying_run_id', $dryingRunId);
            $this->db->where('quality_type', $qualityType);
            $this->db->where('deleted_by', 0);
            $existing = $this->db->getOne('eudr_production_drying_run_quality_details', '*');
            if (empty($existing)) {
                $this->db->rollback();
                return null;
            }
            if ($outputSheetCount > (int)($existing['input_sheet_count'] ?? 0)) {
                $this->db->rollback();
                return null;
            }

            $this->db->where('drying_quality_detail_id', (int)$existing['drying_quality_detail_id']);
            $this->db->update('eudr_production_drying_run_quality_details', [
                'grade_id' => $gradeId,
                'output_sheet_count' => $outputSheetCount,
                'notes' => $notes,
                'updated_by' => $updatedBy,
                'updated_at' => $now,
            ]);
            if ($this->db->getLastErrno() !== 0) {
                $this->db->rollback();
                return null;
            }

            $updatedDetails[] = [
                'quality_type' => $qualityType,
                'output_sheet_count' => $outputSheetCount,
                'notes' => $notes,
                'grade_id' => $gradeId,
            ];
        }

        $runUpdateData = [
            'status' => 'completed',
            'ended_at' => $runEndedAt,
            'updated_by' => $updatedBy,
            'updated_at' => $now,
        ];
        if ($startedAt !== null) {
            $runUpdateData['started_at'] = $startedAt;
        }
        $this->db->where('drying_run_id', $dryingRunId);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_production_drying_runs', $runUpdateData);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->where('oven_id', (int)($dryingRun['oven_id'] ?? 0));
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_production_ovens', [
            'status' => 'available',
            'updated_by' => $updatedBy,
            'updated_at' => $now,
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->commit();

        return [
            'drying_run_id' => $dryingRunId,
            'status' => 'completed',
            'ended_at' => $runEndedAt,
            'quality_details' => $updatedDetails,
        ];
    }
}
