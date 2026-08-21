<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\ProductionOrder;

use App\Application\Utility\CurrentUserContext;
use App\Domain\ProductionOrder\ProductionOrder;
use App\Domain\ProductionOrder\ProductionOrderNotFoundException;
use App\Domain\ProductionOrder\ProductionOrderRepository;
use App\Application\Utility\Utils;

class InDatabaseProductionOrderRepository implements ProductionOrderRepository
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
     * InDatabaseProductionOrderRepository constructor.
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
     * Apply scope-based filtering (self/own/all) using company_id.
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

    private function resolveProductionProgressStatusByTable(string $table, int $production_order_id): string
    {
        $this->db->where('production_order_id', $production_order_id);
        $this->db->where('deleted_by', 0);
        $this->db->groupBy('status');
        $rows = $this->db->arraybuilder()->get($table, null, 'status, COUNT(*) AS total') ?? [];

        if (empty($rows)) {
            return 'not_started';
        }

        $counts = [];
        foreach ($rows as $row) {
            $status = (string)($row['status'] ?? '');
            $counts[$status] = (int)($row['total'] ?? 0);
        }

        if (($counts['in_progress'] ?? 0) > 0) {
            return 'in_progress';
        }
        if (($counts['draft'] ?? 0) > 0) {
            return 'draft';
        }
        if (($counts['completed'] ?? 0) > 0) {
            return 'completed';
        }
        if (($counts['cancelled'] ?? 0) > 0) {
            return 'cancelled';
        }

        $statusKeys = array_keys($counts);
        return !empty($statusKeys) ? (string)$statusKeys[0] : 'not_started';
    }

    private function appendProgressStatusToList(array $rows, string $status): array
    {
        foreach ($rows as &$row) {
            if (is_array($row)) {
                $row['production_progress_status'] = $status;
            }
        }

        return $rows;
    }

    private function getHangingSetupPoleNumbers(int $order_hanging_setup_id): array
    {
        $this->db->where('order_hanging_setup_id', $order_hanging_setup_id);
        $this->db->where('deleted_by', 0);
        $this->db->orderBy('pole_no', 'ASC');
        $rows = $this->db->get('eudr_production_order_hanging_setup_poles', null, 'pole_no') ?? [];

        return array_values(array_map(static function (array $row): int {
            return (int)($row['pole_no'] ?? 0);
        }, $rows));
    }

    private function getHangingSetupDetails(int $order_hanging_setup_id): array
    {
        $this->db->where('d.order_hanging_setup_id', $order_hanging_setup_id);
        $this->db->where('d.deleted_by', 0);
        $this->db->orderBy('d.order_hanging_setup_quality_detail_id', 'ASC');
        $details = $this->db->get(
            'eudr_production_order_hanging_setup_quality_details d',
            null,
            'd.order_hanging_setup_quality_detail_id, d.order_hanging_setup_id, d.production_order_id, d.quality_type, d.input_sheet_count, d.notes'
        ) ?? [];

        if (empty($details)) {
            return [];
        }

        $this->db->where('a.order_hanging_setup_id', $order_hanging_setup_id);
        $this->db->where('a.deleted_by', 0);
        $this->db->orderBy('a.pole_no', 'ASC');
        $assignments = $this->db->get(
            'eudr_production_order_hanging_setup_quality_pole_assignments a',
            null,
            'a.order_hanging_setup_quality_detail_id, a.pole_no'
        ) ?? [];

        $poleNumbersByDetail = [];
        foreach ($assignments as $assignment) {
            $detailId = (int)($assignment['order_hanging_setup_quality_detail_id'] ?? 0);
            if ($detailId <= 0) {
                continue;
            }
            $poleNumbersByDetail[$detailId][] = (int)($assignment['pole_no'] ?? 0);
        }

        foreach ($details as &$detail) {
            $detailId = (int)($detail['order_hanging_setup_quality_detail_id'] ?? 0);
            $detail['input_sheet_count'] = (int)($detail['input_sheet_count'] ?? 0);
            $detail['pole_numbers'] = array_values(array_filter(
                array_map('intval', $poleNumbersByDetail[$detailId] ?? []),
                static function (int $poleNo): bool {
                    return $poleNo > 0;
                }
            ));
        }
        unset($detail);

        return $details;
    }

    private function encodeChangeRequestValue(?array $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    private function decodeChangeRequestValue($raw): ?array
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $decoded = json_decode((string)$raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return [['value' => (string)$raw]];
    }

    private function normalizeSetupChangeRequestRow(array $row): array
    {
        $row['old_value'] = $this->decodeChangeRequestValue($row['old_value'] ?? null);
        $row['new_value'] = $this->decodeChangeRequestValue($row['new_value'] ?? null);

        return $row;
    }

    private function flattenChangeValue(?array $payload): array
    {
        $flattened = [];
        if ($payload === null) {
            return $flattened;
        }

        foreach ($payload as $item) {
            if (!is_array($item)) {
                continue;
            }

            foreach ($item as $key => $value) {
                $flattened[(string)$key] = $value;
            }
        }

        return $flattened;
    }

    private function applySetupChangeRequestValues(int $production_order_id, string $change_type, array $oldValue, array $newValue, int $approved_by, string $now): void
    {
        switch ($change_type) {
            case 'raw_tank':
                if (!isset($oldValue['raw_tank_id'])) {
                    throw new ProductionOrderNotFoundException('Thiếu raw_tank_id cũ để xác định bản ghi cần cập nhật');
                }
                $whereField = ['production_order_id' => $production_order_id];
                $whereField['raw_tank_id'] = (int)$oldValue['raw_tank_id'];
                $updateData = [];
                foreach (['raw_tank_id', 'planned_volume_kg', 'actual_volume_kg', 'started_at', 'ended_at', 'notes', 'setup_status'] as $field) {
                    if (array_key_exists($field, $newValue)) {
                        $updateData[$field] = $newValue[$field];
                    }
                }
                $table = 'eudr_production_order_raw_tank_setup';
                break;

            case 'settling_tank':
                $whereField = ['production_order_id' => $production_order_id];
                if (isset($oldValue['settling_tank_id'])) {
                    $whereField['settling_tank_id'] = (int)$oldValue['settling_tank_id'];
                }
                $updateData = [];
                foreach (['settling_tank_id', 'settling_duration_hours', 'started_at', 'ended_at', 'notes', 'setup_status'] as $field) {
                    if (array_key_exists($field, $newValue)) {
                        $updateData[$field] = $newValue[$field];
                    }
                }
                $table = 'eudr_production_order_settling_tank_setup';
                break;

            case 'channel':
                if (!isset($oldValue['channel_id'])) {
                    throw new ProductionOrderNotFoundException('Thiếu channel_id cũ để xác định bản ghi cần cập nhật');
                }
                $whereField = ['production_order_id' => $production_order_id];
                $whereField['channel_id'] = (int)$oldValue['channel_id'];
                $updateData = [];
                foreach (['channel_id', 'planned_volume_kg', 'coagulation_agent_type', 'coagulation_agent_volume', 'started_at', 'ended_at', 'notes', 'setup_status'] as $field) {
                    if (array_key_exists($field, $newValue)) {
                        $updateData[$field] = $newValue[$field];
                    }
                }
                $table = 'eudr_production_order_channel_setup';
                break;

            case 'cutting_machine':
                $whereField = ['production_order_id' => $production_order_id];
                $updateData = [];
                foreach (['cutting_machine_id', 'started_at', 'ended_at', 'notes', 'setup_status'] as $field) {
                    if (array_key_exists($field, $newValue)) {
                        $updateData[$field] = $newValue[$field];
                    }
                }
                $table = 'eudr_production_order_cutting_machine_setup';
                break;

            case 'roller':
                if (!isset($oldValue['grade_id']) || !isset($oldValue['quality_type'])) {
                    throw new ProductionOrderNotFoundException('Thiếu grade_id hoặc quality_type cũ để xác định bản ghi cần cập nhật');
                }
                $whereField = ['production_order_id' => $production_order_id];
                $whereField['grade_id'] = (int)$oldValue['grade_id'];
                $whereField['quality_type'] = (string)$oldValue['quality_type'];
                $updateData = [];
                foreach (['grade_id', 'quality_type', 'roller_id', 'expected_output_thickness_min_mm', 'expected_output_thickness_max_mm', 'started_at', 'ended_at', 'notes', 'setup_status'] as $field) {
                    if (array_key_exists($field, $newValue)) {
                        $updateData[$field] = $newValue[$field];
                    }
                }
                $table = 'eudr_production_order_roller_setup_by_quality';
                break;

            case 'hanging':
                $whereField = ['production_order_id' => $production_order_id];
                $updateData = [];
                foreach (['gong_cart_id', 'expected_hanging_hours', 'started_at', 'ended_at', 'notes', 'setup_status'] as $field) {
                    if (array_key_exists($field, $newValue)) {
                        $updateData[$field] = $newValue[$field];
                    }
                }
                $table = 'eudr_production_order_hanging_setup';
                break;

            case 'drying':
                $whereField = ['production_order_id' => $production_order_id];
                $updateData = [];
                foreach (['oven_id', 'expected_drying_hours', 'expected_final_moisture_percent', 'started_at', 'ended_at', 'notes', 'setup_status'] as $field) {
                    if (array_key_exists($field, $newValue)) {
                        $updateData[$field] = $newValue[$field];
                    }
                }
                $table = 'eudr_production_order_drying_setup';
                break;

            case 'pressing':
                if (!isset($oldValue['grade_id'])) {
                    throw new ProductionOrderNotFoundException('Thiếu grade_id cũ để xác định bản ghi cần cập nhật');
                }
                $whereField = ['production_order_id' => $production_order_id];
                $whereField['grade_id'] = (int)$oldValue['grade_id'];
                $updateData = [];
                foreach (['grade_id', 'planned_sheet_quantity', 'started_at', 'ended_at', 'notes', 'setup_status'] as $field) {
                    if (array_key_exists($field, $newValue)) {
                        $updateData[$field] = $newValue[$field];
                    }
                }
                $table = 'eudr_production_order_pressing_setup';
                break;

            case 'pallet':
                $whereField = ['production_order_id' => $production_order_id];
                $updateData = [];
                foreach (['planned_pallet_quantity', 'started_at', 'ended_at', 'notes', 'setup_status'] as $field) {
                    if (array_key_exists($field, $newValue)) {
                        $updateData[$field] = $newValue[$field];
                    }
                }
                $table = 'eudr_production_order_pallet_setup';
                break;

            default:
                return;
        }

        if (empty($updateData)) {
            return;
        }

        $updateData['updated_at'] = $now;
        $updateData['updated_by'] = $approved_by;

        foreach ($whereField as $field => $value) {
            $this->db->where($field, $value);
        }
        $this->db->update($table, $updateData);
    }

    private function applySetupStepTimeOverrides(int $production_order_id, array $step_time_overrides, int $approved_by, string $now): void
    {
        $stepTableMap = [
            'raw_tank' => 'eudr_production_order_raw_tank_setup',
            'settling_tank' => 'eudr_production_order_settling_tank_setup',
            'channel' => 'eudr_production_order_channel_setup',
            'cutting_machine' => 'eudr_production_order_cutting_machine_setup',
            'roller' => 'eudr_production_order_roller_setup_by_quality',
            'hanging' => 'eudr_production_order_hanging_setup',
            'drying' => 'eudr_production_order_drying_setup',
            'pressing' => 'eudr_production_order_pressing_setup',
            'pallet' => 'eudr_production_order_pallet_setup',
        ];

        foreach ($step_time_overrides as $stepOverride) {
            if (!is_array($stepOverride)) {
                continue;
            }

            $step = (string)($stepOverride['step'] ?? '');
            if (!isset($stepTableMap[$step])) {
                continue;
            }

            $updateData = [
                'updated_at' => $now,
                'updated_by' => $approved_by,
            ];

            if (array_key_exists('started_at', $stepOverride)) {
                $updateData['started_at'] = $stepOverride['started_at'];
            }
            if (array_key_exists('ended_at', $stepOverride)) {
                $updateData['ended_at'] = $stepOverride['ended_at'];
            }

            if (!array_key_exists('started_at', $updateData) && !array_key_exists('ended_at', $updateData)) {
                continue;
            }

            $this->db->where('production_order_id', $production_order_id);
            $this->db->where('deleted_by', 0);
            $this->db->update($stepTableMap[$step], $updateData);
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
        $contract_code = $params['contract_code'] ?? '';
        $factory_id = $params['factory_id'] ?? null;
        $product_type_category = $params['product_type_category'] ?? 'all';
        $status = $params['status'] ?? 'all';
        $production_date_from = $params['production_date_from'] ?? null;
        $production_date_to = $params['production_date_to'] ?? null;
        $created_date_from = $params['created_date_from'] ?? null;
        $created_date_to = $params['created_date_to'] ?? null;
        $companyIdParam = $company_id_param ?? 0;

       // Count total records
        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'po');
        if (!empty($search)) {
            $this->db->where('(po.production_order_name LIKE ? OR po.production_order_code LIKE ?)', ["%$search%", "%$search%"]);
        }
        if ($product_type_category !== 'all') {
            $this->db->where("po.product_type_category", $product_type_category);
        }
        if ($status !== 'all') {
            $this->db->where("po.status", $status);
        }
        if (!empty($production_date_from)) {
            $this->db->where("po.production_date", $production_date_from, ">=");
        }
        if (!empty($production_date_to)) {
            $this->db->where("po.production_date", $production_date_to, "<=");
        }
        if (!empty($created_date_from)) {
            $this->db->where("DATE(po.created_at)", $created_date_from, ">=");
        }
        if (!empty($created_date_to)) {
            $this->db->where("DATE(po.created_at)", $created_date_to, "<=");
        }
        if (!empty($factory_id)) {
            $this->db->where("po.factory_id", $factory_id);
        }
        $total_records = (int)$this->db->getValue("eudr_production_orders po", "count(*)");


        // Set pagination
        $this->db->pageLimit = $page_limit;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'po');
        if (!empty($search)) {
            $this->db->where('(po.production_order_name LIKE ? OR po.production_order_code LIKE ?)', ["%$search%", "%$search%"]);
        }
        if ($product_type_category !== 'all') {
            $this->db->where("po.product_type_category", $product_type_category);
        }
        if ($status !== 'all') {
            $this->db->where("po.status", $status);
        }
        if (!empty($production_date_from)) {
            $this->db->where("po.production_date", $production_date_from, ">=");
        }
        if (!empty($production_date_to)) {
            $this->db->where("po.production_date", $production_date_to, "<=");
        }
        if (!empty($created_date_from)) {
            $this->db->where("DATE(po.created_at)", $created_date_from, ">=");
        }
        if (!empty($created_date_to)) {
            $this->db->where("DATE(po.created_at)", $created_date_to, "<=");
        }
        if (!empty($factory_id)) {
            $this->db->where("po.factory_id", $factory_id);
        }

        $cols = "po.*, pt.product_type_name, f.factory_name";

        if (!empty($params['order_by'])) {
            $this->db->orderBy('po.'.$params['order_by'], $params['order_type']??'ASC');
        } else {
            $this->db->orderBy("po.production_order_id", "DESC");
        }
        $this->db->join("eudr_production_product_types pt", "pt.product_type_id = po.product_type_id", "LEFT");
        $this->db->join("eudr_factories f", "f.factory_id = po.factory_id", "LEFT");
        $records = $this->db->arraybuilder()->paginate("eudr_production_orders po", $page, $cols);

        $productTypesByOrder = [];
        $productionOrderIds = [];
        if (is_array($records) && !empty($records)) {
            foreach ($records as $record) {
                $productionOrderIds[] = (int)($record['production_order_id'] ?? 0);
            }
            $productionOrderIds = array_values(array_unique(array_filter($productionOrderIds)));
        }

        if (!empty($productionOrderIds)) {
            $this->db->where('s.production_order_id', $productionOrderIds, 'IN');
            $this->db->where('s.deleted_by', 0);
            $this->db->join('eudr_production_grades g', 'g.grade_id = s.grade_id', 'LEFT');
            $this->db->join('eudr_production_product_types p', 'p.product_type_id = s.product_type_id', 'LEFT');
            $this->db->orderBy('s.production_order_id', 'ASC');
            $this->db->orderBy('s.order_pressing_setup_id', 'ASC');

            $productRows = $this->db->arraybuilder()->get(
                'eudr_production_order_pressing_setup s',
                null,
                's.production_order_id, s.planned_sheet_quantity, g.grade_code, g.name AS grade_name, p.product_type_code, p.product_type_name'
            ) ?? [];

            foreach ($productRows as $row) {
                $orderId = (int)($row['production_order_id'] ?? 0);
                if ($orderId <= 0) {
                    continue;
                }

                $productTypesByOrder[$orderId][] = [
                    'grade_code' => (string)($row['grade_code'] ?? ''),
                    'grade_name' => (string)($row['grade_name'] ?? ''),
                    'planned_sheet_quantity' => (int)($row['planned_sheet_quantity'] ?? 0),
                    'product_type_code' => (string)($row['product_type_code'] ?? ''),
                    'product_type_name' => (string)($row['product_type_name'] ?? ''),
                ];
            }
        }

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $orderId = (int)($item['production_order_id'] ?? 0);
                $item['production_product_types'] = $productTypesByOrder[$orderId] ?? [];
                $items[] = new ProductionOrder($item['production_order_id'], $item);
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
    public function findProductionOrderOfId(int $production_order_id): ?ProductionOrder
    {
        $this->db->where("po.production_order_id", $production_order_id);
        $this->db->where("po.deleted_by", 0);
        $this->db->join("eudr_production_product_types pt", "pt.product_type_id = po.product_type_id", "LEFT");
        $this->db->join("eudr_factories f", "f.factory_id = po.factory_id", "LEFT");
        $production_order = $this->db->getOne("eudr_production_orders po", "po.*, pt.product_type_name, f.factory_name");
        if (empty($production_order)) {
            return null;
        }
        return new ProductionOrder($production_order['production_order_id'], $production_order);
    }

    /**
     * {@inheritdoc}
     */
    public function findProductionOrderOfIdWithPermission(int $production_order_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductionOrder
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'po');
        $this->db->where('po.production_order_id', $production_order_id);
        $this->db->join('eudr_production_product_types pt', 'pt.product_type_id = po.product_type_id', 'LEFT');
        $this->db->join('eudr_factories f', 'f.factory_id = po.factory_id', 'LEFT');

        $production_order = $this->db->getOne('eudr_production_orders po', 'po.*, pt.product_type_name, f.factory_name');
        if (empty($production_order)) {
            return null;
        }

        return new ProductionOrder($production_order['production_order_id'], $production_order);
    }


    /**
     * {@inheritdoc}
     */
    public function findProductionOrderOfCode(string $code): ?ProductionOrder
    {
        $this->db->where("po.production_order_code", $code);
        //$this->db->where("po.deleted_by", 0);
        $this->db->join("eudr_production_product_types pt", "pt.product_type_id = po.product_type_id", "LEFT");
        $this->db->join("eudr_factories f", "f.factory_id = po.factory_id", "LEFT");
        $production_order = $this->db->getOne("eudr_production_orders po", "po.*, pt.product_type_name, f.factory_name");
        if (empty($production_order)) {
            return null;
        }
        return new ProductionOrder($production_order['production_order_id'], $production_order);
    }

    /**
     * {@inheritdoc}
     */
    public function findProductionOrderOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductionOrder
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'po');
        $this->db->where('po.production_order_code', $code);
        $this->db->join('eudr_production_product_types pt', 'pt.product_type_id = po.product_type_id', 'LEFT');
        $this->db->join('eudr_factories f', 'f.factory_id = po.factory_id', 'LEFT');

        $production_order = $this->db->getOne('eudr_production_orders po', 'po.*, pt.product_type_name, f.factory_name');
        if (empty($production_order)) {
            return null;
        }

        return new ProductionOrder($production_order['production_order_id'], $production_order);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = "ppod-".date("ymd").'-'.Utils::generateRandomString(8);
            $production_order = $this->findProductionOrderOfCode($code);
            if (!$production_order) {
                break;
            }
        }
        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function createProductionOrder(array $data): ?ProductionOrder
    {
        $this->db->insert("eudr_production_orders", $data);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }

        $production_order_id = $this->db->getInsertId();

        return $this->findProductionOrderOfId($production_order_id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateProductionOrder(int $production_order_id, array $data_update): ProductionOrder
    {
        $this->db->where("production_order_id", $production_order_id);
        $this->db->update("eudr_production_orders", $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new ProductionOrderNotFoundException("Production Order not found with ID: $production_order_id");
        }
        return $this->findProductionOrderOfId($production_order_id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateProductionOrderWithPermission(int $production_order_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ProductionOrder
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, '');
        $this->db->where('production_order_id', $production_order_id);
        $this->db->update('eudr_production_orders', $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new ProductionOrderNotFoundException("Production Order not found with ID: $production_order_id");
        }

        return $this->findProductionOrderOfId($production_order_id);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteProductionOrder(int $production_order_id, int $deleted_by): void
    {
        $this->db->where("production_order_id", $production_order_id);
        $this->db->update('eudr_production_orders', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteProductionOrderWithPermission(int $production_order_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, '');
        $this->db->where('production_order_id', $production_order_id);
        $this->db->update('eudr_production_orders', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

    /**
     * {@inheritdoc}
     */
    public function setupRawTank(int $production_order_id, int $raw_tank_id, float $planned_volume_kg, ?float $actual_volume_kg, ?string $started_at, ?string $ended_at, ?string $notes, int $company_id, int $factory_id, int $user_id): array
    {
        // Validate raw tank exists and belongs to company
        $this->db->where('raw_material_tank_id', $raw_tank_id);
        $this->db->where('company_id', $company_id);
        $this->db->where('deleted_by', 0);
        $rawTank = $this->db->getOne('eudr_tanks_raw_material', 'raw_material_tank_id, raw_material_tank_name, raw_material_tank_code');
        if (empty($rawTank)) {
            throw new ProductionOrderNotFoundException("Không tìm thấy bồn nguyên liệu thô với ID: $raw_tank_id");
        }

        // Upsert: check existing row
        $this->db->where('production_order_id', $production_order_id);
        $this->db->where('raw_tank_id', $raw_tank_id);
        $existing = $this->db->getOne('eudr_production_order_raw_tank_setup', 'order_raw_tank_setup_id');

        if (!empty($existing)) {
            $updateData = [
                'planned_volume_kg' => $planned_volume_kg,
                'notes'             => $notes ?? '',
                'updated_at'        => date('Y-m-d H:i:s'),
                'updated_by'        => $user_id,
            ];
            if ($started_at !== null) {
                $updateData['started_at'] = $started_at;
            }
            if ($ended_at !== null) {
                $updateData['ended_at'] = $ended_at;
            }
            if ($actual_volume_kg !== null) {
                $updateData['actual_volume_kg'] = $actual_volume_kg;
            }
            $this->db->where('order_raw_tank_setup_id', $existing['order_raw_tank_setup_id']);
            $this->db->update('eudr_production_order_raw_tank_setup', $updateData);
        } else {
            $insertData = [
                'production_order_id' => $production_order_id,
                'company_id'          => $company_id,
                'factory_id'          => $factory_id,
                'raw_tank_id'         => $raw_tank_id,
                'planned_volume_kg'   => $planned_volume_kg,
                'actual_volume_kg'    => $actual_volume_kg,
                'started_at'          => $started_at,
                'ended_at'            => $ended_at,
                'notes'               => $notes ?? '',
                'setup_status'        => 'active',
                'created_at'          => date('Y-m-d H:i:s'),
                'created_by'          => $user_id,
            ];
            $this->db->insert('eudr_production_order_raw_tank_setup', $insertData);
        }

        // Return all raw tank setups for this order
        $this->db->where('s.production_order_id', $production_order_id);
        $this->db->orderBy('s.order_raw_tank_setup_id', 'ASC');
        $this->db->join('eudr_tanks_raw_material t', 't.raw_material_tank_id = s.raw_tank_id', 'LEFT');
        return $this->db->get('eudr_production_order_raw_tank_setup s', null,
            's.*, t.raw_material_tank_name, t.raw_material_tank_code') ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function setupSettlingTank(int $production_order_id, int $settling_tank_id, ?int $settling_duration_hours, ?string $started_at, ?string $ended_at, ?string $notes, int $company_id, int $factory_id, int $user_id): array
    {
        // Validate settling tank exists and belongs to company
        $this->db->where('settling_tank_id', $settling_tank_id);
        $this->db->where('company_id', $company_id);
        $this->db->where('deleted_by', 0);
        $settlingTank = $this->db->getOne('eudr_production_settling_tanks', 'settling_tank_id, settling_tank_name, settling_tank_code');
        if (empty($settlingTank)) {
            throw new ProductionOrderNotFoundException("Không tìm thấy bồn lắng đọng với ID: $settling_tank_id");
        }

        // Upsert: only ONE settling tank per production order
        $this->db->where('production_order_id', $production_order_id);
        $existing = $this->db->getOne('eudr_production_order_settling_tank_setup', 'order_settling_tank_setup_id');

        if (!empty($existing)) {
            $updateData = [
                'settling_tank_id'        => $settling_tank_id,
                'settling_duration_hours' => $settling_duration_hours,
                'notes'                   => $notes ?? '',
                'updated_at'              => date('Y-m-d H:i:s'),
                'updated_by'              => $user_id,
            ];
            if ($started_at !== null) {
                $updateData['started_at'] = $started_at;
            }
            if ($ended_at !== null) {
                $updateData['ended_at'] = $ended_at;
            }
            $this->db->where('order_settling_tank_setup_id', $existing['order_settling_tank_setup_id']);
            $this->db->update('eudr_production_order_settling_tank_setup', $updateData);
        } else {
            $insertData = [
                'production_order_id'     => $production_order_id,
                'company_id'              => $company_id,
                'factory_id'              => $factory_id,
                'settling_tank_id'        => $settling_tank_id,
                'settling_duration_hours' => $settling_duration_hours,
                'started_at'              => $started_at,
                'ended_at'                => $ended_at,
                'notes'                   => $notes ?? '',
                'setup_status'            => 'active',
                'created_at'              => date('Y-m-d H:i:s'),
                'created_by'              => $user_id,
            ];
            $this->db->insert('eudr_production_order_settling_tank_setup', $insertData);
        }

        // Return the settling tank setup row with tank name
        $this->db->where('s.production_order_id', $production_order_id);
        $this->db->join('eudr_production_settling_tanks t', 't.settling_tank_id = s.settling_tank_id', 'LEFT');
        return $this->db->getOne('eudr_production_order_settling_tank_setup s', 's.*, t.settling_tank_name, t.settling_tank_code') ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function setupChannel(int $production_order_id, int $channel_id, float $planned_volume_kg, ?string $coagulation_agent_type, ?string $coagulation_agent_volume, ?string $started_at, ?string $ended_at, ?string $notes, int $company_id, int $factory_id, int $user_id): array
    {
        // Validate channel exists and belongs to company
        $this->db->where('channel_id', $channel_id);
        $this->db->where('company_id', $company_id);
        $this->db->where('deleted_by', 0);
        $channel = $this->db->getOne('eudr_production_channels', 'channel_id, channel_name, channel_code');
        if (empty($channel)) {
            throw new ProductionOrderNotFoundException("Không tìm thấy mương với ID: $channel_id");
        }

        // Upsert: one row per (production_order_id, channel_id)
        $this->db->where('production_order_id', $production_order_id);
        $this->db->where('channel_id', $channel_id);
        $existing = $this->db->getOne('eudr_production_order_channel_setup', 'order_channel_setup_id');

        if (!empty($existing)) {
            $updateData = [
                'planned_volume_kg'       => $planned_volume_kg,
                'coagulation_agent_type'  => $coagulation_agent_type,
                'coagulation_agent_volume'=> $coagulation_agent_volume,
                'notes'                   => $notes ?? '',
                'updated_at'              => date('Y-m-d H:i:s'),
                'updated_by'              => $user_id,
            ];
            if ($started_at !== null) {
                $updateData['started_at'] = $started_at;
            }
            if ($ended_at !== null) {
                $updateData['ended_at'] = $ended_at;
            }
            $this->db->where('order_channel_setup_id', $existing['order_channel_setup_id']);
            $this->db->update('eudr_production_order_channel_setup', $updateData);
        } else {
            $insertData = [
                'production_order_id'      => $production_order_id,
                'company_id'               => $company_id,
                'factory_id'               => $factory_id,
                'channel_id'               => $channel_id,
                'planned_volume_kg'        => $planned_volume_kg,
                'coagulation_agent_type'   => $coagulation_agent_type,
                'coagulation_agent_volume' => $coagulation_agent_volume,
                'started_at'               => $started_at,
                'ended_at'                 => $ended_at,
                'notes'                    => $notes ?? '',
                'setup_status'             => 'active',
                'created_at'               => date('Y-m-d H:i:s'),
                'created_by'               => $user_id,
            ];
            $this->db->insert('eudr_production_order_channel_setup', $insertData);
        }

        // Return all channel setups for this order
        $this->db->where('s.production_order_id', $production_order_id);
        $this->db->orderBy('s.order_channel_setup_id', 'ASC');
        $this->db->join('eudr_production_channels c', 'c.channel_id = s.channel_id', 'LEFT');
        return $this->db->get(
            'eudr_production_order_channel_setup s',
            null,
            's.*, c.channel_name, c.channel_code'
        ) ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function setupCuttingMachine(int $production_order_id, int $cutting_machine_id, float $expected_cutting_weight_kg, int $expected_sheet_quantity, ?string $started_at, ?string $ended_at, ?string $notes, int $company_id, int $factory_id, int $user_id): array
    {
        // Validate cutting machine exists and belongs to company
        $this->db->where('cutting_machine_id', $cutting_machine_id);
        $this->db->where('company_id', $company_id);
        $this->db->where('deleted_by', 0);
        $machine = $this->db->getOne('eudr_production_cutting_machines', 'cutting_machine_id, cutting_machine_name, cutting_machine_code');
        if (empty($machine)) {
            throw new ProductionOrderNotFoundException("Không tìm thấy máy cắt với ID: $cutting_machine_id");
        }

        // Upsert: only ONE cutting machine setup per production order
        $this->db->where('production_order_id', $production_order_id);
        $existing = $this->db->getOne('eudr_production_order_cutting_machine_setup', 'order_cutting_machine_setup_id');

        if (!empty($existing)) {
            $updateData = [
                'cutting_machine_id' => $cutting_machine_id,
                'expected_cutting_weight_kg' => $expected_cutting_weight_kg,
                'expected_sheet_quantity'    => $expected_sheet_quantity,
                'notes'                      => $notes ?? '',
                'updated_at'                 => date('Y-m-d H:i:s'),
                'updated_by'                 => $user_id,
            ];
            if ($started_at !== null) {
                $updateData['started_at'] = $started_at;
            }
            if ($ended_at !== null) {
                $updateData['ended_at'] = $ended_at;
            }
            $this->db->where('order_cutting_machine_setup_id', $existing['order_cutting_machine_setup_id']);
            $this->db->update('eudr_production_order_cutting_machine_setup', $updateData);
        } else {
            $insertData = [
                'production_order_id' => $production_order_id,
                'company_id'          => $company_id,
                'factory_id'          => $factory_id,
                'cutting_machine_id'  => $cutting_machine_id,
                'started_at'          => $started_at,
                'expected_cutting_weight_kg' => $expected_cutting_weight_kg,
                'expected_sheet_quantity'    => $expected_sheet_quantity,
                'ended_at'                   => $ended_at,
                'notes'                      => $notes ?? '',
                'setup_status'        => 'active',
                'created_at'          => date('Y-m-d H:i:s'),
                'created_by'          => $user_id,
            ];
            $this->db->insert('eudr_production_order_cutting_machine_setup', $insertData);
        }

        // Return cutting machine setup row with machine details
        $this->db->where('s.production_order_id', $production_order_id);
        $this->db->join('eudr_production_cutting_machines m', 'm.cutting_machine_id = s.cutting_machine_id', 'LEFT');
        return $this->db->getOne(
            'eudr_production_order_cutting_machine_setup s',
            's.*, m.cutting_machine_name, m.cutting_machine_code'
        ) ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function setupRollerByQuality(int $production_order_id, int $grade_id, string $quality_type, int $roller_id, ?float $expected_output_thickness_min_mm, ?float $expected_output_thickness_max_mm, ?string $started_at, ?string $ended_at, int $expected_sheet_quantity, ?string $notes, int $company_id, int $factory_id, int $user_id): array
    {
        // Validate grade exists
        /*
        $this->db->where('grade_id', $grade_id);
        $this->db->where('deleted_by', 0);
        $grade = $this->db->getOne('eudr_production_grades', 'grade_id, grade_code, name');
        if (empty($grade)) {
            throw new ProductionOrderNotFoundException("Không tìm thấy loại chất lượng với ID: $grade_id");
        }
        */

        // Validate roller exists and belongs to company
        $this->db->where('roller_id', $roller_id);
        $this->db->where('company_id', $company_id);
        $this->db->where('deleted_by', 0);
        $roller = $this->db->getOne('eudr_production_rollers', 'roller_id, roller_code, roller_name');
        if (empty($roller)) {
            throw new ProductionOrderNotFoundException("Không tìm thấy máy cán với ID: $roller_id");
        }

        // Upsert: one row per (production_order_id, grade_id)
        $this->db->where('production_order_id', $production_order_id);
        $this->db->where('grade_id', $grade_id);
        $this->db->where('quality_type', $quality_type);
        $existing = $this->db->getOne('eudr_production_order_roller_setup_by_quality', 'order_roller_setup_quality_id');

        if (!empty($existing)) {
            $updateData = [
                'roller_id'                        => $roller_id,
                'expected_output_thickness_min_mm' => $expected_output_thickness_min_mm,
                'expected_output_thickness_max_mm' => $expected_output_thickness_max_mm,
                'expected_sheet_quantity'          => $expected_sheet_quantity,
                'notes'                            => $notes ?? '',
                'updated_at'                       => date('Y-m-d H:i:s'),
                'updated_by'                       => $user_id,
            ];
            if ($started_at !== null) {
                $updateData['started_at'] = $started_at;
            }
            if ($ended_at !== null) {
                $updateData['ended_at'] = $ended_at;
            }
            $this->db->where('order_roller_setup_quality_id', $existing['order_roller_setup_quality_id']);
            $this->db->update('eudr_production_order_roller_setup_by_quality', $updateData);
        } else {
            $insertData = [
                'production_order_id'               => $production_order_id,
                'company_id'                        => $company_id,
                'factory_id'                        => $factory_id,
                'grade_id'                          => $grade_id,
                'quality_type'                      => $quality_type,
                'roller_id'                         => $roller_id,
                'expected_output_thickness_min_mm'  => $expected_output_thickness_min_mm,
                'expected_output_thickness_max_mm'  => $expected_output_thickness_max_mm,
                'expected_sheet_quantity'           => $expected_sheet_quantity,
                'started_at'                        => $started_at,
                'ended_at'                          => $ended_at,
                'notes'                             => $notes ?? '',
                'setup_status'                      => 'active',
                'created_at'                        => date('Y-m-d H:i:s'),
                'created_by'                        => $user_id,
            ];
            $this->db->insert('eudr_production_order_roller_setup_by_quality', $insertData);
        }

        // Return all roller setups for this order
        $this->db->where('s.production_order_id', $production_order_id);
        $this->db->orderBy('s.order_roller_setup_quality_id', 'ASC');
        $this->db->join('eudr_production_grades g', 'g.grade_id = s.grade_id', 'LEFT');
        $this->db->join('eudr_production_rollers r', 'r.roller_id = s.roller_id', 'LEFT');
        return $this->db->get(
            'eudr_production_order_roller_setup_by_quality s',
            null,
            's.*, g.grade_code, g.name AS grade_name, r.roller_code, r.roller_name'
        ) ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function setupHanging(int $production_order_id, int $gong_cart_id, ?int $expected_hanging_hours, ?string $started_at, ?string $ended_at, ?array $details, ?string $notes, int $company_id, int $factory_id, int $user_id): array
    {
        if (!is_array($details) || count($details) === 0) {
            throw new \InvalidArgumentException('details phải có ít nhất 1 nhóm quality hợp lệ');
        }

        $allowedQualityTypes = Utils::getAllowedQualityTypes();
        $seenQualityTypes = [];
        $seenPoles = [];
        $normalizedDetails = [];

        foreach ($details as $item) {
            if (!is_array($item)) {
                throw new \InvalidArgumentException('Mỗi phần tử details phải là object hợp lệ');
            }

            $qualityType = trim((string)($item['quality_type'] ?? ''));
            if ($qualityType === '' || !in_array($qualityType, $allowedQualityTypes, true)) {
                throw new \InvalidArgumentException('quality_type không hợp lệ trong details');
            }
            if (in_array($qualityType, $seenQualityTypes, true)) {
                throw new \InvalidArgumentException('quality_type bị trùng trong details: ' . $qualityType);
            }

            $inputSheetCount = (int)($item['input_sheet_count'] ?? -1);
            if ($inputSheetCount < 0) {
                throw new \InvalidArgumentException('input_sheet_count phải >= 0');
            }

            $poleNumbers = $item['pole_numbers'] ?? null;
            if (!is_array($poleNumbers) || count($poleNumbers) === 0) {
                throw new \InvalidArgumentException('pole_numbers phải là mảng và không được rỗng trong details');
            }

            $normalizedPoleNumbers = [];
            foreach ($poleNumbers as $poleNoRaw) {
                $poleNo = (int)$poleNoRaw;
                if ($poleNo <= 0) {
                    throw new \InvalidArgumentException('pole_numbers phải chứa số nguyên >= 1');
                }
                if (in_array($poleNo, $seenPoles, true)) {
                    throw new \InvalidArgumentException('Một sào chỉ được gán cho 1 quality. Sào bị trùng: ' . $poleNo);
                }
                $seenPoles[] = $poleNo;
                $normalizedPoleNumbers[] = $poleNo;
            }

            $seenQualityTypes[] = $qualityType;
            $normalizedDetails[] = [
                'quality_type' => $qualityType,
                'input_sheet_count' => $inputSheetCount,
                'pole_numbers' => array_values(array_unique($normalizedPoleNumbers)),
                'notes' => isset($item['notes']) ? trim((string)$item['notes']) : null,
            ];
        }

        // Validate gong cart exists and belongs to company
        $this->db->where('gong_cart_id', $gong_cart_id);
        $this->db->where('company_id', $company_id);
        $this->db->where('deleted_by', 0);
        $gongCart = $this->db->getOne('eudr_production_gong_carts', 'gong_cart_id, gong_cart_code, gong_cart_name, max_poles');
        if (empty($gongCart)) {
            throw new ProductionOrderNotFoundException("Không tìm thấy xe gòong với ID: $gong_cart_id");
        }
        $maxPoles = (int)($gongCart['max_poles'] ?? 0);
        if ($maxPoles <= 0) {
            throw new \InvalidArgumentException('Xe gòong chưa được cấu hình số sào hợp lệ (max_poles)');
        }

        foreach ($normalizedDetails as $detail) {
            foreach ($detail['pole_numbers'] as $poleNo) {
                if ($poleNo > $maxPoles) {
                    throw new \InvalidArgumentException('pole_numbers chứa sào vượt quá max_poles của xe gòong');
                }
            }
        }

        $now = date('Y-m-d H:i:s');
        $orderHangingSetupId = 0;
        $this->db->startTransaction();

        // Upsert: only ONE hanging setup per production order
        $this->db->where('production_order_id', $production_order_id);
        $existing = $this->db->getOne('eudr_production_order_hanging_setup', 'order_hanging_setup_id');

        if (!empty($existing)) {
            $orderHangingSetupId = (int)$existing['order_hanging_setup_id'];
            $updateData = [
                'gong_cart_id'            => $gong_cart_id,
                'expected_hanging_hours'  => $expected_hanging_hours,
                'notes'                   => $notes ?? '',
                'updated_at'              => $now,
                'updated_by'              => $user_id,
            ];
            if ($started_at !== null) {
                $updateData['started_at'] = $started_at;
            }
            if ($ended_at !== null) {
                $updateData['ended_at'] = $ended_at;
            }
            $this->db->where('order_hanging_setup_id', $orderHangingSetupId);
            $this->db->update('eudr_production_order_hanging_setup', $updateData);
            if ($this->db->getLastErrno() !== 0) {
                $this->db->rollback();
                throw new \RuntimeException('Không thể cập nhật cấu hình xe gòong phơi');
            }
        } else {
            $insertData = [
                'production_order_id'      => $production_order_id,
                'company_id'               => $company_id,
                'factory_id'               => $factory_id,
                'gong_cart_id'             => $gong_cart_id,
                'expected_hanging_hours'   => $expected_hanging_hours,
                'started_at'               => $started_at,
                'ended_at'                 => $ended_at,
                'notes'                    => $notes ?? '',
                'setup_status'             => 'active',
                'created_at'               => $now,
                'created_by'               => $user_id,
            ];
            $this->db->insert('eudr_production_order_hanging_setup', $insertData);
            if ($this->db->getLastErrno() !== 0) {
                $this->db->rollback();
                throw new \RuntimeException('Không thể tạo cấu hình xe gòong phơi');
            }
            $orderHangingSetupId = (int)$this->db->getInsertId();
        }

        $this->db->where('order_hanging_setup_id', $orderHangingSetupId);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_production_order_hanging_setup_poles', [
            'deleted_by' => $user_id,
            'deleted_at' => $now,
            'updated_by' => $user_id,
            'updated_at' => $now,
            'pole_status' => 'cancelled',
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            throw new \RuntimeException('Không thể đồng bộ danh sách sào cần phơi');
        }

        $this->db->where('order_hanging_setup_id', $orderHangingSetupId);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_production_order_hanging_setup_quality_details', [
            'deleted_by' => $user_id,
            'deleted_at' => $now,
            'updated_by' => $user_id,
            'updated_at' => $now,
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            throw new \RuntimeException('Không thể đồng bộ chi tiết quality cho cấu hình phơi');
        }

        $this->db->where('order_hanging_setup_id', $orderHangingSetupId);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_production_order_hanging_setup_quality_pole_assignments', [
            'deleted_by' => $user_id,
            'deleted_at' => $now,
            'updated_by' => $user_id,
            'updated_at' => $now,
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            throw new \RuntimeException('Không thể đồng bộ phân bổ sào theo quality');
        }

        foreach ($normalizedDetails as $detail) {
            $this->db->where('order_hanging_setup_id', $orderHangingSetupId);
            $this->db->where('quality_type', $detail['quality_type']);
            $existingDetail = $this->db->getOne(
                'eudr_production_order_hanging_setup_quality_details',
                'order_hanging_setup_quality_detail_id'
            );

            if (!empty($existingDetail)) {
                $orderHangingSetupQualityDetailId = (int)$existingDetail['order_hanging_setup_quality_detail_id'];
                $this->db->where('order_hanging_setup_quality_detail_id', $orderHangingSetupQualityDetailId);
                $this->db->update('eudr_production_order_hanging_setup_quality_details', [
                    'production_order_id' => $production_order_id,
                    'input_sheet_count' => (int)$detail['input_sheet_count'],
                    'notes' => $detail['notes'] ?? null,
                    'deleted_by' => 0,
                    'deleted_at' => null,
                    'updated_by' => $user_id,
                    'updated_at' => $now,
                ]);
                if ($this->db->getLastErrno() !== 0) {
                    $this->db->rollback();
                    throw new \RuntimeException('Không thể cập nhật chi tiết quality cho cấu hình phơi');
                }
            } else {
                $this->db->insert('eudr_production_order_hanging_setup_quality_details', [
                    'order_hanging_setup_id' => $orderHangingSetupId,
                    'production_order_id' => $production_order_id,
                    'quality_type' => (string)$detail['quality_type'],
                    'input_sheet_count' => (int)$detail['input_sheet_count'],
                    'notes' => $detail['notes'] ?? null,
                    'created_by' => $user_id,
                    'created_at' => $now,
                    'updated_by' => 0,
                    'updated_at' => null,
                    'deleted_by' => 0,
                    'deleted_at' => null,
                ]);
                if ($this->db->getLastErrno() !== 0) {
                    $this->db->rollback();
                    throw new \RuntimeException('Không thể thêm chi tiết quality cho cấu hình phơi');
                }
                $orderHangingSetupQualityDetailId = (int)$this->db->getInsertId();
            }

            foreach ($detail['pole_numbers'] as $poleNo) {
                $this->db->where('order_hanging_setup_id', $orderHangingSetupId);
                $this->db->where('pole_no', (int)$poleNo);
                $existingPole = $this->db->getOne('eudr_production_order_hanging_setup_poles', 'order_hanging_setup_pole_id');

                if (!empty($existingPole)) {
                    $orderHangingSetupPoleId = (int)$existingPole['order_hanging_setup_pole_id'];
                    $this->db->where('order_hanging_setup_pole_id', $orderHangingSetupPoleId);
                    $this->db->update('eudr_production_order_hanging_setup_poles', [
                        'production_order_id' => $production_order_id,
                        'pole_status' => 'active',
                        'deleted_by' => 0,
                        'deleted_at' => null,
                        'updated_by' => $user_id,
                        'updated_at' => $now,
                    ]);
                    if ($this->db->getLastErrno() !== 0) {
                        $this->db->rollback();
                        throw new \RuntimeException('Không thể cập nhật sào cần phơi');
                    }
                } else {
                    $this->db->insert('eudr_production_order_hanging_setup_poles', [
                        'order_hanging_setup_id' => $orderHangingSetupId,
                        'production_order_id' => $production_order_id,
                        'pole_no' => (int)$poleNo,
                        'pole_status' => 'active',
                        'notes' => null,
                        'created_by' => $user_id,
                        'created_at' => $now,
                        'updated_by' => 0,
                        'updated_at' => null,
                        'deleted_by' => 0,
                        'deleted_at' => null,
                    ]);
                    if ($this->db->getLastErrno() !== 0) {
                        $this->db->rollback();
                        throw new \RuntimeException('Không thể thêm sào cần phơi');
                    }
                    $orderHangingSetupPoleId = (int)$this->db->getInsertId();
                }

                $this->db->where('order_hanging_setup_id', $orderHangingSetupId);
                $this->db->where('pole_no', (int)$poleNo);
                $existingAssignment = $this->db->getOne(
                    'eudr_production_order_hanging_setup_quality_pole_assignments',
                    'order_hanging_setup_pole_assignment_id'
                );

                if (!empty($existingAssignment)) {
                    $this->db->where('order_hanging_setup_pole_assignment_id', (int)$existingAssignment['order_hanging_setup_pole_assignment_id']);
                    $this->db->update('eudr_production_order_hanging_setup_quality_pole_assignments', [
                        'production_order_id' => $production_order_id,
                        'order_hanging_setup_quality_detail_id' => $orderHangingSetupQualityDetailId,
                        'order_hanging_setup_pole_id' => $orderHangingSetupPoleId,
                        'quality_type' => (string)$detail['quality_type'],
                        'deleted_by' => 0,
                        'deleted_at' => null,
                        'updated_by' => $user_id,
                        'updated_at' => $now,
                    ]);
                    if ($this->db->getLastErrno() !== 0) {
                        $this->db->rollback();
                        throw new \RuntimeException('Không thể cập nhật phân bổ sào theo quality');
                    }
                } else {
                    $this->db->insert('eudr_production_order_hanging_setup_quality_pole_assignments', [
                        'order_hanging_setup_id' => $orderHangingSetupId,
                        'production_order_id' => $production_order_id,
                        'order_hanging_setup_quality_detail_id' => $orderHangingSetupQualityDetailId,
                        'order_hanging_setup_pole_id' => $orderHangingSetupPoleId,
                        'quality_type' => (string)$detail['quality_type'],
                        'pole_no' => (int)$poleNo,
                        'notes' => null,
                        'created_by' => $user_id,
                        'created_at' => $now,
                        'updated_by' => 0,
                        'updated_at' => null,
                        'deleted_by' => 0,
                        'deleted_at' => null,
                    ]);
                    if ($this->db->getLastErrno() !== 0) {
                        $this->db->rollback();
                        throw new \RuntimeException('Không thể thêm phân bổ sào theo quality');
                    }
                }
            }
        }

        $this->db->commit();

        // Return hanging setup row with gong cart details
        $this->db->where('s.production_order_id', $production_order_id);
        $this->db->join('eudr_production_gong_carts g', 'g.gong_cart_id = s.gong_cart_id', 'LEFT');
        $hangingSetup = $this->db->getOne(
            'eudr_production_order_hanging_setup s',
            's.*, g.gong_cart_code, g.gong_cart_name'
        ) ?? [];

        if (!empty($hangingSetup) && !empty($hangingSetup['order_hanging_setup_id'])) {
            $hangingSetup['details'] = $this->getHangingSetupDetails((int)$hangingSetup['order_hanging_setup_id']);
            $hangingSetup['pole_numbers'] = $this->getHangingSetupPoleNumbers((int)$hangingSetup['order_hanging_setup_id']);
        }

        return $hangingSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function setupDrying(int $production_order_id, int $oven_id, ?int $expected_drying_hours, ?float $expected_final_moisture_percent, ?string $started_at, ?string $ended_at, ?string $notes, int $company_id, int $factory_id, int $user_id): array
    {
        // Validate oven exists and belongs to company
        $this->db->where('oven_id', $oven_id);
        $this->db->where('company_id', $company_id);
        $this->db->where('deleted_by', 0);
        $oven = $this->db->getOne('eudr_production_ovens', 'oven_id, oven_code, oven_name');
        if (empty($oven)) {
            throw new ProductionOrderNotFoundException("Không tìm thấy máy sấy với ID: $oven_id");
        }

        // Upsert: only ONE drying setup per production order
        $this->db->where('production_order_id', $production_order_id);
        $existing = $this->db->getOne('eudr_production_order_drying_setup', 'order_drying_setup_id');

        if (!empty($existing)) {
            $updateData = [
                'oven_id' => $oven_id,
                'expected_drying_hours' => $expected_drying_hours,
                'expected_final_moisture_percent' => $expected_final_moisture_percent,
                'notes' => $notes ?? '',
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => $user_id,
            ];
            if ($started_at !== null) {
                $updateData['started_at'] = $started_at;
            }
            if ($ended_at !== null) {
                $updateData['ended_at'] = $ended_at;
            }
            $this->db->where('order_drying_setup_id', $existing['order_drying_setup_id']);
            $this->db->update('eudr_production_order_drying_setup', $updateData);
        } else {
            $insertData = [
                'production_order_id' => $production_order_id,
                'company_id' => $company_id,
                'factory_id' => $factory_id,
                'oven_id' => $oven_id,
                'expected_drying_hours' => $expected_drying_hours,
                'expected_final_moisture_percent' => $expected_final_moisture_percent,
                'started_at' => $started_at,
                'ended_at' => $ended_at,
                'notes' => $notes ?? '',
                'setup_status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $user_id,
            ];
            $this->db->insert('eudr_production_order_drying_setup', $insertData);
        }

        // Return drying setup row with oven details
        $this->db->where('s.production_order_id', $production_order_id);
        $this->db->join('eudr_production_ovens o', 'o.oven_id = s.oven_id', 'LEFT');
        return $this->db->getOne(
            'eudr_production_order_drying_setup s',
            's.*, o.oven_code, o.oven_name'
        ) ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getFullSetupOfProductionOrder(int $production_order_id): array
    {
        $stageProgressStatus = [
            'raw_tank' => $this->resolveProductionProgressStatusByTable('eudr_production_channel_runs', $production_order_id),
            'settling_tank' => $this->resolveProductionProgressStatusByTable('eudr_production_channel_runs', $production_order_id),
            'channel' => $this->resolveProductionProgressStatusByTable('eudr_production_channel_runs', $production_order_id),
            'cutting_machine' => $this->resolveProductionProgressStatusByTable('eudr_production_cutting_runs', $production_order_id),
            'roller' => $this->resolveProductionProgressStatusByTable('eudr_production_rolling_runs', $production_order_id),
            'hanging' => $this->resolveProductionProgressStatusByTable('eudr_production_hanging_runs', $production_order_id),
            'drying' => $this->resolveProductionProgressStatusByTable('eudr_production_drying_runs', $production_order_id),
            'pressing' => $this->resolveProductionProgressStatusByTable('eudr_production_pressing_runs', $production_order_id),
            'pallet' => $this->resolveProductionProgressStatusByTable('eudr_production_pallet_runs', $production_order_id),
        ];

        // Step 1: raw tank setups
        $this->db->where('s.production_order_id', $production_order_id);
        $this->db->orderBy('s.order_raw_tank_setup_id', 'ASC');
        $this->db->join('eudr_tanks_raw_material t', 't.raw_material_tank_id = s.raw_tank_id', 'LEFT');
        $rawTankSetups = $this->db->get(
            'eudr_production_order_raw_tank_setup s',
            null,
            's.*, t.raw_material_tank_code, t.raw_material_tank_name'
        ) ?? [];
        $rawTankSetups = $this->appendProgressStatusToList($rawTankSetups, $stageProgressStatus['raw_tank']);

        // Step 2: settling tank setup
        $this->db->where('s.production_order_id', $production_order_id);
        $this->db->join('eudr_production_settling_tanks t', 't.settling_tank_id = s.settling_tank_id', 'LEFT');
        $settlingTankSetup = $this->db->getOne(
            'eudr_production_order_settling_tank_setup s',
            's.*, t.settling_tank_code, t.settling_tank_name'
        ) ?? [];
        if (!empty($settlingTankSetup)) {
            $settlingTankSetup['production_progress_status'] = $stageProgressStatus['settling_tank'];
        }

        // Step 3: channel setups
        $this->db->where('s.production_order_id', $production_order_id);
        $this->db->orderBy('s.order_channel_setup_id', 'ASC');
        $this->db->join('eudr_production_channels c', 'c.channel_id = s.channel_id', 'LEFT');
        $channelSetups = $this->db->get(
            'eudr_production_order_channel_setup s',
            null,
            's.*, c.channel_code, c.channel_name'
        ) ?? [];
        $channelSetups = $this->appendProgressStatusToList($channelSetups, $stageProgressStatus['channel']);

        // Step 4: cutting machine setup
        $this->db->where('s.production_order_id', $production_order_id);
        $this->db->join('eudr_production_cutting_machines m', 'm.cutting_machine_id = s.cutting_machine_id', 'LEFT');
        $cuttingMachineSetup = $this->db->getOne(
            'eudr_production_order_cutting_machine_setup s',
            's.*, m.cutting_machine_code, m.cutting_machine_name'
        ) ?? [];
        if (!empty($cuttingMachineSetup)) {
            $cuttingMachineSetup['production_progress_status'] = $stageProgressStatus['cutting_machine'];
        }

        // Step 5: roller setups by quality
        $this->db->where('s.production_order_id', $production_order_id);
        $this->db->orderBy('s.order_roller_setup_quality_id', 'ASC');
        $this->db->join('eudr_production_grades g', 'g.grade_id = s.grade_id', 'LEFT');
        $this->db->join('eudr_production_rollers r', 'r.roller_id = s.roller_id', 'LEFT');
        $rollerSetupsByQuality = $this->db->get(
            'eudr_production_order_roller_setup_by_quality s',
            null,
            's.*, g.grade_code, g.name AS grade_name, r.roller_code, r.roller_name'
        ) ?? [];
        $rollerSetupsByQuality = $this->appendProgressStatusToList($rollerSetupsByQuality, $stageProgressStatus['roller']);

        // Step 6: hanging setup
        $this->db->where('s.production_order_id', $production_order_id);
        $this->db->join('eudr_production_gong_carts g', 'g.gong_cart_id = s.gong_cart_id', 'LEFT');
        $hangingSetup = $this->db->getOne(
            'eudr_production_order_hanging_setup s',
            's.*, g.gong_cart_code, g.gong_cart_name'
        ) ?? [];
        if (!empty($hangingSetup)) {
            $hangingSetup['details'] = $this->getHangingSetupDetails((int)$hangingSetup['order_hanging_setup_id']);
            $hangingSetup['pole_numbers'] = $this->getHangingSetupPoleNumbers((int)$hangingSetup['order_hanging_setup_id']);
            $hangingSetup['production_progress_status'] = $stageProgressStatus['hanging'];
        }

        // Step 7: drying setup
        $this->db->where('s.production_order_id', $production_order_id);
        $this->db->join('eudr_production_ovens o', 'o.oven_id = s.oven_id', 'LEFT');
        $dryingSetup = $this->db->getOne(
            'eudr_production_order_drying_setup s',
            's.*, o.oven_code, o.oven_name'
        ) ?? [];
        if (!empty($dryingSetup)) {
            $dryingSetup['production_progress_status'] = $stageProgressStatus['drying'];
        }

        // Step 8: pressing setups by quality
        $this->db->where('s.production_order_id', $production_order_id);
        $this->db->orderBy('s.order_pressing_setup_id', 'ASC');
        $this->db->join('eudr_production_grades g', 'g.grade_id = s.grade_id', 'LEFT');
        $this->db->join('eudr_production_product_types p', 'p.product_type_id = s.product_type_id', 'LEFT');
        $pressingSetupsByQuality = $this->db->get(
            'eudr_production_order_pressing_setup s',
            null,
            's.*, g.grade_code, g.name AS grade_name, p.product_type_code, p.product_type_name AS product_type_name'
        ) ?? [];
        $pressingSetupsByQuality = $this->appendProgressStatusToList($pressingSetupsByQuality, $stageProgressStatus['pressing']);

        // Step 9: pallet setup
        $this->db->where('production_order_id', $production_order_id);
        $palletSetup = $this->db->getOne('eudr_production_order_pallet_setup') ?? [];
        if (!empty($palletSetup)) {
            $palletSetup['production_progress_status'] = $stageProgressStatus['pallet'];
        }

        return [
            'raw_tank_setups' => $rawTankSetups,
            'settling_tank_setup' => $settlingTankSetup,
            'channel_setups' => $channelSetups,
            'cutting_machine_setup' => $cuttingMachineSetup,
            'roller_setups_by_quality' => $rollerSetupsByQuality,
            'hanging_setup' => $hangingSetup,
            'drying_setup' => $dryingSetup,
            'pressing_setups_by_quality' => $pressingSetupsByQuality,
            'pallet_setup' => $palletSetup,
            'stage_progress_status' => $stageProgressStatus,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function setupPressing(int $production_order_id, int $grade_id, int $product_type_id, int $planned_sheet_quantity, ?string $started_at, ?string $ended_at, ?string $notes, int $company_id, int $factory_id, int $user_id): array
    {
        // Validate grade exists
        $this->db->where('grade_id', $grade_id);
        $this->db->where('deleted_by', 0);
        $grade = $this->db->getOne('eudr_production_grades', 'grade_id, grade_code, name');
        if (empty($grade)) {
            throw new ProductionOrderNotFoundException("Không tìm thấy loại chất lượng với ID: $grade_id");
        }

        // Upsert: one row per (production_order_id, grade_id)
        $this->db->where('production_order_id', $production_order_id);
        $this->db->where('grade_id', $grade_id);
        $existing = $this->db->getOne('eudr_production_order_pressing_setup', 'order_pressing_setup_id, product_type_id');

        if (!empty($existing)) {
            $updateData = [
                'product_type_id' => $product_type_id,
                'planned_sheet_quantity' => $planned_sheet_quantity,
                'notes' => $notes ?? '',
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => $user_id,
            ];
            if ($started_at !== null) {
                $updateData['started_at'] = $started_at;
            }
            if ($ended_at !== null) {
                $updateData['ended_at'] = $ended_at;
            }
            $this->db->where('order_pressing_setup_id', $existing['order_pressing_setup_id']);
            $this->db->update('eudr_production_order_pressing_setup', $updateData);
        } else {
            $insertData = [
                'production_order_id' => $production_order_id,
                'company_id' => $company_id,
                'factory_id' => $factory_id,
                'grade_id' => $grade_id,
                'product_type_id' => $product_type_id,
                'planned_sheet_quantity' => $planned_sheet_quantity,
                'started_at' => $started_at,
                'ended_at' => $ended_at,
                'notes' => $notes ?? '',
                'setup_status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $user_id,
            ];
            $this->db->insert('eudr_production_order_pressing_setup', $insertData);
        }

        // Return all pressing setups for this order
        $this->db->where('s.production_order_id', $production_order_id);
        $this->db->orderBy('s.order_pressing_setup_id', 'ASC');
        $this->db->join('eudr_production_grades g', 'g.grade_id = s.grade_id', 'LEFT');
        $this->db->join('eudr_production_product_types p', 'p.product_type_id = s.product_type_id', 'LEFT');
        return $this->db->get(
            'eudr_production_order_pressing_setup s',
            null,
            's.*, g.grade_code, g.name AS grade_name, p.product_type_code, p.product_type_name AS product_type_name'
        ) ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function setupPallet(int $production_order_id, int $planned_pallet_quantity, ?string $started_at, ?string $ended_at, ?string $notes, int $company_id, int $factory_id, int $warehouse_id, int $user_id): array
    {
        // Upsert: only ONE pallet setup per production order
        $this->db->where('production_order_id', $production_order_id);
        $existing = $this->db->getOne('eudr_production_order_pallet_setup', 'order_pallet_setup_id');

        if (!empty($existing)) {
            $updateData = [
                'planned_pallet_quantity' => $planned_pallet_quantity,
                'notes' => $notes ?? '',
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => $user_id,
            ];
            if ($started_at !== null) {
                $updateData['started_at'] = $started_at;
            }
            if ($ended_at !== null) {
                $updateData['ended_at'] = $ended_at;
            }
            $this->db->where('order_pallet_setup_id', $existing['order_pallet_setup_id']);
            $this->db->update('eudr_production_order_pallet_setup', $updateData);
        } else {
            $insertData = [
                'production_order_id' => $production_order_id,
                'company_id' => $company_id,
                'factory_id' => $factory_id,
                'planned_pallet_quantity' => $planned_pallet_quantity,
                'started_at' => $started_at,
                'ended_at' => $ended_at,
                'notes' => $notes ?? '',
                'setup_status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'warehouse_id' => $warehouse_id,
                'created_by' => $user_id,
            ];
            $this->db->insert('eudr_production_order_pallet_setup', $insertData);
        }

        // Return pallet setup row for this order
        $this->db->where('production_order_id', $production_order_id);
        return $this->db->getOne('eudr_production_order_pallet_setup') ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function createSetupChangeRequest(int $production_order_id, string $change_type, string $change_description, ?array $old_value, ?array $new_value, ?string $reason, int $requested_by, int $company_id, int $factory_id): array
    {
        $insertData = [
            'production_order_id' => $production_order_id,
            'company_id' => $company_id,
            'factory_id' => $factory_id,
            'change_type' => $change_type,
            'change_description' => $change_description,
            'old_value' => $this->encodeChangeRequestValue($old_value),
            'new_value' => $this->encodeChangeRequestValue($new_value),
            'reason' => $reason,
            'requested_by' => $requested_by,
            'approval_status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $requested_by,
        ];
        $this->db->insert('eudr_production_order_setup_change_requests', $insertData);

        $insertId = (int)$this->db->getInsertId();
        $this->db->where('change_request_id', $insertId);
        $record = $this->db->getOne('eudr_production_order_setup_change_requests') ?? [];
        return !empty($record) ? $this->normalizeSetupChangeRequestRow($record) : [];
    }

    /**
     * {@inheritdoc}
     */
    public function findAllSetupChangeRequests(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $search = $params['search'] ?? '';
        $approval_status = $params['approval_status'] ?? 'all';
        $change_type = $params['change_type'] ?? 'all';
        $production_order_code = $params['production_order_code'] ?? '';
        $factory_id = $params['factory_id'] ?? 0;
        $companyIdParam = $company_id_param ?? 0;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'cr');
        if (!empty($search)) {
            $this->db->where('(cr.change_description LIKE ? OR cr.reason LIKE ? OR po.production_order_code LIKE ? OR po.production_order_name LIKE ?)', ["%$search%", "%$search%", "%$search%", "%$search%"]);
        }
        if ($approval_status !== 'all') {
            $this->db->where('cr.approval_status', $approval_status);
        }
        if ($change_type !== 'all') {
            $this->db->where('cr.change_type', $change_type);
        }
        if (!empty($production_order_code)) {
            $this->db->where('po.production_order_code', $production_order_code);
        }
        if (!empty($factory_id)) {
            $this->db->where('cr.factory_id', $factory_id);
        }
        $this->db->join('eudr_production_orders po', 'po.production_order_id = cr.production_order_id', 'LEFT');
        $total_records = (int)$this->db->getValue('eudr_production_order_setup_change_requests cr', 'count(*)');

        $this->db->pageLimit = $page_limit;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'cr');
        if (!empty($search)) {
            $this->db->where('(cr.change_description LIKE ? OR cr.reason LIKE ? OR po.production_order_code LIKE ? OR po.production_order_name LIKE ?)', ["%$search%", "%$search%", "%$search%", "%$search%"]);
        }
        if ($approval_status !== 'all') {
            $this->db->where('cr.approval_status', $approval_status);
        }
        if ($change_type !== 'all') {
            $this->db->where('cr.change_type', $change_type);
        }
        if (!empty($production_order_code)) {
            $this->db->where('po.production_order_code', $production_order_code);
        }
        if (!empty($factory_id)) {
            $this->db->where('cr.factory_id', $factory_id);
        }
        $this->db->orderBy('cr.change_request_id', 'DESC');
        $this->db->join('eudr_production_orders po', 'po.production_order_id = cr.production_order_id', 'LEFT');
        $records = $this->db->arraybuilder()->paginate(
            'eudr_production_order_setup_change_requests cr',
            $page,
            'cr.*, po.production_order_code, po.production_order_name, po.status AS production_order_status'
        );

        $records = $records ?? [];
        foreach ($records as &$record) {
            if (is_array($record)) {
                $record = $this->normalizeSetupChangeRequestRow($record);
            }
        }
        unset($record);

        return [
            'current_page' => $page,
            'total_pages' => $this->db->totalPages,
            'total_records' => $total_records,
            'page_limit' => $this->db->pageLimit,
            'records' => $records,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function approveSetupChangeRequest(int $change_request_id, int $approved_by, ?string $approval_notes = null, ?array $step_time_overrides = null): array
    {
        $this->db->where('change_request_id', $change_request_id);
        $changeRequest = $this->db->getOne('eudr_production_order_setup_change_requests');
        if (empty($changeRequest)) {
            throw new ProductionOrderNotFoundException("Không tìm thấy yêu cầu thay đổi với ID: $change_request_id");
        }

        $production_order_id = (int)$changeRequest['production_order_id'];
        $change_type = (string)$changeRequest['change_type'];
        $oldValue = $this->flattenChangeValue($this->decodeChangeRequestValue($changeRequest['old_value'] ?? null));
        $newValue = $this->flattenChangeValue($this->decodeChangeRequestValue($changeRequest['new_value'] ?? null));
        $now = date('Y-m-d H:i:s');

        $this->applySetupChangeRequestValues($production_order_id, $change_type, $oldValue, $newValue, $approved_by, $now);
        if (is_array($step_time_overrides) && !empty($step_time_overrides)) {
            $this->applySetupStepTimeOverrides($production_order_id, $step_time_overrides, $approved_by, $now);
        }

        // Update change request status
        $updateData = [
            'approval_status' => 'approved',
            'approved_by' => $approved_by,
            'approval_notes' => $approval_notes,
            'approved_at' => $now,
            'updated_at' => $now,
            'updated_by' => $approved_by,
        ];
        $this->db->where('change_request_id', $change_request_id);
        $this->db->update('eudr_production_order_setup_change_requests', $updateData);

        $this->db->where('change_request_id', $change_request_id);
        $record = $this->db->getOne('eudr_production_order_setup_change_requests') ?? [];
        return !empty($record) ? $this->normalizeSetupChangeRequestRow($record) : [];
    }

    /**
     * {@inheritdoc}
     */
    public function rejectSetupChangeRequest(int $change_request_id, int $approved_by, ?string $approval_notes = null): array
    {
        $this->db->where('change_request_id', $change_request_id);
        $changeRequest = $this->db->getOne('eudr_production_order_setup_change_requests', 'change_request_id');
        if (empty($changeRequest)) {
            throw new ProductionOrderNotFoundException("Không tìm thấy yêu cầu thay đổi với ID: $change_request_id");
        }

        $updateData = [
            'approval_status' => 'rejected',
            'approved_by' => $approved_by,
            'approval_notes' => $approval_notes,
            'approved_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $approved_by,
        ];
        $this->db->where('change_request_id', $change_request_id);
        $this->db->update('eudr_production_order_setup_change_requests', $updateData);

        $this->db->where('change_request_id', $change_request_id);
        $record = $this->db->getOne('eudr_production_order_setup_change_requests') ?? [];
        return !empty($record) ? $this->normalizeSetupChangeRequestRow($record) : [];
    }

    /**
     * {@inheritdoc}
     */
    public function getExecutionDataOfProductionOrder(int $production_order_id, array $filters = []): array
    {
        $resolveFilter = static function (array $input, string $key): string {
            $value = (string)($input[$key] ?? 'all');
            return in_array($value, ['draft', 'in_progress', 'completed', 'cancelled', 'all'], true) ? $value : 'all';
        };

        $channelStatus = $resolveFilter($filters, 'channel_status');
        $settlingTankStatus = $resolveFilter($filters, 'settling_tank_status');
        $cuttingStatus = $resolveFilter($filters, 'cutting_status');
        $rollingStatus = $resolveFilter($filters, 'rolling_status');
        $hangingStatus = $resolveFilter($filters, 'hanging_status');
        $dryingStatus = $resolveFilter($filters, 'drying_status');
        $pressingStatus = $resolveFilter($filters, 'pressing_status');
        $palletStatus = $resolveFilter($filters, 'pallet_status');

        $stageProgressStatus = [
            'raw_tank' => $this->resolveProductionProgressStatusByTable('eudr_production_channel_runs', $production_order_id),
            'settling_tank' => $this->resolveProductionProgressStatusByTable('eudr_production_settling_tank_runs', $production_order_id),
            'channel' => $this->resolveProductionProgressStatusByTable('eudr_production_channel_runs', $production_order_id),
            'cutting_machine' => $this->resolveProductionProgressStatusByTable('eudr_production_cutting_runs', $production_order_id),
            'roller' => $this->resolveProductionProgressStatusByTable('eudr_production_rolling_runs', $production_order_id),
            'hanging' => $this->resolveProductionProgressStatusByTable('eudr_production_hanging_runs', $production_order_id),
            'drying' => $this->resolveProductionProgressStatusByTable('eudr_production_drying_runs', $production_order_id),
            'pressing' => $this->resolveProductionProgressStatusByTable('eudr_production_pressing_runs', $production_order_id),
            'pallet' => $this->resolveProductionProgressStatusByTable('eudr_production_pallet_runs', $production_order_id),
        ];

        $buildSummary = function (string $table, string $statusFilter = 'all') use ($production_order_id): array {
            $this->db->where('production_order_id', $production_order_id);
            $this->db->where('deleted_by', 0);
            if ($statusFilter !== 'all') {
                $this->db->where('status', $statusFilter);
            }
            $total = (int)$this->db->getValue($table, 'count(*)');

            $this->db->where('production_order_id', $production_order_id);
            $this->db->where('deleted_by', 0);
            if ($statusFilter !== 'all') {
                $this->db->where('status', $statusFilter);
                $completed = ($statusFilter === 'completed') ? $total : 0;
            } else {
                $this->db->where('status', 'completed');
                $completed = (int)$this->db->getValue($table, 'count(*)');
            }

            return [
                'total_runs' => $total,
                'completed_runs' => $completed,
                'confirmed' => ($total > 0 && $total === $completed),
            ];
        };

        // Step 1+3: raw tank -> channel runs
        $this->db->where('cr.production_order_id', $production_order_id);
        $this->db->where('cr.deleted_by', 0);
        if ($channelStatus !== 'all') {
            $this->db->where('cr.status', $channelStatus);
        }
        $this->db->orderBy('cr.channel_run_id', 'DESC');
        $this->db->join('eudr_tanks_raw_material t', 't.raw_material_tank_id = cr.raw_tank_id', 'LEFT');
        $this->db->join('eudr_production_channels c', 'c.channel_id = cr.channel_id', 'LEFT');
        $this->db->join('eudr_users u', 'u.user_id = cr.created_by', 'LEFT');
        $channelRuns = $this->db->arraybuilder()->get(
            'eudr_production_channel_runs cr',
            null,
            'cr.*, t.raw_material_tank_code, t.raw_material_tank_name, c.channel_code, c.channel_name, u.full_name AS created_by_name'
        ) ?? [];

        // Step 2: settling tank runs
        $this->db->where('sr.production_order_id', $production_order_id);
        $this->db->where('sr.deleted_by', 0);
        if ($settlingTankStatus !== 'all') {
            $this->db->where('sr.status', $settlingTankStatus);
        }
        $this->db->orderBy('sr.settling_tank_run_id', 'DESC');
        $this->db->join('eudr_production_settling_tanks st', 'st.settling_tank_id = sr.settling_tank_id', 'LEFT');
        $this->db->join('eudr_users u', 'u.user_id = sr.created_by', 'LEFT');
        $settlingRuns = $this->db->arraybuilder()->get(
            'eudr_production_settling_tank_runs sr',
            null,
            'sr.*, st.settling_tank_code, st.settling_tank_name, u.full_name AS created_by_name'
        ) ?? [];

        // Step 4: cutting runs + quality outputs
        $this->db->where('cr.production_order_id', $production_order_id);
        $this->db->where('cr.deleted_by', 0);
        if ($cuttingStatus !== 'all') {
            $this->db->where('cr.status', $cuttingStatus);
        }
        $this->db->orderBy('cr.cutting_run_id', 'DESC');
        $this->db->join('eudr_production_cutting_machines cm', 'cm.cutting_machine_id = cr.cutting_machine_id', 'LEFT');
        $this->db->join('eudr_users u', 'u.user_id = cr.created_by', 'LEFT');
        $cuttingRuns = $this->db->arraybuilder()->get('eudr_production_cutting_runs cr', null, 'cr.*, cm.cutting_machine_code, cm.cutting_machine_name, u.full_name AS created_by_name') ?? [];

        $cuttingRunIds = array_values(array_filter(array_map(static fn(array $row): int => (int)($row['cutting_run_id'] ?? 0), $cuttingRuns)));
        $cuttingQualityMap = [];
        if (!empty($cuttingRunIds)) {
            $this->db->where('qo.cutting_run_id', $cuttingRunIds, 'IN');
            $this->db->where('qo.deleted_by', 0);
            $this->db->join('eudr_production_grades g', 'g.grade_id = qo.grade_id', 'LEFT');
            $this->db->orderBy('qo.cutting_quality_output_id', 'ASC');
            $qualityRows = $this->db->arraybuilder()->get('eudr_production_cutting_run_quality_outputs qo', null, 'qo.*, g.grade_code, g.name AS grade_name') ?? [];
            foreach ($qualityRows as $row) {
                $rid = (int)($row['cutting_run_id'] ?? 0);
                $cuttingQualityMap[$rid][] = $row;
            }
        }
        foreach ($cuttingRuns as &$run) {
            $rid = (int)($run['cutting_run_id'] ?? 0);
            $run['quality_outputs'] = $cuttingQualityMap[$rid] ?? [];
        }
        unset($run);

        // Step 5: rolling runs + quality details
        $this->db->where('rr.production_order_id', $production_order_id);
        $this->db->where('rr.deleted_by', 0);
        if ($rollingStatus !== 'all') {
            $this->db->where('rr.status', $rollingStatus);
        }
        $this->db->orderBy('rr.rolling_run_id', 'DESC');
        $this->db->join('eudr_production_rollers r', 'r.roller_id = rr.roller_id', 'LEFT');
        $this->db->join('eudr_users u', 'u.user_id = rr.created_by', 'LEFT');
        $rollingRuns = $this->db->arraybuilder()->get('eudr_production_rolling_runs rr', null, 'rr.*, r.roller_code, r.roller_name, u.full_name AS created_by_name') ?? [];

        $rollingRunIds = array_values(array_filter(array_map(static fn(array $row): int => (int)($row['rolling_run_id'] ?? 0), $rollingRuns)));
        $rollingQualityMap = [];
        if (!empty($rollingRunIds)) {
            $this->db->where('qd.rolling_run_id', $rollingRunIds, 'IN');
            $this->db->where('qd.deleted_by', 0);
            $this->db->join('eudr_production_grades g', 'g.grade_id = qd.grade_id', 'LEFT');
            $this->db->orderBy('qd.rolling_quality_detail_id', 'ASC');
            $qualityRows = $this->db->arraybuilder()->get('eudr_production_rolling_run_quality_details qd', null, 'qd.*, g.grade_code, g.name AS grade_name') ?? [];
            foreach ($qualityRows as $row) {
                $rid = (int)($row['rolling_run_id'] ?? 0);
                $rollingQualityMap[$rid][] = $row;
            }
        }
        foreach ($rollingRuns as &$run) {
            $rid = (int)($run['rolling_run_id'] ?? 0);
            $run['quality_details'] = $rollingQualityMap[$rid] ?? [];
        }
        unset($run);

        // Step 6: hanging runs + quality details
        $this->db->where('hr.production_order_id', $production_order_id);
        $this->db->where('hr.deleted_by', 0);
        if ($hangingStatus !== 'all') {
            $this->db->where('hr.status', $hangingStatus);
        }
        $this->db->orderBy('hr.hanging_run_id', 'DESC');
        $this->db->join('eudr_production_gong_carts gc', 'gc.gong_cart_id = hr.gong_cart_id', 'LEFT');
        $this->db->join('eudr_users u', 'u.user_id = hr.created_by', 'LEFT');
        $hangingRuns = $this->db->arraybuilder()->get('eudr_production_hanging_runs hr', null, 'hr.*, gc.gong_cart_code, gc.gong_cart_name, u.full_name AS created_by_name') ?? [];

        $hangingRunIds = array_values(array_filter(array_map(static fn(array $row): int => (int)($row['hanging_run_id'] ?? 0), $hangingRuns)));
        $hangingQualityMap = [];
        if (!empty($hangingRunIds)) {
            $this->db->where('hd.hanging_run_id', $hangingRunIds, 'IN');
            $this->db->where('hd.deleted_by', 0);
            $this->db->join('eudr_production_grades g', 'g.grade_id = hd.grade_id', 'LEFT');
            $this->db->orderBy('hd.hanging_quality_detail_id', 'ASC');
            $detailRows = $this->db->arraybuilder()->get('eudr_production_hanging_run_quality_details hd', null, 'hd.*, g.grade_code, g.name AS grade_name') ?? [];
            foreach ($detailRows as $row) {
                $rid = (int)($row['hanging_run_id'] ?? 0);
                $hangingQualityMap[$rid][] = $row;
            }
        }
        foreach ($hangingRuns as &$run) {
            $rid = (int)($run['hanging_run_id'] ?? 0);
            $run['quality_details'] = $hangingQualityMap[$rid] ?? [];
        }
        unset($run);

        // Step 7: drying runs + quality details
        $this->db->where('dr.production_order_id', $production_order_id);
        $this->db->where('dr.deleted_by', 0);
        if ($dryingStatus !== 'all') {
            $this->db->where('dr.status', $dryingStatus);
        }
        $this->db->orderBy('dr.drying_run_id', 'DESC');
        $this->db->join('eudr_production_ovens o', 'o.oven_id = dr.oven_id', 'LEFT');
        $this->db->join('eudr_users u', 'u.user_id = dr.created_by', 'LEFT');
        $dryingRuns = $this->db->arraybuilder()->get('eudr_production_drying_runs dr', null, 'dr.*, o.oven_code, o.oven_name, u.full_name AS created_by_name') ?? [];

        $dryingRunIds = array_values(array_filter(array_map(static fn(array $row): int => (int)($row['drying_run_id'] ?? 0), $dryingRuns)));
        $dryingQualityMap = [];
        if (!empty($dryingRunIds)) {
            $this->db->where('qd.drying_run_id', $dryingRunIds, 'IN');
            $this->db->where('qd.deleted_by', 0);
            $this->db->join('eudr_production_grades g', 'g.grade_id = qd.grade_id', 'LEFT');
            $this->db->orderBy('qd.drying_quality_detail_id', 'ASC');
            $detailRows = $this->db->arraybuilder()->get('eudr_production_drying_run_quality_details qd', null, 'qd.*, g.grade_code, g.name AS grade_name') ?? [];
            foreach ($detailRows as $row) {
                $rid = (int)($row['drying_run_id'] ?? 0);
                $dryingQualityMap[$rid][] = $row;
            }
        }
        foreach ($dryingRuns as &$run) {
            $rid = (int)($run['drying_run_id'] ?? 0);
            $run['quality_details'] = $dryingQualityMap[$rid] ?? [];
        }
        unset($run);

        // Step 8: pressing runs + quality details + bales
        $this->db->where('pr.production_order_id', $production_order_id);
        $this->db->where('pr.deleted_by', 0);
        if ($pressingStatus !== 'all') {
            $this->db->where('pr.status', $pressingStatus);
        }
        $this->db->orderBy('pr.pressing_run_id', 'DESC');
        $this->db->join('eudr_users u', 'u.user_id = pr.created_by', 'LEFT');
        $pressingRuns = $this->db->arraybuilder()->get('eudr_production_pressing_runs pr', null, 'pr.*, u.full_name AS created_by_name') ?? [];

        $pressingRunIds = array_values(array_filter(array_map(static fn(array $row): int => (int)($row['pressing_run_id'] ?? 0), $pressingRuns)));
        $pressingQualityMap = [];
        $pressingBalesMap = [];
        if (!empty($pressingRunIds)) {
            $this->db->where('pqd.pressing_run_id', $pressingRunIds, 'IN');
            $this->db->where('pqd.deleted_by', 0);
            $this->db->join('eudr_production_grades g', 'g.grade_id = pqd.grade_id', 'LEFT');
            $this->db->join('eudr_production_product_types pt', 'pt.product_type_id = pqd.product_type_id', 'LEFT');
            $this->db->orderBy('pqd.pressing_quality_detail_id', 'ASC');
            $detailRows = $this->db->arraybuilder()->get('eudr_production_pressing_run_quality_details pqd', null, 'pqd.*, g.grade_code, g.name AS grade_name, pt.product_type_code, pt.product_type_name AS product_type_name') ?? [];
            foreach ($detailRows as $row) {
                $rid = (int)($row['pressing_run_id'] ?? 0);
                $pressingQualityMap[$rid][] = $row;
            }

            $this->db->where('b.pressing_run_id', $pressingRunIds, 'IN');
            $this->db->where('b.deleted_by', 0);
            $this->db->join('eudr_production_grades g', 'g.grade_id = b.grade_id', 'LEFT');
            $this->db->orderBy('b.bale_id', 'ASC');
            $baleRows = $this->db->arraybuilder()->get('eudr_production_bales b', null, 'b.*, g.grade_code, g.name AS grade_name') ?? [];
            foreach ($baleRows as $row) {
                $rid = (int)($row['pressing_run_id'] ?? 0);
                $pressingBalesMap[$rid][] = $row;
            }
        }
        foreach ($pressingRuns as &$run) {
            $rid = (int)($run['pressing_run_id'] ?? 0);
            $run['quality_details'] = $pressingQualityMap[$rid] ?? [];
            $run['bales'] = $pressingBalesMap[$rid] ?? [];
        }
        unset($run);

        // Step 9: pallet runs + pallets + pallet items
        $this->db->where('pr.production_order_id', $production_order_id);
        $this->db->where('pr.deleted_by', 0);
        if ($palletStatus !== 'all') {
            $this->db->where('pr.status', $palletStatus);
        }
        $this->db->orderBy('pr.pallet_run_id', 'DESC');
        $this->db->join('eudr_users u', 'u.user_id = pr.created_by', 'LEFT');
        $palletRuns = $this->db->arraybuilder()->get('eudr_production_pallet_runs pr', null, 'pr.*, u.full_name AS created_by_name') ?? [];

        $palletRunIds = array_values(array_filter(array_map(static fn(array $row): int => (int)($row['pallet_run_id'] ?? 0), $palletRuns)));
        $palletMap = [];
        if (!empty($palletRunIds)) {
            $this->db->where('p.pallet_run_id', $palletRunIds, 'IN');
            $this->db->where('p.deleted_by', 0);
            $this->db->join('eudr_warehouses w', 'w.warehouse_id = p.warehouse_id', 'LEFT');
            $this->db->orderBy('p.pallet_id', 'ASC');
            $palletRows = $this->db->arraybuilder()->get('eudr_production_pallets p', null, 'p.*, w.warehouse_code, w.warehouse_name') ?? [];

            $palletIds = [];
            foreach ($palletRows as $row) {
                $palletIds[] = (int)($row['pallet_id'] ?? 0);
                $rid = (int)($row['pallet_run_id'] ?? 0);
                $row['items'] = [];
                $palletMap[$rid][] = $row;
            }

            if (!empty($palletIds)) {
                $this->db->where('pi.pallet_id', $palletIds, 'IN');
                $this->db->where('pi.deleted_by', 0);
                $this->db->join('eudr_production_bales b', 'b.bale_id = pi.bale_id', 'LEFT');
                $this->db->join('eudr_production_grades g', 'g.grade_id = b.grade_id', 'LEFT');
                $this->db->orderBy('pi.pallet_item_id', 'ASC');
                $itemRows = $this->db->arraybuilder()->get('eudr_production_pallet_items pi', null, 'pi.*, b.bale_no, b.bale_weight_kg, b.status AS bale_status, g.grade_code, g.name AS grade_name') ?? [];

                $itemsByPallet = [];
                foreach ($itemRows as $row) {
                    $pid = (int)($row['pallet_id'] ?? 0);
                    $itemsByPallet[$pid][] = $row;
                }

                foreach ($palletMap as $rid => &$palletsOfRun) {
                    foreach ($palletsOfRun as &$pallet) {
                        $pid = (int)($pallet['pallet_id'] ?? 0);
                        $pallet['items'] = $itemsByPallet[$pid] ?? [];
                    }
                    unset($pallet);
                }
                unset($palletsOfRun);
            }
        }

        foreach ($palletRuns as &$run) {
            $rid = (int)($run['pallet_run_id'] ?? 0);
            $run['pallets'] = $palletMap[$rid] ?? [];
        }
        unset($run);

        return [
            'stage_progress_status' => $stageProgressStatus,
            'stage_confirmed_summary' => [
                'raw_tank_channel' => $buildSummary('eudr_production_channel_runs', $channelStatus),
                'settling_tank' => $buildSummary('eudr_production_settling_tank_runs', $settlingTankStatus),
                'cutting_machine' => $buildSummary('eudr_production_cutting_runs', $cuttingStatus),
                'roller' => $buildSummary('eudr_production_rolling_runs', $rollingStatus),
                'hanging' => $buildSummary('eudr_production_hanging_runs', $hangingStatus),
                'drying' => $buildSummary('eudr_production_drying_runs', $dryingStatus),
                'pressing' => $buildSummary('eudr_production_pressing_runs', $pressingStatus),
                'pallet' => $buildSummary('eudr_production_pallet_runs', $palletStatus),
            ],
            'raw_tank_channel_runs' => $channelRuns,
            'settling_tank_runs' => $settlingRuns,
            'cutting_runs' => $cuttingRuns,
            'rolling_runs' => $rollingRuns,
            'hanging_runs' => $hangingRuns,
            'drying_runs' => $dryingRuns,
            'pressing_runs' => $pressingRuns,
            'pallet_runs' => $palletRuns,
        ];
    }

}

