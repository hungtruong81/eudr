<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\ProductionRoller;

use App\Application\Utility\CurrentUserContext;
use App\Application\Utility\Utils;
use App\Domain\ProductionRoller\ProductionRoller;
use App\Domain\ProductionRoller\ProductionRollerNotFoundException;
use App\Domain\ProductionRoller\ProductionRollerRepository;

class InDatabaseProductionRollerRepository implements ProductionRollerRepository
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
     * Apply scope-based filtering to the database query.
     *
     * @param string $scope The scope of the query (e.g., 'self', 'own', 'all').
     * @param int $authUserId The ID of the authenticated user.
     * @param int $companyId The ID of the company associated with the user.
     * @param int|null $companyIdParam An optional company ID parameter for 'all' scope.
     * @param string $alias An optional table alias for the query.
     */
    private function scopeWhere(string $scope, int $authUserId, int $companyId, ?int $companyIdParam = null, string $alias = 'pr'): void
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
     * Find all production rollers with optional filtering and pagination.
     *
     * @param array $params An array of parameters for filtering and pagination.
     * @param int|null $auth_user_id The ID of the authenticated user (optional).
     * @param string $scope The scope of the query (default is 'own').
     * @param int|null $company_id The ID of the company associated with the user (optional).
     * @param int|null $company_id_param An optional company ID parameter for 'all' scope.
     * @return array An array containing pagination info and a list of ProductionRoller objects.
     */
    public function findAll($params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $search = $params['search'] ?? '';
        $rollerCode = $params['roller_code'] ?? '';
        $rollerName = $params['roller_name'] ?? '';
        $status = $params['status'] ?? 'all';
        $factoryId = $params['factory_id'] ?? 0;
        $companyIdParam = $company_id_param ?? 0;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'pr');
        if (!empty($search)) {
            $this->db->where('(pr.roller_code LIKE ? OR pr.roller_name LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($rollerCode)) {
            $this->db->where('pr.roller_code', $rollerCode);
        }
        if (!empty($rollerName)) {
            $this->db->where('pr.roller_name', $rollerName);
        }
        if ($status !== 'all') {
            $this->db->where('pr.status', $status);
        }
        if (!empty($factoryId)) {
            $this->db->where('pr.factory_id', $factoryId);
        }
        $total_records = (int)$this->db->getValue('eudr_production_rollers pr', 'count(*)');

        $this->db->pageLimit = $page_limit;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'pr');
        if (!empty($search)) {
            $this->db->where('(pr.roller_code LIKE ? OR pr.roller_name LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($rollerCode)) {
            $this->db->where('pr.roller_code', $rollerCode);
        }
        if (!empty($rollerName)) {
            $this->db->where('pr.roller_name', $rollerName);
        }
        if ($status !== 'all') {
            $this->db->where('pr.status', $status);
        }
        if (!empty($factoryId)) {
            $this->db->where('pr.factory_id', $factoryId);
        }

        $cols = 'pr.*, f.factory_name';

        if (!empty($params['order_by'])) {
            $this->db->orderBy('pr.' . $params['order_by'], $params['order_type'] ?? 'ASC');
        } else {
            $this->db->orderBy('pr.roller_id', 'DESC');
        }

        $this->db->join('eudr_factories f', 'f.factory_id = pr.factory_id', 'LEFT');
        $records = $this->db->arraybuilder()->paginate('eudr_production_rollers pr', $page, $cols);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = new ProductionRoller($item['roller_id'], $item);
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

    public function findAllRollingRuns(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $companyIdParam = $company_id_param ?? 0;
        $productionOrderId = $params['production_order_id'] ?? 0;
        $factoryId = $params['factory_id'] ?? 0;
        $rollerId = $params['roller_id'] ?? 0;
        $status = $params['status'] ?? 'all';

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'rr');
        if (!empty($productionOrderId)) {
            $this->db->where('rr.production_order_id', (int)$productionOrderId);
        }
        if (!empty($factoryId)) {
            $this->db->where('rr.factory_id', (int)$factoryId);
        }
        if (!empty($rollerId)) {
            $this->db->where('rr.roller_id', (int)$rollerId);
        }
        if ($status !== 'all') {
            $this->db->where('rr.status', $status);
        }
        $total_records = (int)$this->db->getValue('eudr_production_rolling_runs rr', 'count(*)');

        $this->db->pageLimit = $page_limit;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'rr');
        if (!empty($productionOrderId)) {
            $this->db->where('rr.production_order_id', (int)$productionOrderId);
        }
        if (!empty($factoryId)) {
            $this->db->where('rr.factory_id', (int)$factoryId);
        }
        if (!empty($rollerId)) {
            $this->db->where('rr.roller_id', (int)$rollerId);
        }
        if ($status !== 'all') {
            $this->db->where('rr.status', $status);
        }

        $cols = 'rr.*, r.roller_code, r.roller_name, po.production_order_code';
        $this->db->join('eudr_production_rollers r', 'r.roller_id = rr.roller_id', 'LEFT');
        $this->db->join('eudr_production_orders po', 'po.production_order_id = rr.production_order_id', 'LEFT');
        $this->db->orderBy('rr.rolling_run_id', 'DESC');
        $records = $this->db->arraybuilder()->paginate('eudr_production_rolling_runs rr', $page, $cols);

        $items = [];
        $runIds = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $item['quality_details'] = [];
                $items[] = $item;
                $runIds[] = (int)($item['rolling_run_id'] ?? 0);
            }
        }

        if (!empty($runIds)) {
            $this->db->where('rolling_run_id', $runIds, 'IN');
            $this->db->where('deleted_by', 0);
            $this->db->orderBy('rolling_quality_detail_id', 'ASC');
            $qualityRows = $this->db->arraybuilder()->get('eudr_production_rolling_run_quality_details') ?? [];

            $qualityMap = [];
            foreach ($qualityRows as $row) {
                $rid = (int)($row['rolling_run_id'] ?? 0);
                $qualityMap[$rid][] = $row;
            }
            foreach ($items as $index => $run) {
                $rid = (int)($run['rolling_run_id'] ?? 0);
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
     * Find a production roller by its ID.
     *
     * @param int $roller_id
     * @return ProductionRoller|null
     */
    public function findProductionRollerOfId(int $roller_id): ?ProductionRoller
    {
        $this->db->where('pr.roller_id', $roller_id);
        $this->db->where('pr.deleted_by', 0);
        $this->db->join('eudr_factories f', 'f.factory_id = pr.factory_id', 'LEFT');
        $production_roller = $this->db->getOne('eudr_production_rollers pr', 'pr.*, f.factory_name');
        if (empty($production_roller)) {
            return null;
        }
        return new ProductionRoller($production_roller['roller_id'], $production_roller);
    }

    /**
     * Find a production roller by its ID with permission check.
     *
     * @param int $roller_id
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return ProductionRoller|null
     */
    public function findProductionRollerOfIdWithPermission(int $roller_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductionRoller
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'pr');
        $this->db->where('pr.roller_id', $roller_id);
        $this->db->join('eudr_factories f', 'f.factory_id = pr.factory_id', 'LEFT');
        $production_roller = $this->db->getOne('eudr_production_rollers pr', 'pr.*, f.factory_name');
        if (empty($production_roller)) {
            return null;
        }
        return new ProductionRoller($production_roller['roller_id'], $production_roller);
    }

    /**
     * Find a production roller by its code.
     *
     * @param string $code
     * @return ProductionRoller|null
     */
    public function findProductionRollerOfCode(string $code): ?ProductionRoller
    {
        $this->db->where('pr.roller_code', $code);
        $this->db->where('pr.deleted_by', 0);
        $this->db->join('eudr_factories f', 'f.factory_id = pr.factory_id', 'LEFT');
        $production_roller = $this->db->getOne('eudr_production_rollers pr', 'pr.*, f.factory_name');
        if (empty($production_roller)) {
            return null;
        }
        return new ProductionRoller($production_roller['roller_id'], $production_roller);
    }

    /**
     * Find a production roller by its code with permission check.
     *
     * @param string $code
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return ProductionRoller|null
     */
    public function findProductionRollerOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductionRoller
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'pr');
        $this->db->where('pr.roller_code', $code);
        $this->db->join('eudr_factories f', 'f.factory_id = pr.factory_id', 'LEFT');
        $production_roller = $this->db->getOne('eudr_production_rollers pr', 'pr.*, f.factory_name');
        if (empty($production_roller)) {
            return null;
        }
        return new ProductionRoller($production_roller['roller_id'], $production_roller);
    }

    /**
     * Generate a unique code for a production roller.
     *
     * @return string
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = 'prol-' . date('ymd') . '-' . Utils::generateRandomString(8);
            $production_roller = $this->findProductionRollerOfCode($code);
            if (!$production_roller) {
                break;
            }
        }
        return $code;
    }

    /**
     * Create a new production roller with the given data.
     *
     * @param array $data
     * @return ProductionRoller|null
     */
    public function createProductionRoller(array $data): ?ProductionRoller
    {
        $this->db->insert('eudr_production_rollers', $data);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }

        $roller_id = $this->db->getInsertId();
        return $this->findProductionRollerOfId($roller_id);
    }

    /**
     * Update a production roller with the given data.
     *
     * @param int $roller_id
     * @param array $data_update
     * @return ProductionRoller
     * @throws ProductionRollerNotFoundException
     */
    public function updateProductionRoller(int $roller_id, array $data_update): ProductionRoller
    {
        $this->db->where('roller_id', $roller_id);
        $this->db->update('eudr_production_rollers', $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new ProductionRollerNotFoundException("Production Roller not found with ID: $roller_id");
        }
        return $this->findProductionRollerOfId($roller_id);
    }

    /**
     * Update a production roller with the given data and permission check.
     *
     * @param int $roller_id
     * @param array $data_update
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return ProductionRoller
     * @throws ProductionRollerNotFoundException
     */
    public function updateProductionRollerWithPermission(int $roller_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ProductionRoller
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, '');
        $this->db->where('roller_id', $roller_id);
        $this->db->update('eudr_production_rollers', $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new ProductionRollerNotFoundException("Production Roller not found with ID: $roller_id");
        }
        return $this->findProductionRollerOfId($roller_id);
    }

    /**
     * Delete a production roller.
     *
     * @param int $roller_id
     * @param int $deleted_by
     * @return void
     */
    public function deleteProductionRoller(int $roller_id, int $deleted_by): void
    {
        $this->db->where('roller_id', $roller_id);
        $this->db->update('eudr_production_rollers', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

    /**
     * Delete a production roller with permission check.
     *
     * @param int $roller_id
     * @param int $deleted_by
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return void
     */
    public function deleteProductionRollerWithPermission(int $roller_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, '');
        $this->db->where('roller_id', $roller_id);
        $this->db->update('eudr_production_rollers', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

    /**
     * Find one rolling run by id with scope permission.
     * 
     * @param int $rolling_run_id
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return array|null
     */
    public function findRollingRunOfIdWithPermission(int $rolling_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'rr');
        $this->db->where('rr.rolling_run_id', $rolling_run_id);
        $record = $this->db->getOne('eudr_production_rolling_runs rr', 'rr.*');

        return !empty($record) ? $record : null;
    }

    public function getRollingRunDetailWithPermission(int $rolling_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array
    {
        $run = $this->findRollingRunOfIdWithPermission($rolling_run_id, $auth_user_id, $scope, $company_id, $company_id_param);
        if (empty($run)) {
            return null;
        }

        $this->db->where('rr.rolling_run_id', $rolling_run_id);
        $this->db->join('eudr_production_rollers r', 'r.roller_id = rr.roller_id', 'LEFT');
        $this->db->join('eudr_production_cutting_runs cr', 'cr.cutting_run_id = rr.cutting_run_id', 'LEFT');
        $runDetail = $this->db->getOne(
            'eudr_production_rolling_runs rr',
            'rr.*, r.roller_code, r.roller_name, cr.cutting_run_id AS source_cutting_run_id, cr.status AS source_cutting_run_status, cr.cutting_machine_id AS source_cutting_machine_id'
        );
        if (empty($runDetail)) {
            return null;
        }

        $this->db->where('qd.rolling_run_id', $rolling_run_id);
        $this->db->where('qd.deleted_by', 0);
        $this->db->orderBy('qd.quality_type', 'ASC');
        $this->db->join('eudr_production_grades g', 'g.grade_id = qd.grade_id', 'LEFT');
        $qualityDetails = $this->db->get(
            'eudr_production_rolling_run_quality_details qd',
            null,
            'qd.*, g.grade_code, g.name AS grade_name'
        ) ?? [];

        $runDetail['quality_details'] = $qualityDetails;

        return $runDetail;
    }

    /**
     * Update rolling run quality details.
     * 
     * @param array $data
     * @return array|null
     */
    public function updateRollingRunQualityDetails(array $data): ?array
    {
        $rollingRunId = (int)($data['rolling_run_id'] ?? 0);
        $details = $data['details'] ?? [];
        $startedAt = !empty($data['started_at']) ? (string)$data['started_at'] : null;
        $endedAt = !empty($data['ended_at']) ? (string)$data['ended_at'] : null;
        $updatedBy = (int)($data['updated_by'] ?? 0);

        if ($rollingRunId <= 0 || !is_array($details) || count($details) === 0 || $updatedBy <= 0) {
            return null;
        }

        $this->db->where('rolling_run_id', $rollingRunId);
        $this->db->where('deleted_by', 0);
        $rollingRun = $this->db->getOne('eudr_production_rolling_runs', '*');
        if (empty($rollingRun)) {
            return null;
        }

        $now = date('Y-m-d H:i:s', time());
        $runEndedAt = $endedAt ?? $now;
        $updatedDetails = [];

        $this->db->startTransaction();

        foreach ($details as $item) {
            $qualityType = (string)($item['quality_type'] ?? 'NA');
            $gradeId = isset($item['grade_id']) ? (int)$item['grade_id'] : 0;
            $inputSheetCount = (int)($item['input_sheet_count'] ?? 0);
            $outputSheetCount = (int)($item['output_sheet_count'] ?? 0);
            $thicknessMin = isset($item['output_sheet_thickness_min_mm']) ? (float)$item['output_sheet_thickness_min_mm'] : 2.50;
            $thicknessMax = isset($item['output_sheet_thickness_max_mm']) ? (float)$item['output_sheet_thickness_max_mm'] : 3.50;
            $notes = $item['notes'] ?? null;

            $this->db->where('rolling_run_id', $rollingRunId);
            $this->db->where('quality_type', $qualityType);
            $this->db->where('deleted_by', 0);
            $existing = $this->db->getOne('eudr_production_rolling_run_quality_details', '*');

            if (!empty($existing)) {
                $this->db->where('rolling_quality_detail_id', (int)$existing['rolling_quality_detail_id']);
                $this->db->update('eudr_production_rolling_run_quality_details', [
                    'grade_id' => $gradeId,
                    'input_sheet_count' => $inputSheetCount,
                    'output_sheet_count' => $outputSheetCount,
                    'output_sheet_thickness_min_mm' => $thicknessMin,
                    'output_sheet_thickness_max_mm' => $thicknessMax,
                    'notes' => $notes,
                    'updated_by' => $updatedBy,
                    'updated_at' => $now,
                ]);
                if ($this->db->getLastErrno() !== 0) {
                    $this->db->rollback();
                    return null;
                }
            } else {
                $this->db->insert('eudr_production_rolling_run_quality_details', [
                    'rolling_run_id' => $rollingRunId,
                    'grade_id' => $gradeId,
                    'quality_type' => $qualityType,
                    'input_sheet_count' => $inputSheetCount,
                    'output_sheet_count' => $outputSheetCount,
                    'output_sheet_thickness_min_mm' => $thicknessMin,
                    'output_sheet_thickness_max_mm' => $thicknessMax,
                    'notes' => $notes,
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
            }

            $updatedDetails[] = [
                'quality_type' => $qualityType,
                'grade_id' => $gradeId,
                'input_sheet_count' => $inputSheetCount,
                'output_sheet_count' => $outputSheetCount,
                'output_sheet_thickness_min_mm' => $thicknessMin,
                'output_sheet_thickness_max_mm' => $thicknessMax,
                'notes' => $notes,
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
        $this->db->where('rolling_run_id', $rollingRunId);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_production_rolling_runs', $runUpdateData);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->where('roller_id', (int)($rollingRun['roller_id'] ?? 0));
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_production_rollers', [
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
            'rolling_run_id' => $rollingRunId,
            'status' => 'completed',
            'ended_at' => $runEndedAt,
            'quality_details' => $updatedDetails,
        ];
    }
}
