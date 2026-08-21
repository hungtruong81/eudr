<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\ProductionCuttingMachine;

use App\Application\Utility\CurrentUserContext;
use App\Application\Utility\Utils;
use App\Domain\ProductionCuttingMachine\ProductionCuttingMachine;
use App\Domain\ProductionCuttingMachine\ProductionCuttingMachineNotFoundException;
use App\Domain\ProductionCuttingMachine\ProductionCuttingMachineRepository;

class InDatabaseProductionCuttingMachineRepository implements ProductionCuttingMachineRepository
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
     * Apply scope-based where conditions for queries.
     *
     * @param string $scope
     * @param int $authUserId
     * @param int $companyId
     * @param int|null $companyIdParam
     * @param string $alias
     * @return void
     */
    private function scopeWhere(string $scope, int $authUserId, int $companyId, ?int $companyIdParam = null, string $alias = 'pcm'): void
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
     * Find all production cutting machines based on the given parameters.
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
        $cuttingMachineCode = $params['cutting_machine_code'] ?? '';
        $cuttingMachineName = $params['cutting_machine_name'] ?? '';
        $status = $params['status'] ?? 'all';
        $factoryId = $params['factory_id'] ?? 0;
        $companyIdParam = $company_id_param ?? 0;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'pcm');
        if (!empty($search)) {
            $this->db->where('(pcm.cutting_machine_code LIKE ? OR pcm.cutting_machine_name LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($cuttingMachineCode)) {
            $this->db->where('pcm.cutting_machine_code', $cuttingMachineCode);
        }
        if (!empty($cuttingMachineName)) {
            $this->db->where('pcm.cutting_machine_name', $cuttingMachineName);
        }
        if ($status !== 'all') {
            $this->db->where('pcm.status', $status);
        }
        if (!empty($factoryId)) {
            $this->db->where('pcm.factory_id', $factoryId);
        }
        $total_records = (int)$this->db->getValue('eudr_production_cutting_machines pcm', 'count(*)');

        $this->db->pageLimit = $page_limit;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'pcm');
        if (!empty($search)) {
            $this->db->where('(pcm.cutting_machine_code LIKE ? OR pcm.cutting_machine_name LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($cuttingMachineCode)) {
            $this->db->where('pcm.cutting_machine_code', $cuttingMachineCode);
        }
        if (!empty($cuttingMachineName)) {
            $this->db->where('pcm.cutting_machine_name', $cuttingMachineName);
        }
        if ($status !== 'all') {
            $this->db->where('pcm.status', $status);
        }
        if (!empty($factoryId)) {
            $this->db->where('pcm.factory_id', $factoryId);
        }

        $cols = 'pcm.*, f.factory_name';

        if (!empty($params['order_by'])) {
            $this->db->orderBy('pcm.' . $params['order_by'], $params['order_type'] ?? 'ASC');
        } else {
            $this->db->orderBy('pcm.cutting_machine_id', 'DESC');
        }

        $this->db->join('eudr_factories f', 'f.factory_id = pcm.factory_id', 'LEFT');
        $records = $this->db->arraybuilder()->paginate('eudr_production_cutting_machines pcm', $page, $cols);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = new ProductionCuttingMachine($item['cutting_machine_id'], $item);
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
     * Find all cutting runs based on the given parameters.
     *
     * @param array $params
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return array
     */
    public function findAllCuttingRuns(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $productionOrderId = $params['production_order_id'] ?? 0;
        $factoryId = $params['factory_id'] ?? 0;
        $cuttingMachineId = $params['cutting_machine_id'] ?? 0;
        $status = $params['status'] ?? 'all';
        $companyIdParam = $company_id_param ?? 0;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'cr');
        if (!empty($productionOrderId)) {
            $this->db->where('cr.production_order_id', (int)$productionOrderId);
        }
        if (!empty($factoryId)) {
            $this->db->where('cr.factory_id', (int)$factoryId);
        }
        if (!empty($cuttingMachineId)) {
            $this->db->where('cr.cutting_machine_id', (int)$cuttingMachineId);
        }
        if ($status !== 'all') {
            $this->db->where('cr.status', $status);
        }
        $total_records = (int)$this->db->getValue('eudr_production_cutting_runs cr', 'count(*)');

        $this->db->pageLimit = $page_limit;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'cr');
        if (!empty($productionOrderId)) {
            $this->db->where('cr.production_order_id', (int)$productionOrderId);
        }
        if (!empty($factoryId)) {
            $this->db->where('cr.factory_id', (int)$factoryId);
        }
        if (!empty($cuttingMachineId)) {
            $this->db->where('cr.cutting_machine_id', (int)$cuttingMachineId);
        }
        if ($status !== 'all') {
            $this->db->where('cr.status', $status);
        }

        $cols = 'cr.*, pcm.cutting_machine_code, pcm.cutting_machine_name, po.production_order_code';
        $this->db->join('eudr_production_cutting_machines pcm', 'pcm.cutting_machine_id = cr.cutting_machine_id', 'LEFT');
        $this->db->join('eudr_production_orders po', 'po.production_order_id = cr.production_order_id', 'LEFT');
        $this->db->orderBy('cr.cutting_run_id', 'DESC');
        $records = $this->db->arraybuilder()->paginate('eudr_production_cutting_runs cr', $page, $cols);

        $items = [];
        $runIds = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $item['quality_outputs'] = [];
                $items[] = $item;
                $runIds[] = (int)($item['cutting_run_id'] ?? 0);
            }
        }

        if (!empty($runIds)) {
            $this->db->where('cutting_run_id', $runIds, 'IN');
            $this->db->where('deleted_by', 0);
            $this->db->orderBy('cutting_quality_output_id', 'ASC');
            $qualityRows = $this->db->arraybuilder()->get('eudr_production_cutting_run_quality_outputs');

            $qualityMap = [];
            if (!empty($qualityRows)) {
                foreach ($qualityRows as $row) {
                    $rid = (int)($row['cutting_run_id'] ?? 0);
                    if (!isset($qualityMap[$rid])) {
                        $qualityMap[$rid] = [];
                    }
                    $qualityMap[$rid][] = $row;
                }
            }

            foreach ($items as $index => $run) {
                $rid = (int)($run['cutting_run_id'] ?? 0);
                $items[$index]['quality_outputs'] = $qualityMap[$rid] ?? [];
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
     * Find a production cutting machine by its ID.
     *
     * @param int $cutting_machine_id
     * @return ProductionCuttingMachine|null
     */
    public function findProductionCuttingMachineOfId(int $cutting_machine_id): ?ProductionCuttingMachine
    {
        $this->db->where('pcm.cutting_machine_id', $cutting_machine_id);
        $this->db->where('pcm.deleted_by', 0);
        $this->db->join('eudr_factories f', 'f.factory_id = pcm.factory_id', 'LEFT');
        $production_cutting_machine = $this->db->getOne('eudr_production_cutting_machines pcm', 'pcm.*, f.factory_name');
        if (empty($production_cutting_machine)) {
            return null;
        }
        return new ProductionCuttingMachine($production_cutting_machine['cutting_machine_id'], $production_cutting_machine);
    }

    /**
     * Find a production cutting machine by its ID with permission check.
     *
     * @param int $cutting_machine_id
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return ProductionCuttingMachine|null
     */
    public function findProductionCuttingMachineOfIdWithPermission(int $cutting_machine_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductionCuttingMachine
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'pcm');
        $this->db->where('pcm.cutting_machine_id', $cutting_machine_id);
        $this->db->join('eudr_factories f', 'f.factory_id = pcm.factory_id', 'LEFT');
        $production_cutting_machine = $this->db->getOne('eudr_production_cutting_machines pcm', 'pcm.*, f.factory_name');
        if (empty($production_cutting_machine)) {
            return null;
        }
        return new ProductionCuttingMachine($production_cutting_machine['cutting_machine_id'], $production_cutting_machine);
    }

    /**
     * Find a production cutting machine by its code.
     *
     * @param string $code
     * @return ProductionCuttingMachine|null
     */
    public function findProductionCuttingMachineOfCode(string $code): ?ProductionCuttingMachine
    {
        $this->db->where('pcm.cutting_machine_code', $code);
        $this->db->where('pcm.deleted_by', 0);
        $this->db->join('eudr_factories f', 'f.factory_id = pcm.factory_id', 'LEFT');
        $production_cutting_machine = $this->db->getOne('eudr_production_cutting_machines pcm', 'pcm.*, f.factory_name');
        if (empty($production_cutting_machine)) {
            return null;
        }
        return new ProductionCuttingMachine($production_cutting_machine['cutting_machine_id'], $production_cutting_machine);
    }

    /**
     * Find a production cutting machine by its code with permission check.
     *
     * @param string $code
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return ProductionCuttingMachine|null
     */
    public function findProductionCuttingMachineOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductionCuttingMachine
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'pcm');
        $this->db->where('pcm.cutting_machine_code', $code);
        $this->db->join('eudr_factories f', 'f.factory_id = pcm.factory_id', 'LEFT');
        $production_cutting_machine = $this->db->getOne('eudr_production_cutting_machines pcm', 'pcm.*, f.factory_name');
        if (empty($production_cutting_machine)) {
            return null;
        }
        return new ProductionCuttingMachine($production_cutting_machine['cutting_machine_id'], $production_cutting_machine);
    }

    /**
     * Generate a unique code for production cutting machine.
     *
     * @return string
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = 'pcme-' . date('ymd') . '-' . Utils::generateRandomString(8);
            $production_cutting_machine = $this->findProductionCuttingMachineOfCode($code);
            if (!$production_cutting_machine) {
                break;
            }
        }
        return $code;
    }

    /**
     * Create a new production cutting machine with the given data.
     *
     * @param array $data
     * @return ProductionCuttingMachine|null
     */
    public function createProductionCuttingMachine(array $data): ?ProductionCuttingMachine
    {
        $this->db->insert('eudr_production_cutting_machines', $data);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }

        $cutting_machine_id = $this->db->getInsertId();
        return $this->findProductionCuttingMachineOfId($cutting_machine_id);
    }

    /**
     * Update a production cutting machine with the given data.
     *
     * @param int $cutting_machine_id
     * @param array $data_update
     * @return ProductionCuttingMachine
     * @throws ProductionCuttingMachineNotFoundException
     */
    public function updateProductionCuttingMachine(int $cutting_machine_id, array $data_update): ProductionCuttingMachine
    {
        $this->db->where('cutting_machine_id', $cutting_machine_id);
        $this->db->update('eudr_production_cutting_machines', $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new ProductionCuttingMachineNotFoundException("Production Cutting Machine not found with ID: $cutting_machine_id");
        }
        return $this->findProductionCuttingMachineOfId($cutting_machine_id);
    }

    /**
     * Update a production cutting machine with permission check.
     *
     * @param int $cutting_machine_id
     * @param array $data_update
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return ProductionCuttingMachine
     * @throws ProductionCuttingMachineNotFoundException
     */
    public function updateProductionCuttingMachineWithPermission(int $cutting_machine_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ProductionCuttingMachine
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, '');
        $this->db->where('cutting_machine_id', $cutting_machine_id);
        $this->db->update('eudr_production_cutting_machines', $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new ProductionCuttingMachineNotFoundException("Production Cutting Machine not found with ID: $cutting_machine_id");
        }
        return $this->findProductionCuttingMachineOfId($cutting_machine_id);
    }

    /**
     * Delete a production cutting machine.
     *
     * @param int $cutting_machine_id
     * @param int $deleted_by
     * @return void
     */
    public function deleteProductionCuttingMachine(int $cutting_machine_id, int $deleted_by): void
    {
        $this->db->where('cutting_machine_id', $cutting_machine_id);
        $this->db->update('eudr_production_cutting_machines', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

    /**
     * Delete a production cutting machine with permission check.
     *
     * @param int $cutting_machine_id
     * @param int $deleted_by
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return void
     */
    public function deleteProductionCuttingMachineWithPermission(int $cutting_machine_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, '');
        $this->db->where('cutting_machine_id', $cutting_machine_id);
        $this->db->update('eudr_production_cutting_machines', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

    /**
     * Find a cutting run by its ID with permission check.
     *
     * @param int $cutting_run_id
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return array|null
     */
    public function findCuttingRunOfIdWithPermission(int $cutting_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'cr');
        $this->db->where('cr.cutting_run_id', $cutting_run_id);
        $record = $this->db->getOne('eudr_production_cutting_runs cr', 'cr.*');

        return !empty($record) ? $record : null;
    }

    public function getCuttingRunDetailWithPermission(int $cutting_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array
    {
        $run = $this->findCuttingRunOfIdWithPermission($cutting_run_id, $auth_user_id, $scope, $company_id, $company_id_param);
        if (empty($run)) {
            return null;
        }

        $this->db->where('cr.cutting_run_id', $cutting_run_id);
        $this->db->join('eudr_production_cutting_machines cm', 'cm.cutting_machine_id = cr.cutting_machine_id', 'LEFT');
        $this->db->join('eudr_production_channel_runs ch', 'ch.channel_run_id = cr.channel_run_id', 'LEFT');
        $runDetail = $this->db->getOne(
            'eudr_production_cutting_runs cr',
            'cr.*, cm.cutting_machine_code, cm.cutting_machine_name, ch.channel_run_id AS source_channel_run_id, ch.status AS source_channel_run_status, ch.channel_id AS source_channel_id, ch.raw_tank_id AS source_raw_tank_id'
        );
        if (empty($runDetail)) {
            return null;
        }

        $this->db->where('qo.cutting_run_id', $cutting_run_id);
        $this->db->where('qo.deleted_by', 0);
        $this->db->orderBy('qo.quality_type', 'ASC');
        $this->db->join('eudr_production_grades g', 'g.grade_id = qo.grade_id', 'LEFT');
        $qualityOutputs = $this->db->get(
            'eudr_production_cutting_run_quality_outputs qo',
            null,
            'qo.*, g.grade_code, g.name AS grade_name'
        ) ?? [];

        $runDetail['quality_outputs'] = $qualityOutputs;

        return $runDetail;
    }

    /**
     * Update cutting run quality outputs and related statuses.
     *
     * @param array $data
     * @return array|null
     */
    public function updateCuttingRunQualityOutputs(array $data): ?array
    {
        $cuttingRunId = (int)($data['cutting_run_id'] ?? 0);
        $outputs = $data['outputs'] ?? [];
        $startedAt = !empty($data['started_at']) ? (string)$data['started_at'] : null;
        $endedAt = !empty($data['ended_at']) ? (string)$data['ended_at'] : null;
        $updatedBy = (int)($data['updated_by'] ?? 0);

        if ($cuttingRunId <= 0 || !is_array($outputs) || count($outputs) === 0 || $updatedBy <= 0) {
            return null;
        }

        $this->db->where('cutting_run_id', $cuttingRunId);
        $this->db->where('deleted_by', 0);
        $cuttingRun = $this->db->getOne('eudr_production_cutting_runs', '*');
        if (empty($cuttingRun)) {
            return null;
        }

        $now = date('Y-m-d H:i:s', time());
        $runEndedAt = $endedAt ?? $now;
        $updatedOutputs = [];

        $this->db->startTransaction();

        foreach ($outputs as $item) {
            $qualityType = (string)($item['quality_type'] ?? 'NA');
            $gradeId = isset($item['grade_id']) ? (int)$item['grade_id'] : 0;
            $outputSheetCount = (int)($item['output_sheet_count'] ?? 0);
            $thicknessMin = isset($item['output_sheet_thickness_min_mm']) ? (float)$item['output_sheet_thickness_min_mm'] : 15.00;
            $thicknessMax = isset($item['output_sheet_thickness_max_mm']) ? (float)$item['output_sheet_thickness_max_mm'] : 20.00;
            $notes = $item['notes'] ?? null;

            $this->db->where('cutting_run_id', $cuttingRunId);
            $this->db->where('quality_type', $qualityType);
            $this->db->where('deleted_by', 0);
            $existing = $this->db->getOne('eudr_production_cutting_run_quality_outputs', '*');

            if (!empty($existing)) {
                $this->db->where('cutting_quality_output_id', (int)$existing['cutting_quality_output_id']);
                $this->db->update('eudr_production_cutting_run_quality_outputs', [
                    'grade_id' => $gradeId,
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
                $this->db->insert('eudr_production_cutting_run_quality_outputs', [
                    'cutting_run_id' => $cuttingRunId,
                    'grade_id' => $gradeId,
                    'quality_type' => $qualityType,
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

            $updatedOutputs[] = [
                'quality_type' => $qualityType,
                'grade_id' => $gradeId,
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
        $this->db->where('cutting_run_id', $cuttingRunId);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_production_cutting_runs', $runUpdateData);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->where('cutting_machine_id', (int)($cuttingRun['cutting_machine_id'] ?? 0));
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_production_cutting_machines', [
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
            'cutting_run_id' => $cuttingRunId,
            'status' => 'completed',
            'ended_at' => $runEndedAt,
            'quality_outputs' => $updatedOutputs,
        ];
    }

    public function createRollingRunFromCutting(array $data): ?array
    {
        $cuttingRunId = (int)($data['cutting_run_id'] ?? 0);
        $rollerId = (int)($data['roller_id'] ?? 0);
        $notes = $data['notes'] ?? null;
        $updatedBy = (int)($data['updated_by'] ?? 0);

        if ($cuttingRunId <= 0 || $rollerId <= 0 || $updatedBy <= 0) {
            return null;
        }

        $this->db->startTransaction();

        $this->db->where('cutting_run_id', $cuttingRunId);
        $this->db->where('deleted_by', 0);
        $cuttingRun = $this->db->getOne('eudr_production_cutting_runs', '*');
        if (empty($cuttingRun)) {
            $this->db->rollback();
            return null;
        }

        $this->db->where('cutting_machine_id', (int)($cuttingRun['cutting_machine_id'] ?? 0));
        $this->db->where('deleted_by', 0);
        $cuttingMachine = $this->db->getOne('eudr_production_cutting_machines', 'cutting_machine_id, factory_id, company_id, status');
        if (empty($cuttingMachine)) {
            $this->db->rollback();
            return null;
        }

        $this->db->where('roller_id', $rollerId);
        $this->db->where('deleted_by', 0);
        $roller = $this->db->getOne('eudr_production_rollers', 'roller_id, factory_id, company_id, status');
        if (empty($roller)) {
            $this->db->rollback();
            return null;
        }

        if ((int)($roller['factory_id'] ?? 0) !== (int)($cuttingRun['factory_id'] ?? 0) || (int)($roller['company_id'] ?? 0) !== (int)($cuttingRun['company_id'] ?? 0)) {
            $this->db->rollback();
            return null;
        }
        if ((string)($roller['status'] ?? '') !== 'available') {
            $this->db->rollback();
            return null;
        }

        $now = date('Y-m-d H:i:s', time());

        $rollingData = [
            'production_order_id' => (int)($cuttingRun['production_order_id'] ?? 0),
            'cutting_run_id' => $cuttingRunId,
            'company_id' => (int)($cuttingRun['company_id'] ?? 0),
            'factory_id' => (int)($cuttingRun['factory_id'] ?? 0),
            'roller_id' => $rollerId,
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

        $this->db->insert('eudr_production_rolling_runs', $rollingData);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }
        $rollingRunId = (int)$this->db->getInsertId();

        $this->db->where('roller_id', $rollerId);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_production_rollers', [
            'status' => 'in_use',
            'updated_by' => $updatedBy,
            'updated_at' => $now,
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->where('cutting_run_id', $cuttingRunId);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_production_cutting_runs', [
            'status' => 'completed',
            'ended_at' => $now,
            'updated_by' => $updatedBy,
            'updated_at' => $now,
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->where('cutting_machine_id', (int)($cuttingRun['cutting_machine_id'] ?? 0));
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_production_cutting_machines', [
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
            'cutting_run_id' => $cuttingRunId,
            'roller_id' => $rollerId,
            'status' => 'in_progress',
            'started_at' => $now,
        ];
    }
}
