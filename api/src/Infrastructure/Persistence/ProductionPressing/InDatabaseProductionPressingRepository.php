<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\ProductionPressing;

use App\Application\Utility\CurrentUserContext;
use App\Domain\ProductionPressing\ProductionPressingRepository;

class InDatabaseProductionPressingRepository implements ProductionPressingRepository
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

    public function findAllPressingRuns(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $companyIdParam = $company_id_param ?? 0;
        $productionOrderId = $params['production_order_id'] ?? 0;
        $factoryId = $params['factory_id'] ?? 0;
        $status = $params['status'] ?? 'all';

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'pr');
        if (!empty($productionOrderId)) {
            $this->db->where('pr.production_order_id', (int)$productionOrderId);
        }
        if (!empty($factoryId)) {
            $this->db->where('pr.factory_id', (int)$factoryId);
        }
        if ($status !== 'all') {
            $this->db->where('pr.status', $status);
        }
        $total_records = (int)$this->db->getValue('eudr_production_pressing_runs pr', 'count(*)');

        $this->db->pageLimit = $page_limit;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'pr');
        if (!empty($productionOrderId)) {
            $this->db->where('pr.production_order_id', (int)$productionOrderId);
        }
        if (!empty($factoryId)) {
            $this->db->where('pr.factory_id', (int)$factoryId);
        }
        if ($status !== 'all') {
            $this->db->where('pr.status', $status);
        }

        $cols = 'pr.*, po.production_order_code';
        $this->db->join('eudr_production_orders po', 'po.production_order_id = pr.production_order_id', 'LEFT');
        $this->db->orderBy('pr.pressing_run_id', 'DESC');
        $records = $this->db->arraybuilder()->paginate('eudr_production_pressing_runs pr', $page, $cols);

        $items = [];
        $runIds = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $item['quality_details'] = [];
                $items[] = $item;
                $runIds[] = (int)($item['pressing_run_id'] ?? 0);
            }
        }

        if (!empty($runIds)) {
            $this->db->where('pressing_run_id', $runIds, 'IN');
            $this->db->where('deleted_by', 0);
            $this->db->orderBy('pressing_quality_detail_id', 'ASC');
            $qualityRows = $this->db->arraybuilder()->get('eudr_production_pressing_run_quality_details') ?? [];

            $qualityMap = [];
            foreach ($qualityRows as $row) {
                $rid = (int)($row['pressing_run_id'] ?? 0);
                $qualityMap[$rid][] = $row;
            }
            foreach ($items as $index => $run) {
                $rid = (int)($run['pressing_run_id'] ?? 0);
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

    public function findDryingRunOfIdWithPermission(int $drying_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'dr');
        $this->db->where('dr.drying_run_id', $drying_run_id);
        $record = $this->db->getOne('eudr_production_drying_runs dr', 'dr.*');

        return !empty($record) ? $record : null;
    }

    public function findPressingRunOfIdWithPermission(int $pressing_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'pr');
        $this->db->where('pr.pressing_run_id', $pressing_run_id);
        $record = $this->db->getOne('eudr_production_pressing_runs pr', 'pr.*');

        return !empty($record) ? $record : null;
    }

    public function getPressingRunDetailWithPermission(int $pressing_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array
    {
        $run = $this->findPressingRunOfIdWithPermission($pressing_run_id, $auth_user_id, $scope, $company_id, $company_id_param);
        if (empty($run)) {
            return null;
        }

        $this->db->where('pr.pressing_run_id', $pressing_run_id);
        $this->db->join('eudr_production_drying_runs dr', 'dr.drying_run_id = pr.drying_run_id', 'LEFT');
        $runDetail = $this->db->getOne(
            'eudr_production_pressing_runs pr',
            'pr.*, dr.drying_run_id AS source_drying_run_id, dr.status AS source_drying_run_status, dr.oven_id AS source_oven_id'
        );
        if (empty($runDetail)) {
            return null;
        }

        $this->db->where('pqd.pressing_run_id', $pressing_run_id);
        $this->db->where('pqd.deleted_by', 0);
        $this->db->orderBy('pqd.grade_id', 'ASC');
        $qualityDetails = $this->db->get('eudr_production_pressing_run_quality_details pqd') ?? [];

        $this->db->where('b.pressing_run_id', $pressing_run_id);
        $this->db->where('b.deleted_by', 0);
        $this->db->orderBy('b.bale_no', 'ASC');
        $bales = $this->db->get('eudr_production_bales b') ?? [];

        $runDetail['quality_details'] = $qualityDetails;
        $runDetail['bales'] = $bales;

        return $runDetail;
    }

    public function createPressingRunFromDrying(array $data): ?array
    {
        $dryingRunId = (int)($data['drying_run_id'] ?? 0);
        $notes = $data['notes'] ?? null;
        $updatedBy = (int)($data['updated_by'] ?? 0);

        if ($dryingRunId <= 0 || $updatedBy <= 0) {
            return null;
        }

        $this->db->startTransaction();

        $this->db->where('drying_run_id', $dryingRunId);
        $this->db->where('deleted_by', 0);
        $dryingRun = $this->db->getOne('eudr_production_drying_runs', '*');
        if (empty($dryingRun)) {
            $this->db->rollback();
            return null;
        }
        if ((string)($dryingRun['status'] ?? '') !== 'completed') {
            $this->db->rollback();
            return null;
        }

        $this->db->where('drying_run_id', $dryingRunId);
        $this->db->where('deleted_by', 0);
        $dryingDetails = $this->db->get(
            'eudr_production_drying_run_quality_details',
            null,
            'grade_id, quality_type, output_sheet_count, notes'
        );

        if (empty($dryingDetails)) {
            $this->db->rollback();
            return null;
        }

        foreach ($dryingDetails as $dryingDetail) {
            if ((int)($dryingDetail['grade_id'] ?? 0) <= 0) {
                $this->db->rollback();
                return null;
            }
        }

        $now = date('Y-m-d H:i:s', time());

        $this->db->where('drying_run_id', $dryingRunId);
        $this->db->where('deleted_by', 0);
        $existingPressingRun = $this->db->getOne('eudr_production_pressing_runs', '*');
        if (!empty($existingPressingRun)) {
            $this->db->rollback();
            return null;
        }

        $pressingData = [
            'drying_run_id' => $dryingRunId,
            'production_order_id' => (int)($dryingRun['production_order_id'] ?? 0),
            'company_id' => (int)($dryingRun['company_id'] ?? 0),
            'factory_id' => (int)($dryingRun['factory_id'] ?? 0),
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

        $this->db->insert('eudr_production_pressing_runs', $pressingData);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }
        $pressingRunId = (int)$this->db->getInsertId();

        $qualityDetails = [];
        foreach ($dryingDetails as $dryingDetail) {
            $gradeId = (int)$dryingDetail['grade_id'];
            $inputDriedSheetCount = (int)($dryingDetail['output_sheet_count'] ?? 0);
            $detailNotes = $dryingDetail['notes'] ?? null;

            $this->db->insert('eudr_production_pressing_run_quality_details', [
                'pressing_run_id' => $pressingRunId,
                'grade_id' => $gradeId,
                'input_dried_sheet_count' => $inputDriedSheetCount,
                'qualified_sheet_count' => 0,
                'rejected_sheet_count' => 0,
                'output_bale_count' => 0,
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
                'grade_id' => $gradeId,
                'input_dried_sheet_count' => $inputDriedSheetCount,
            ];
        }

        $this->db->commit();

        return [
            'pressing_run_id' => $pressingRunId,
            'drying_run_id' => $dryingRunId,
            'status' => 'in_progress',
            'started_at' => $now,
            'quality_details' => $qualityDetails,
        ];
    }

    public function updatePressingRunQualityDetails(array $data): ?array
    {
        $pressingRunId = (int)($data['pressing_run_id'] ?? 0);
        $details = $data['details'] ?? [];
        $startedAt = !empty($data['started_at']) ? (string)$data['started_at'] : null;
        $endedAt = !empty($data['ended_at']) ? (string)$data['ended_at'] : null;
        $updatedBy = (int)($data['updated_by'] ?? 0);

        if ($pressingRunId <= 0 || !is_array($details) || count($details) === 0 || $updatedBy <= 0) {
            return null;
        }

        $this->db->where('pressing_run_id', $pressingRunId);
        $this->db->where('deleted_by', 0);
        $pressingRun = $this->db->getOne('eudr_production_pressing_runs', '*');
        if (empty($pressingRun)) {
            return null;
        }
        if ((string)($pressingRun['status'] ?? '') === 'cancelled') {
            return null;
        }

        $now = date('Y-m-d H:i:s', time());
        $runEndedAt = $endedAt ?? $now;
        $updatedDetails = [];

        $this->db->startTransaction();

        foreach ($details as $item) {
            $gradeId = (int)($item['grade_id'] ?? 0);
            $productTypeId = (int)($item['product_type_id'] ?? 0);
            $qualifiedSheetCount = (int)($item['qualified_sheet_count'] ?? -1);
            $rejectedSheetCount = (int)($item['rejected_sheet_count'] ?? -1);
            $outputBaleCount = (int)($item['output_bale_count'] ?? -1);
            $notes = $item['notes'] ?? null;

            if ($gradeId <= 0 || $qualifiedSheetCount < 0 || $rejectedSheetCount < 0 || $outputBaleCount < 0) {
                $this->db->rollback();
                return null;
            }

            $this->db->where('pressing_run_id', $pressingRunId);
            $this->db->where('grade_id', $gradeId);
            $this->db->where('deleted_by', 0);
            $existing = $this->db->getOne('eudr_production_pressing_run_quality_details', '*');
            if (empty($existing)) {
                $this->db->rollback();
                return null;
            }

            $inputDriedSheetCount = (int)($existing['input_dried_sheet_count'] ?? 0);
            if (($qualifiedSheetCount + $rejectedSheetCount) > $inputDriedSheetCount) {
                $this->db->rollback();
                return null;
            }

            $this->db->where('pressing_quality_detail_id', (int)$existing['pressing_quality_detail_id']);
            $this->db->update('eudr_production_pressing_run_quality_details', [
                'grade_id' => $gradeId,
                'product_type_id' => $productTypeId,
                'qualified_sheet_count' => $qualifiedSheetCount,
                'rejected_sheet_count' => $rejectedSheetCount,
                'output_bale_count' => $outputBaleCount,
                'notes' => $notes,
                'updated_by' => $updatedBy,
                'updated_at' => $now,
            ]);
            if ($this->db->getLastErrno() !== 0) {
                $this->db->rollback();
                return null;
            }

            $updatedDetails[] = [
                'pressing_quality_detail_id' => (int)$existing['pressing_quality_detail_id'],
                'grade_id' => $gradeId,
                'product_type_id' => $productTypeId,
                'qualified_sheet_count' => $qualifiedSheetCount,
                'rejected_sheet_count' => $rejectedSheetCount,
                'output_bale_count' => $outputBaleCount,
                'notes' => $notes,
            ];
        }

        $this->db->where('pressing_run_id', $pressingRunId);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_production_bales', [
            'deleted_by' => $updatedBy,
            'deleted_at' => $now,
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->where('pressing_run_id', $pressingRunId);
        $this->db->where('deleted_by', 0);
        $finalDetails = $this->db->get(
            'eudr_production_pressing_run_quality_details',
            null,
            'pressing_quality_detail_id, grade_id, output_bale_count'
        ) ?? [];

        $balesCreated = 0;
        $sequence = 1;
        foreach ($finalDetails as $finalDetail) {
            $baleCount = (int)($finalDetail['output_bale_count'] ?? 0);
            $gradeId = (int)($finalDetail['grade_id'] ?? 0);
            $pressingQualityDetailId = (int)($finalDetail['pressing_quality_detail_id'] ?? 0);
            if ($gradeId <= 0 || $pressingQualityDetailId <= 0 || $baleCount <= 0) {
                continue;
            }

            for ($i = 0; $i < $baleCount; $i++) {
                $baleNo = sprintf('PR%u-%04u', $pressingRunId, $sequence);
                $sequence++;

                $this->db->insert('eudr_production_bales', [
                    'pressing_run_id' => $pressingRunId,
                    'pressing_quality_detail_id' => $pressingQualityDetailId,
                    'production_order_id' => (int)($pressingRun['production_order_id'] ?? 0),
                    'company_id' => (int)($pressingRun['company_id'] ?? 0),
                    'factory_id' => (int)($pressingRun['factory_id'] ?? 0),
                    'bale_no' => $baleNo,
                    'grade_id' => $gradeId,
                    'bale_weight_kg' => null,
                    'status' => 'formed',
                    'notes' => null,
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
                $balesCreated++;
            }
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
        $this->db->where('pressing_run_id', $pressingRunId);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_production_pressing_runs', $runUpdateData);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->commit();

        return [
            'pressing_run_id' => $pressingRunId,
            'status' => 'completed',
            'ended_at' => $runEndedAt,
            'bales_created' => $balesCreated,
            'quality_details' => $updatedDetails,
        ];
    }
}
