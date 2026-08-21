<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\ProductionPallet;

use App\Application\Utility\CurrentUserContext;
use App\Application\Utility\Utils;
use App\Domain\ProductionPallet\ProductionPalletRepository;

class InDatabaseProductionPalletRepository implements ProductionPalletRepository
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

    private function generatePalletRunCode(int $productionOrderId): string
    {
        while (true) {
            $code = 'prun-' . date('ymd') . '-' . Utils::generateRandomString(6);
            $this->db->where('production_order_id', $productionOrderId);
            $this->db->where('pallet_run_code', $code);
            $this->db->where('deleted_by', 0);
            $exists = $this->db->getOne('eudr_production_pallet_runs', 'pallet_run_id');
            if (empty($exists)) {
                return $code;
            }
        }
    }

    private function generatePalletCode(): string
    {
        while (true) {
            $code = 'palt-' . date('ymd') . '-' . Utils::generateRandomString(8);
            $this->db->where('pallet_code', $code);
            $this->db->where('deleted_by', 0);
            $exists = $this->db->getOne('eudr_production_pallets', 'pallet_id');
            if (empty($exists)) {
                return $code;
            }
        }
    }

    public function findAllPalletRuns(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array
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
        $total_records = (int)$this->db->getValue('eudr_production_pallet_runs pr', 'count(*)');

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
        $this->db->orderBy('pr.pallet_run_id', 'DESC');
        $records = $this->db->arraybuilder()->paginate('eudr_production_pallet_runs pr', $page, $cols);

        $items = [];
        $runIds = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $item['pallets'] = [];
                $items[] = $item;
                $runIds[] = (int)($item['pallet_run_id'] ?? 0);
            }
        }

        if (!empty($runIds)) {
            $this->db->where('pallet_run_id', $runIds, 'IN');
            $this->db->where('deleted_by', 0);
            $this->db->orderBy('pallet_id', 'ASC');
            $palletRows = $this->db->arraybuilder()->get('eudr_production_pallets') ?? [];

            $palletMap = [];
            foreach ($palletRows as $row) {
                $rid = (int)($row['pallet_run_id'] ?? 0);
                $palletMap[$rid][] = $row;
            }

            foreach ($items as $index => $run) {
                $rid = (int)($run['pallet_run_id'] ?? 0);
                $items[$index]['pallets'] = $palletMap[$rid] ?? [];
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

    public function findPressingRunOfIdWithPermission(int $pressing_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'pr');
        $this->db->where('pr.pressing_run_id', $pressing_run_id);
        $record = $this->db->getOne('eudr_production_pressing_runs pr', 'pr.*');

        return !empty($record) ? $record : null;
    }

    public function findPalletRunOfIdWithPermission(int $pallet_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'pr');
        $this->db->where('pr.pallet_run_id', $pallet_run_id);
        $record = $this->db->getOne('eudr_production_pallet_runs pr', 'pr.*');

        return !empty($record) ? $record : null;
    }

    public function getPalletRunDetailWithPermission(int $pallet_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array
    {
        $run = $this->findPalletRunOfIdWithPermission($pallet_run_id, $auth_user_id, $scope, $company_id, $company_id_param);
        if (empty($run)) {
            return null;
        }

        $this->db->where('pr.pallet_run_id', $pallet_run_id);
        $this->db->join('eudr_production_orders po', 'po.production_order_id = pr.production_order_id', 'LEFT');
        $runDetail = $this->db->getOne('eudr_production_pallet_runs pr', 'pr.*, po.production_order_code');
        if (empty($runDetail)) {
            return null;
        }

        $this->db->where('p.pallet_run_id', $pallet_run_id);
        $this->db->where('p.deleted_by', 0);
        $this->db->orderBy('p.pallet_id', 'ASC');
        $pallets = $this->db->get('eudr_production_pallets p') ?? [];

        $palletIds = [];
        foreach ($pallets as $pallet) {
            $palletIds[] = (int)($pallet['pallet_id'] ?? 0);
        }

        $itemsMap = [];
        if (!empty($palletIds)) {
            $this->db->where('pi.pallet_id', $palletIds, 'IN');
            $this->db->where('pi.deleted_by', 0);
            $this->db->join('eudr_production_bales b', 'b.bale_id = pi.bale_id', 'LEFT');
            $this->db->orderBy('pi.pallet_item_id', 'ASC');
            $itemRows = $this->db->get('eudr_production_pallet_items pi', null, 'pi.*, b.bale_no, b.grade_id, b.status AS bale_status, b.bale_weight_kg') ?? [];

            foreach ($itemRows as $row) {
                $pid = (int)($row['pallet_id'] ?? 0);
                $itemsMap[$pid][] = $row;
            }
        }

        foreach ($pallets as $index => $pallet) {
            $pid = (int)($pallet['pallet_id'] ?? 0);
            $pallets[$index]['items'] = $itemsMap[$pid] ?? [];
        }

        $runDetail['pallets'] = $pallets;

        return $runDetail;
    }

    public function findAllPallets(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $companyIdParam = $company_id_param ?? 0;
        $productionOrderId = $params['production_order_id'] ?? 0;
        $factoryId = $params['factory_id'] ?? 0;
        $palletRunId = $params['pallet_run_id'] ?? 0;
        $warehouseId = $params['warehouse_id'] ?? 0;
        $status = $params['status'] ?? 'closed';
        $search = $params['search'] ?? '';

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'p');
        $this->db->join('eudr_production_pallet_runs pr', 'pr.pallet_run_id = p.pallet_run_id AND pr.deleted_by = 0', 'LEFT');
        $this->db->join('eudr_production_orders po', 'po.production_order_id = p.production_order_id', 'LEFT');
        if (!empty($productionOrderId)) {
            $this->db->where('p.production_order_id', (int)$productionOrderId);
        }
        if (!empty($factoryId)) {
            $this->db->where('p.factory_id', (int)$factoryId);
        }
        if (!empty($palletRunId)) {
            $this->db->where('p.pallet_run_id', (int)$palletRunId);
        }
        if (!empty($warehouseId)) {
            $this->db->where('p.warehouse_id', (int)$warehouseId);
        }
        if ($status !== 'all') {
            $this->db->where('p.status', $status);
        }
        if ($search !== '') {
            $this->db->where('(p.pallet_code LIKE ? OR p.pallet_no LIKE ? OR pr.pallet_run_code LIKE ? OR po.production_order_code LIKE ?)', ["%$search%", "%$search%", "%$search%", "%$search%"]);
        }
        $total_records = (int)$this->db->getValue('eudr_production_pallets p', 'count(*)');

        $this->db->pageLimit = $page_limit;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'p');
        $this->db->join('eudr_production_pallet_runs pr', 'pr.pallet_run_id = p.pallet_run_id AND pr.deleted_by = 0', 'LEFT');
        $this->db->join('eudr_production_orders po', 'po.production_order_id = p.production_order_id', 'LEFT');
        $this->db->join('eudr_warehouses w', 'w.warehouse_id = p.warehouse_id', 'LEFT');
        $this->db->join('eudr_factories f', 'f.factory_id = p.factory_id', 'LEFT');
        $this->db->join('eudr_users u', 'u.user_id = p.created_by', 'LEFT');
        if (!empty($productionOrderId)) {
            $this->db->where('p.production_order_id', (int)$productionOrderId);
        }
        if (!empty($factoryId)) {
            $this->db->where('p.factory_id', (int)$factoryId);
        }
        if (!empty($palletRunId)) {
            $this->db->where('p.pallet_run_id', (int)$palletRunId);
        }
        if (!empty($warehouseId)) {
            $this->db->where('p.warehouse_id', (int)$warehouseId);
        }
        if ($status !== 'all') {
            $this->db->where('p.status', $status);
        }
        if ($search !== '') {
            $this->db->where('(p.pallet_code LIKE ? OR p.pallet_no LIKE ? OR pr.pallet_run_code LIKE ? OR po.production_order_code LIKE ?)', ["%$search%", "%$search%", "%$search%", "%$search%"]);
        }

        $cols = 'p.*, pr.pallet_run_code, pr.status AS pallet_run_status, po.production_order_code, w.warehouse_name, f.factory_name, u.full_name AS created_by_name';
        $this->db->orderBy('p.pallet_id', 'DESC');
        $records = $this->db->arraybuilder()->paginate('eudr_production_pallets p', $page, $cols);

        return [
            'current_page' => $page,
            'total_pages' => $this->db->totalPages,
            'total_records' => $total_records,
            'page_limit' => $this->db->pageLimit,
            'records' => $records ?? [],
        ];
    }

    public function getPalletDetailByCodeWithPermission(string $pallet_code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'p');
        $this->db->where('p.pallet_code', $pallet_code);
        $this->db->join('eudr_production_pallet_runs pr', 'pr.pallet_run_id = p.pallet_run_id AND pr.deleted_by = 0', 'LEFT');
        $this->db->join('eudr_production_orders po', 'po.production_order_id = p.production_order_id', 'LEFT');
        $this->db->join('eudr_warehouses w', 'w.warehouse_id = p.warehouse_id', 'LEFT');
        $this->db->join('eudr_factories f', 'f.factory_id = p.factory_id', 'LEFT');
        $this->db->join('eudr_users u', 'u.user_id = p.created_by', 'LEFT');
        $pallet = $this->db->getOne('eudr_production_pallets p', 'p.*, pr.pallet_run_code, pr.status AS pallet_run_status, po.production_order_code, w.warehouse_name, f.factory_name, u.full_name AS created_by_name');
        if (empty($pallet)) {
            return null;
        }

        $palletId = (int)($pallet['pallet_id'] ?? 0);
        if ($palletId <= 0) {
            return null;
        }

        $this->db->where('pi.pallet_id', $palletId);
        $this->db->where('pi.deleted_by', 0);
        $this->db->join('eudr_production_bales b', 'b.bale_id = pi.bale_id', 'LEFT');
        $this->db->join('eudr_production_grades g', 'g.grade_id = b.grade_id', 'LEFT');
        $this->db->orderBy('pi.pallet_item_id', 'ASC');
        $items = $this->db->get('eudr_production_pallet_items pi', null, 'pi.*, b.bale_no, b.bale_weight_kg, b.grade_id, g.grade_code, g.name AS grade_name, b.status AS bale_status') ?? [];

        $pallet['items'] = $items;

        return $pallet;
    }

    public function findAllBales(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $companyIdParam = $company_id_param ?? 0;
        $productionOrderId = $params['production_order_id'] ?? 0;
        $factoryId = $params['factory_id'] ?? 0;
        $palletRunId = $params['pallet_run_id'] ?? 0;
        $pressingRunId = $params['pressing_run_id'] ?? 0;
        $gradeId = $params['grade_id'] ?? 0;
        $status = $params['status'] ?? 'all';
        $search = $params['search'] ?? '';

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'b');
        $this->db->join('eudr_production_pallet_items pi', 'pi.bale_id = b.bale_id AND pi.deleted_by = 0', 'LEFT');
        $this->db->join('eudr_production_pallets p', 'p.pallet_id = pi.pallet_id AND p.deleted_by = 0', 'LEFT');
        $this->db->join('eudr_production_pallet_runs pr', 'pr.pallet_run_id = p.pallet_run_id AND pr.deleted_by = 0', 'LEFT');
        $this->db->join('eudr_production_pressing_run_quality_details pqd', 'pqd.pressing_quality_detail_id = b.pressing_quality_detail_id AND pqd.deleted_by = 0', 'LEFT');
        $this->db->join('eudr_production_pressing_runs phr', 'phr.pressing_run_id = pqd.pressing_run_id AND phr.deleted_by = 0', 'LEFT');
        $this->db->join('eudr_production_grades g', 'g.grade_id = b.grade_id', 'LEFT');
        if (!empty($productionOrderId)) {
            $this->db->where('b.production_order_id', (int)$productionOrderId);
        }
        if (!empty($factoryId)) {
            $this->db->where('b.factory_id', (int)$factoryId);
        }
        if (!empty($palletRunId)) {
            $this->db->where('pr.pallet_run_id', (int)$palletRunId);
        }
        if (!empty($pressingRunId)) {
            $this->db->where('phr.pressing_run_id', (int)$pressingRunId);
        }
        if (!empty($gradeId)) {
            $this->db->where('b.grade_id', (int)$gradeId);
        }
        if ($status !== 'all') {
            $this->db->where('b.status', $status);
        }
        if ($search !== '') {
            $this->db->where('(b.bale_no LIKE ? OR p.pallet_no LIKE ? OR pr.pallet_run_code LIKE ?)', ["%$search%", "%$search%", "%$search%"]);
        }
        $total_records = (int)$this->db->getValue('eudr_production_bales b', 'count(*)');

        $this->db->pageLimit = $page_limit;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'b');
        $this->db->join('eudr_production_pallet_items pi', 'pi.bale_id = b.bale_id AND pi.deleted_by = 0', 'LEFT');
        $this->db->join('eudr_production_pallets p', 'p.pallet_id = pi.pallet_id AND p.deleted_by = 0', 'LEFT');
        $this->db->join('eudr_production_pallet_runs pr', 'pr.pallet_run_id = p.pallet_run_id AND pr.deleted_by = 0', 'LEFT');
        $this->db->join('eudr_production_pressing_run_quality_details pqd', 'pqd.pressing_quality_detail_id = b.pressing_quality_detail_id AND pqd.deleted_by = 0', 'LEFT');
        $this->db->join('eudr_production_pressing_runs phr', 'phr.pressing_run_id = pqd.pressing_run_id AND phr.deleted_by = 0', 'LEFT');
        $this->db->join('eudr_production_grades g', 'g.grade_id = b.grade_id', 'LEFT');
        $this->db->join('eudr_factories f', 'f.factory_id = b.factory_id', 'LEFT');
        $this->db->join('eudr_production_orders po', 'po.production_order_id = b.production_order_id', 'LEFT');
        $this->db->join('eudr_users u', 'u.user_id = b.created_by', 'LEFT');
        if (!empty($productionOrderId)) {
            $this->db->where('b.production_order_id', (int)$productionOrderId);
        }
        if (!empty($factoryId)) {
            $this->db->where('b.factory_id', (int)$factoryId);
        }
        if (!empty($palletRunId)) {
            $this->db->where('pr.pallet_run_id', (int)$palletRunId);
        }
        if (!empty($pressingRunId)) {
            $this->db->where('phr.pressing_run_id', (int)$pressingRunId);
        }
        if (!empty($gradeId)) {
            $this->db->where('b.grade_id', (int)$gradeId);
        }
        if ($status !== 'all') {
            $this->db->where('b.status', $status);
        }
        if ($search !== '') {
            $this->db->where('(b.bale_no LIKE ? OR p.pallet_no LIKE ? OR pr.pallet_run_code LIKE ?)', ["%$search%", "%$search%", "%$search%"]);
        }

        $cols = 'b.*, pr.pallet_run_code, pr.status AS pallet_run_status, p.pallet_code, p.pallet_no, p.status AS pallet_status, g.grade_code, g.name AS grade_name, phr.pressing_run_id AS source_pressing_run_id, phr.status AS pressing_run_status, f.factory_name, u.full_name AS created_by_name, po.production_order_code';
        $this->db->orderBy('b.bale_id', 'DESC');
        $records = $this->db->arraybuilder()->paginate('eudr_production_bales b', $page, $cols);

        return [
            'current_page' => $page,
            'total_pages' => $this->db->totalPages,
            'total_records' => $total_records,
            'page_limit' => $this->db->pageLimit,
            'records' => $records ?? [],
        ];
    }

    public function getBaleDetailWithPermission(int $bale_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'b');
        $this->db->where('b.bale_id', $bale_id);
        $this->db->join('eudr_factories f', 'f.factory_id = b.factory_id', 'LEFT');
        $this->db->join('eudr_production_orders po', 'po.production_order_id = b.production_order_id', 'LEFT');
        $this->db->join('eudr_users u', 'u.user_id = b.created_by', 'LEFT');
        $this->db->join('eudr_production_grades g', 'g.grade_id = b.grade_id', 'LEFT');
        $this->db->join('eudr_production_pallet_items pi', 'pi.bale_id = b.bale_id AND pi.deleted_by = 0', 'LEFT');
        $this->db->join('eudr_production_pallets p', 'p.pallet_id = pi.pallet_id AND p.deleted_by = 0', 'LEFT');
        $this->db->join('eudr_production_pallet_runs pr', 'pr.pallet_run_id = p.pallet_run_id AND pr.deleted_by = 0', 'LEFT');
        $this->db->join('eudr_production_pressing_run_quality_details pqd', 'pqd.pressing_quality_detail_id = b.pressing_quality_detail_id AND pqd.deleted_by = 0', 'LEFT');
        $this->db->join('eudr_production_pressing_runs phr', 'phr.pressing_run_id = pqd.pressing_run_id AND phr.deleted_by = 0', 'LEFT');
        $cols = 'b.*, pr.pallet_run_code, pr.status AS pallet_run_status, p.pallet_code, p.pallet_no, p.status AS pallet_status, g.grade_code, g.name AS grade_name, phr.pressing_run_id AS source_pressing_run_id, phr.status AS pressing_run_status, f.factory_name, u.full_name AS created_by_name, po.production_order_code';
        $record = $this->db->getOne('eudr_production_bales b', $cols);
        return !empty($record) ? $record : null;
    }

    public function createPalletRunFromPressing(array $data): ?array
    {
        $pressingRunId = (int)($data['pressing_run_id'] ?? 0);
        $notes = $data['notes'] ?? null;
        $updatedBy = (int)($data['updated_by'] ?? 0);

        if ($pressingRunId <= 0 || $updatedBy <= 0) {
            return null;
        }

        $this->db->startTransaction();

        $this->db->where('pressing_run_id', $pressingRunId);
        $this->db->where('deleted_by', 0);
        $pressingRun = $this->db->getOne('eudr_production_pressing_runs', '*');
        if (empty($pressingRun)) {
            $this->db->rollback();
            return null;
        }
        if ((string)($pressingRun['status'] ?? '') !== 'completed') {
            $this->db->rollback();
            return null;
        }

        $this->db->where('pressing_run_id', $pressingRunId);
        $this->db->where('deleted_by', 0);
        $inputBaleCount = (int)$this->db->getValue('eudr_production_bales', 'count(*)');
        if ($inputBaleCount <= 0) {
            $this->db->rollback();
            return null;
        }

        $now = date('Y-m-d H:i:s', time());
        $productionOrderId = (int)($pressingRun['production_order_id'] ?? 0);
        $palletRunCode = $this->generatePalletRunCode($productionOrderId);
        $runNotes = trim((string)($notes ?? ''));
        $notePrefix = 'source_pressing_run_id:' . $pressingRunId;
        $combinedNotes = $runNotes === '' ? $notePrefix : ($notePrefix . '; ' . $runNotes);

        $this->db->insert('eudr_production_pallet_runs', [
            'production_order_id' => $productionOrderId,
            'company_id' => (int)($pressingRun['company_id'] ?? 0),
            'factory_id' => (int)($pressingRun['factory_id'] ?? 0),
            'pallet_run_code' => $palletRunCode,
            'input_bale_count' => $inputBaleCount,
            'output_pallet_count' => 0,
            'started_at' => $now,
            'ended_at' => null,
            'status' => 'in_progress',
            'notes' => $combinedNotes,
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

        $palletRunId = (int)$this->db->getInsertId();

        $this->db->commit();

        return [
            'pallet_run_id' => $palletRunId,
            'pallet_run_code' => $palletRunCode,
            'pressing_run_id' => $pressingRunId,
            'status' => 'in_progress',
            'input_bale_count' => $inputBaleCount,
            'started_at' => $now,
        ];
    }

    public function createPalletWithBales(array $data): ?array
    {
        $palletRunId = (int)($data['pallet_run_id'] ?? 0);
        $palletNo = trim((string)($data['pallet_no'] ?? ''));
        $warehouseId = isset($data['warehouse_id']) ? (int)$data['warehouse_id'] : null;
        $baleIds = $data['bale_ids'] ?? [];
        $notes = $data['notes'] ?? null;
        $updatedBy = (int)($data['updated_by'] ?? 0);

        if ($palletRunId <= 0 || $updatedBy <= 0 || !is_array($baleIds) || count($baleIds) === 0) {
            return null;
        }

        $normalizedBaleIds = array_values(array_unique(array_map('intval', $baleIds)));
        foreach ($normalizedBaleIds as $baleId) {
            if ($baleId <= 0) {
                return null;
            }
        }

        $this->db->startTransaction();

        $this->db->where('pallet_run_id', $palletRunId);
        $this->db->where('deleted_by', 0);
        $run = $this->db->getOne('eudr_production_pallet_runs', '*');
        if (empty($run)) {
            $this->db->rollback();
            return null;
        }
        if ((string)($run['status'] ?? '') !== 'in_progress') {
            $this->db->rollback();
            return null;
        }

        $this->db->where('bale_id', $normalizedBaleIds, 'IN');
        $this->db->where('deleted_by', 0);
        $bales = $this->db->get('eudr_production_bales', null, 'bale_id, production_order_id, company_id, factory_id');
        if (empty($bales) || count($bales) !== count($normalizedBaleIds)) {
            $this->db->rollback();
            return null;
        }

        $expectedOrderId = (int)($run['production_order_id'] ?? 0);
        $expectedCompanyId = (int)($run['company_id'] ?? 0);
        $expectedFactoryId = (int)($run['factory_id'] ?? 0);
        foreach ($bales as $bale) {
            if ((int)$bale['production_order_id'] !== $expectedOrderId || (int)$bale['company_id'] !== $expectedCompanyId || (int)$bale['factory_id'] !== $expectedFactoryId) {
                $this->db->rollback();
                return null;
            }
        }

        $this->db->where('pi.bale_id', $normalizedBaleIds, 'IN');
        $this->db->where('pi.deleted_by', 0);
        $this->db->where('p.deleted_by', 0);
        $this->db->join('eudr_production_pallets p', 'p.pallet_id = pi.pallet_id', 'INNER');
        $assignedRows = $this->db->get('eudr_production_pallet_items pi', null, 'pi.bale_id');
        if (!empty($assignedRows)) {
            $this->db->rollback();
            return null;
        }

        $now = date('Y-m-d H:i:s', time());

        if ($palletNo === '') {
            $this->db->where('pallet_run_id', $palletRunId);
            $this->db->where('deleted_by', 0);
            $existingCount = (int)$this->db->getValue('eudr_production_pallets', 'count(*)');
            $palletNo = 'P' . str_pad((string)($existingCount + 1), 4, '0', STR_PAD_LEFT);
        }

        $this->db->where('pallet_run_id', $palletRunId);
        $this->db->where('pallet_no', $palletNo);
        $this->db->where('deleted_by', 0);
        $existsPalletNo = $this->db->getOne('eudr_production_pallets', 'pallet_id');
        if (!empty($existsPalletNo)) {
            $this->db->rollback();
            return null;
        }

        $palletCode = $this->generatePalletCode();
        $this->db->insert('eudr_production_pallets', [
            'pallet_code' => $palletCode,
            'pallet_run_id' => $palletRunId,
            'production_order_id' => $expectedOrderId,
            'company_id' => $expectedCompanyId,
            'factory_id' => $expectedFactoryId,
            'warehouse_id' => $warehouseId,
            'pallet_no' => $palletNo,
            'bale_count' => count($normalizedBaleIds),
            'status' => 'closed',
            'packed_at' => $now,
            'shipped_at' => null,
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

        $palletId = (int)$this->db->getInsertId();
        foreach ($normalizedBaleIds as $baleId) {
            $this->db->insert('eudr_production_pallet_items', [
                'pallet_id' => $palletId,
                'bale_id' => $baleId,
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

        if ($notes !== null) {
            $this->db->where('pallet_id', $palletId);
            $this->db->update('eudr_production_pallets', [
                'notes' => $notes,
                'updated_by' => $updatedBy,
                'updated_at' => $now,
            ]);
            if ($this->db->getLastErrno() !== 0) {
                $this->db->rollback();
                return null;
            }
        }

        $this->db->commit();

        return [
            'pallet_id' => $palletId,
            'pallet_code' => $palletCode,
            'pallet_no' => $palletNo,
            'pallet_run_id' => $palletRunId,
            'bale_count' => count($normalizedBaleIds),
            'status' => 'closed',
            'bale_ids' => $normalizedBaleIds,
        ];
    }

    public function updatePalletItem(array $data): ?array
    {
        $palletItemId = (int)($data['pallet_item_id'] ?? 0);
        $newBaleId = (int)($data['bale_id'] ?? 0);
        $updatedBy = (int)($data['updated_by'] ?? 0);

        if ($palletItemId <= 0 || $newBaleId <= 0 || $updatedBy <= 0) {
            return null;
        }

        $this->db->startTransaction();

        $this->db->where('pi.pallet_item_id', $palletItemId);
        $this->db->where('pi.deleted_by', 0);
        $this->db->join('eudr_production_pallets p', 'p.pallet_id = pi.pallet_id', 'INNER');
        $item = $this->db->getOne('eudr_production_pallet_items pi', 'pi.*, p.pallet_id, p.pallet_run_id, p.production_order_id, p.company_id, p.factory_id, p.status AS pallet_status');
        if (empty($item)) {
            $this->db->rollback();
            return null;
        }

        $palletId = (int)($item['pallet_id'] ?? 0);
        if ($palletId <= 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->where('pallet_run_id', (int)($item['pallet_run_id'] ?? 0));
        $this->db->where('deleted_by', 0);
        $run = $this->db->getOne('eudr_production_pallet_runs', 'status');
        if (empty($run) || (string)($run['status'] ?? '') !== 'in_progress') {
            $this->db->rollback();
            return null;
        }

        $this->db->where('bale_id', $newBaleId);
        $this->db->where('deleted_by', 0);
        $bale = $this->db->getOne('eudr_production_bales', 'bale_id, production_order_id, company_id, factory_id');
        if (empty($bale)) {
            $this->db->rollback();
            return null;
        }

        if (
            (int)($bale['production_order_id'] ?? 0) !== (int)($item['production_order_id'] ?? 0)
            || (int)($bale['company_id'] ?? 0) !== (int)($item['company_id'] ?? 0)
            || (int)($bale['factory_id'] ?? 0) !== (int)($item['factory_id'] ?? 0)
        ) {
            $this->db->rollback();
            return null;
        }

        $this->db->where('pi.bale_id', $newBaleId);
        $this->db->where('pi.deleted_by', 0);
        $this->db->where('pi.pallet_item_id', $palletItemId, '!=');
        $this->db->where('p.deleted_by', 0);
        $this->db->join('eudr_production_pallets p', 'p.pallet_id = pi.pallet_id', 'INNER');
        $exists = $this->db->getOne('eudr_production_pallet_items pi', 'pi.pallet_item_id');
        if (!empty($exists)) {
            $this->db->rollback();
            return null;
        }

        $now = date('Y-m-d H:i:s', time());

        $this->db->where('pallet_item_id', $palletItemId);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_production_pallet_items', [
            'bale_id' => $newBaleId,
            'updated_by' => $updatedBy,
            'updated_at' => $now,
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->where('pallet_item_id', $palletItemId);
        $updated = $this->db->getOne('eudr_production_pallet_items', '*');
        if (empty($updated)) {
            $this->db->rollback();
            return null;
        }

        $this->db->commit();

        return $updated;
    }

    public function deletePalletItem(array $data): ?array
    {
        $palletItemId = (int)($data['pallet_item_id'] ?? 0);
        $updatedBy = (int)($data['updated_by'] ?? 0);

        if ($palletItemId <= 0 || $updatedBy <= 0) {
            return null;
        }

        $this->db->startTransaction();

        $this->db->where('pi.pallet_item_id', $palletItemId);
        $this->db->where('pi.deleted_by', 0);
        $this->db->join('eudr_production_pallets p', 'p.pallet_id = pi.pallet_id', 'INNER');
        $item = $this->db->getOne('eudr_production_pallet_items pi', 'pi.*, p.pallet_id, p.pallet_run_id');
        if (empty($item)) {
            $this->db->rollback();
            return null;
        }

        $palletId = (int)($item['pallet_id'] ?? 0);
        $palletRunId = (int)($item['pallet_run_id'] ?? 0);
        if ($palletId <= 0 || $palletRunId <= 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->where('pallet_run_id', $palletRunId);
        $this->db->where('deleted_by', 0);
        $run = $this->db->getOne('eudr_production_pallet_runs', 'status');
        if (empty($run) || (string)($run['status'] ?? '') !== 'in_progress') {
            $this->db->rollback();
            return null;
        }

        $now = date('Y-m-d H:i:s', time());

        $this->db->where('pallet_item_id', $palletItemId);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_production_pallet_items', [
            'deleted_by' => $updatedBy,
            'deleted_at' => $now,
            'updated_by' => $updatedBy,
            'updated_at' => $now,
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->where('pallet_id', $palletId);
        $this->db->where('deleted_by', 0);
        $baleCount = (int)$this->db->getValue('eudr_production_pallet_items', 'count(*)');

        $this->db->where('pallet_id', $palletId);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_production_pallets', [
            'bale_count' => $baleCount,
            'updated_by' => $updatedBy,
            'updated_at' => $now,
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->commit();

        return [
            'pallet_item_id' => $palletItemId,
            'pallet_id' => $palletId,
            'pallet_run_id' => $palletRunId,
            'pallet_bale_count' => $baleCount,
            'deleted_at' => $now,
        ];
    }

    public function completePalletRun(array $data): ?array
    {
        $palletRunId = (int)($data['pallet_run_id'] ?? 0);
        $startedAt = !empty($data['started_at']) ? (string)$data['started_at'] : null;
        $endedAt = !empty($data['ended_at']) ? (string)$data['ended_at'] : null;
        $updatedBy = (int)($data['updated_by'] ?? 0);

        if ($palletRunId <= 0 || $updatedBy <= 0) {
            return null;
        }

        $this->db->where('pallet_run_id', $palletRunId);
        $this->db->where('deleted_by', 0);
        $run = $this->db->getOne('eudr_production_pallet_runs', '*');
        if (empty($run)) {
            return null;
        }
        if ((string)($run['status'] ?? '') === 'completed') {
            return [
                'pallet_run_id' => $palletRunId,
                'status' => 'completed',
                'ended_at' => $run['ended_at'] ?? null,
                'output_pallet_count' => (int)($run['output_pallet_count'] ?? 0),
            ];
        }

        $this->db->where('pallet_run_id', $palletRunId);
        $this->db->where('deleted_by', 0);
        $outputPalletCount = (int)$this->db->getValue('eudr_production_pallets', 'count(*)');

        $now = date('Y-m-d H:i:s', time());
        $runEndedAt = $endedAt ?? $now;

        $runUpdateData = [
            'output_pallet_count' => $outputPalletCount,
            'status' => 'completed',
            'ended_at' => $runEndedAt,
            'updated_by' => $updatedBy,
            'updated_at' => $now,
        ];
        if ($startedAt !== null) {
            $runUpdateData['started_at'] = $startedAt;
        }
        $this->db->where('pallet_run_id', $palletRunId);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_production_pallet_runs', $runUpdateData);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }

        return [
            'pallet_run_id' => $palletRunId,
            'status' => 'completed',
            'ended_at' => $runEndedAt,
            'output_pallet_count' => $outputPalletCount,
        ];
    }
}
