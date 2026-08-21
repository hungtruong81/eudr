<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\ProductionGongCart;

use App\Application\Utility\CurrentUserContext;
use App\Application\Utility\Utils;
use App\Domain\ProductionGongCart\ProductionGongCart;
use App\Domain\ProductionGongCart\ProductionGongCartNotFoundException;
use App\Domain\ProductionGongCart\ProductionGongCartRepository;

class InDatabaseProductionGongCartRepository implements ProductionGongCartRepository
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
     * Apply scope-based where conditions to the query.
     *
     * @param string $scope
     * @param int $authUserId
     * @param int $companyId
     * @param int|null $companyIdParam
     * @param string $alias
     * @return void
     */
    private function scopeWhere(string $scope, int $authUserId, int $companyId, ?int $companyIdParam = null, string $alias = 'pgc'): void
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
     * Find all production gong carts with optional filters and pagination.
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
        $gongCartCode = $params['gong_cart_code'] ?? '';
        $gongCartName = $params['gong_cart_name'] ?? '';
        $status = $params['status'] ?? 'all';
        $factoryId = $params['factory_id'] ?? 0;
        $companyIdParam = $company_id_param ?? 0;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'pgc');
        if (!empty($search)) {
            $this->db->where('(pgc.gong_cart_code LIKE ? OR pgc.gong_cart_name LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($gongCartCode)) {
            $this->db->where('pgc.gong_cart_code', $gongCartCode);
        }
        if (!empty($gongCartName)) {
            $this->db->where('pgc.gong_cart_name', $gongCartName);
        }
        if ($status !== 'all') {
            $this->db->where('pgc.status', $status);
        }
        if (!empty($factoryId)) {
            $this->db->where('pgc.factory_id', $factoryId);
        }
        $total_records = (int)$this->db->getValue('eudr_production_gong_carts pgc', 'count(*)');

        $this->db->pageLimit = $page_limit;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'pgc');
        if (!empty($search)) {
            $this->db->where('(pgc.gong_cart_code LIKE ? OR pgc.gong_cart_name LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($gongCartCode)) {
            $this->db->where('pgc.gong_cart_code', $gongCartCode);
        }
        if (!empty($gongCartName)) {
            $this->db->where('pgc.gong_cart_name', $gongCartName);
        }
        if ($status !== 'all') {
            $this->db->where('pgc.status', $status);
        }
        if (!empty($factoryId)) {
            $this->db->where('pgc.factory_id', $factoryId);
        }

        $cols = 'pgc.*, f.factory_name';

        if (!empty($params['order_by'])) {
            $this->db->orderBy('pgc.' . $params['order_by'], $params['order_type'] ?? 'ASC');
        } else {
            $this->db->orderBy('pgc.gong_cart_id', 'DESC');
        }

        $this->db->join('eudr_factories f', 'f.factory_id = pgc.factory_id', 'LEFT');
        $records = $this->db->arraybuilder()->paginate('eudr_production_gong_carts pgc', $page, $cols);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = new ProductionGongCart($item['gong_cart_id'], $item);
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

    public function findAllHangingRuns(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $companyIdParam = $company_id_param ?? 0;
        $productionOrderId = $params['production_order_id'] ?? 0;
        $factoryId = $params['factory_id'] ?? 0;
        $gongCartId = $params['gong_cart_id'] ?? 0;
        $status = $params['status'] ?? 'all';

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'hr');
        if (!empty($productionOrderId)) {
            $this->db->where('hr.production_order_id', (int)$productionOrderId);
        }
        if (!empty($factoryId)) {
            $this->db->where('hr.factory_id', (int)$factoryId);
        }
        if (!empty($gongCartId)) {
            $this->db->where('hr.gong_cart_id', (int)$gongCartId);
        }
        if ($status !== 'all') {
            $this->db->where('hr.status', $status);
        }
        $total_records = (int)$this->db->getValue('eudr_production_hanging_runs hr', 'count(*)');

        $this->db->pageLimit = $page_limit;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'hr');
        if (!empty($productionOrderId)) {
            $this->db->where('hr.production_order_id', (int)$productionOrderId);
        }
        if (!empty($factoryId)) {
            $this->db->where('hr.factory_id', (int)$factoryId);
        }
        if (!empty($gongCartId)) {
            $this->db->where('hr.gong_cart_id', (int)$gongCartId);
        }
        if ($status !== 'all') {
            $this->db->where('hr.status', $status);
        }

        $cols = 'hr.*, gc.gong_cart_code, gc.gong_cart_name, po.production_order_code';
        $this->db->join('eudr_production_gong_carts gc', 'gc.gong_cart_id = hr.gong_cart_id', 'LEFT');
        $this->db->join('eudr_production_orders po', 'po.production_order_id = hr.production_order_id', 'LEFT');
        $this->db->orderBy('hr.hanging_run_id', 'DESC');
        $records = $this->db->arraybuilder()->paginate('eudr_production_hanging_runs hr', $page, $cols);

        $items = [];
        $runIds = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $item['quality_details'] = [];
                $items[] = $item;
                $runIds[] = (int)($item['hanging_run_id'] ?? 0);
            }
        }

        if (!empty($runIds)) {
            $this->db->where('hanging_run_id', $runIds, 'IN');
            $this->db->where('deleted_by', 0);
            $this->db->orderBy('hanging_quality_detail_id', 'ASC');
            $qualityRows = $this->db->arraybuilder()->get('eudr_production_hanging_run_quality_details') ?? [];

            $qualityMap = [];
            foreach ($qualityRows as $row) {
                $rid = (int)($row['hanging_run_id'] ?? 0);
                $qualityMap[$rid][] = $row;
            }
            foreach ($items as $index => $run) {
                $rid = (int)($run['hanging_run_id'] ?? 0);
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
     * Find a production gong cart by its ID.
     *
     * @param int $gong_cart_id
     * @return ProductionGongCart|null
     */
    public function findProductionGongCartOfId(int $gong_cart_id): ?ProductionGongCart
    {
        $this->db->where('pgc.gong_cart_id', $gong_cart_id);
        $this->db->where('pgc.deleted_by', 0);
        $this->db->join('eudr_factories f', 'f.factory_id = pgc.factory_id', 'LEFT');
        $production_gong_cart = $this->db->getOne('eudr_production_gong_carts pgc', 'pgc.*, f.factory_name');
        if (empty($production_gong_cart)) {
            return null;
        }
        return new ProductionGongCart($production_gong_cart['gong_cart_id'], $production_gong_cart);
    }

    /**
     * Find a production gong cart by its ID with permission check.
     *
     * @param int $gong_cart_id
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return ProductionGongCart|null
     */
    public function findProductionGongCartOfIdWithPermission(int $gong_cart_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductionGongCart
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'pgc');
        $this->db->where('pgc.gong_cart_id', $gong_cart_id);
        $this->db->join('eudr_factories f', 'f.factory_id = pgc.factory_id', 'LEFT');
        $production_gong_cart = $this->db->getOne('eudr_production_gong_carts pgc', 'pgc.*, f.factory_name');
        if (empty($production_gong_cart)) {
            return null;
        }
        return new ProductionGongCart($production_gong_cart['gong_cart_id'], $production_gong_cart);
    }

    /**
     * Find a production gong cart by its code.
     *
     * @param string $code
     * @return ProductionGongCart|null
     */
    public function findProductionGongCartOfCode(string $code): ?ProductionGongCart
    {
        $this->db->where('pgc.gong_cart_code', $code);
        $this->db->where('pgc.deleted_by', 0);
        $this->db->join('eudr_factories f', 'f.factory_id = pgc.factory_id', 'LEFT');
        $production_gong_cart = $this->db->getOne('eudr_production_gong_carts pgc', 'pgc.*, f.factory_name');
        if (empty($production_gong_cart)) {
            return null;
        }
        return new ProductionGongCart($production_gong_cart['gong_cart_id'], $production_gong_cart);
    }

    /**
     * Find a production gong cart by its code with permission check.
     *
     * @param string $code
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return ProductionGongCart|null
     */
    public function findProductionGongCartOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductionGongCart
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'pgc');
        $this->db->where('pgc.gong_cart_code', $code);
        $this->db->join('eudr_factories f', 'f.factory_id = pgc.factory_id', 'LEFT');
        $production_gong_cart = $this->db->getOne('eudr_production_gong_carts pgc', 'pgc.*, f.factory_name');
        if (empty($production_gong_cart)) {
            return null;
        }
        return new ProductionGongCart($production_gong_cart['gong_cart_id'], $production_gong_cart);
    }

    /**
     * Generate a unique code for a production gong cart.
     *
     * @return string
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = 'pgct-' . date('ymd') . '-' . Utils::generateRandomString(8);
            $production_gong_cart = $this->findProductionGongCartOfCode($code);
            if (!$production_gong_cart) {
                break;
            }
        }
        return $code;
    }

    /**
     * Create a new production gong cart with the given data.
     *
     * @param array $data
     * @return ProductionGongCart|null
     */
    public function createProductionGongCart(array $data): ?ProductionGongCart
    {
        $this->db->insert('eudr_production_gong_carts', $data);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }

        $gong_cart_id = $this->db->getInsertId();
        return $this->findProductionGongCartOfId($gong_cart_id);
    }

    /**
     * Update a production gong cart with the given data.
     *
     * @param int $gong_cart_id
     * @param array $data_update
     * @return ProductionGongCart
     * @throws ProductionGongCartNotFoundException
     */
    public function updateProductionGongCart(int $gong_cart_id, array $data_update): ProductionGongCart
    {
        $this->db->where('gong_cart_id', $gong_cart_id);
        $this->db->update('eudr_production_gong_carts', $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new ProductionGongCartNotFoundException("Production Gong Cart not found with ID: $gong_cart_id");
        }
        return $this->findProductionGongCartOfId($gong_cart_id);
    }

    /**
     * Update a production gong cart with the given data and permission check.
     *
     * @param int $gong_cart_id
     * @param array $data_update
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return ProductionGongCart
     * @throws ProductionGongCartNotFoundException
     */
    public function updateProductionGongCartWithPermission(int $gong_cart_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ProductionGongCart
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, '');
        $this->db->where('gong_cart_id', $gong_cart_id);
        $this->db->update('eudr_production_gong_carts', $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new ProductionGongCartNotFoundException("Production Gong Cart not found with ID: $gong_cart_id");
        }
        return $this->findProductionGongCartOfId($gong_cart_id);
    }

    /**
     * Delete a production gong cart.
     *
     * @param int $gong_cart_id
     * @param int $deleted_by
     * @return void
     */
    public function deleteProductionGongCart(int $gong_cart_id, int $deleted_by): void
    {
        $this->db->where('gong_cart_id', $gong_cart_id);
        $this->db->update('eudr_production_gong_carts', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

    /**
     * Delete a production gong cart with permission check.
     *
     * @param int $gong_cart_id
     * @param int $deleted_by
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return void
     */
    public function deleteProductionGongCartWithPermission(int $gong_cart_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, '');
        $this->db->where('gong_cart_id', $gong_cart_id);
        $this->db->update('eudr_production_gong_carts', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
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

    public function getHangingRunDetailWithPermission(int $hanging_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array
    {
        $run = $this->findHangingRunOfIdWithPermission($hanging_run_id, $auth_user_id, $scope, $company_id, $company_id_param);
        if (empty($run)) {
            return null;
        }

        $this->db->where('hr.hanging_run_id', $hanging_run_id);
        $this->db->join('eudr_production_gong_carts gc', 'gc.gong_cart_id = hr.gong_cart_id', 'LEFT');
        $this->db->join('eudr_production_rolling_runs rr', 'rr.rolling_run_id = hr.rolling_run_id', 'LEFT');
        $runDetail = $this->db->getOne(
            'eudr_production_hanging_runs hr',
            'hr.*, gc.gong_cart_code, gc.gong_cart_name, gc.max_poles, rr.rolling_run_id AS source_rolling_run_id, rr.status AS source_rolling_run_status'
        );
        if (empty($runDetail)) {
            return null;
        }

        $this->db->where('hanging_run_id', $hanging_run_id);
        $this->db->where('deleted_by', 0);
        $this->db->orderBy('quality_type', 'ASC');
        $qualityDetails = $this->db->get('eudr_production_hanging_run_quality_details') ?? [];

        $this->db->where('hanging_run_id', $hanging_run_id);
        $this->db->where('deleted_by', 0);
        $this->db->orderBy('pole_no', 'ASC');
        $poles = $this->db->get('eudr_production_hanging_run_poles') ?? [];

        $this->db->where('a.hanging_run_id', $hanging_run_id);
        $this->db->where('a.deleted_by', 0);
        $this->db->orderBy('a.pole_no', 'ASC');
        $this->db->join('eudr_production_hanging_run_poles p', 'p.hanging_run_pole_id = a.hanging_run_pole_id', 'LEFT');
        $assignments = $this->db->get(
            'eudr_production_hanging_quality_pole_assignments a',
            null,
            'a.*, p.pole_status'
        ) ?? [];

        $runDetail['quality_details'] = $qualityDetails;
        $runDetail['poles'] = $poles;
        $runDetail['pole_assignments'] = $assignments;

        return $runDetail;
    }

    /**
     * Assign rolling sheets to hanging poles.
     *
     * @param array $data
     * @return array|null
     */
    public function assignRollingSheetsToHangingPoles(array $data): ?array
    {
        $rollingRunId = (int)($data['rolling_run_id'] ?? 0);
        $gongCartId = (int)($data['gong_cart_id'] ?? 0);
        $details = $data['details'] ?? [];
        $notes = $data['notes'] ?? null;
        $updatedBy = (int)($data['updated_by'] ?? 0);

        if ($rollingRunId <= 0 || $gongCartId <= 0 || !is_array($details) || count($details) === 0 || $updatedBy <= 0) {
            return null;
        }

        $this->db->startTransaction();

        $this->db->where('rolling_run_id', $rollingRunId);
        $this->db->where('deleted_by', 0);
        $rollingRun = $this->db->getOne('eudr_production_rolling_runs', '*');
        if (empty($rollingRun)) {
            $this->db->rollback();
            return null;
        }
        if ((string)($rollingRun['status'] ?? '') !== 'completed') {
            $this->db->rollback();
            return null;
        }

        $this->db->where('gong_cart_id', $gongCartId);
        $this->db->where('deleted_by', 0);
        $gongCart = $this->db->getOne('eudr_production_gong_carts', '*');
        if (empty($gongCart)) {
            $this->db->rollback();
            return null;
        }

        if ((int)($gongCart['company_id'] ?? 0) !== (int)($rollingRun['company_id'] ?? 0)) {
            $this->db->rollback();
            return null;
        }
        if ((int)($gongCart['factory_id'] ?? 0) !== (int)($rollingRun['factory_id'] ?? 0)) {
            $this->db->rollback();
            return null;
        }

        $this->db->where('rolling_run_id', $rollingRunId);
        $this->db->where('deleted_by', 0);
        $rollingQualities = $this->db->get('eudr_production_rolling_run_quality_details', null, 'quality_type, output_sheet_count');
        $rollingOutputMap = [];
        foreach ($rollingQualities as $row) {
            $rollingOutputMap[(string)$row['quality_type']] = (int)($row['output_sheet_count'] ?? 0);
        }

        $allowedQualityTypes = Utils::getAllowedQualityTypes();
        $incomingQualityTypes = [];
        foreach ($details as $item) {
            $qualityType = (string)($item['quality_type'] ?? '');
            if (!in_array($qualityType, $allowedQualityTypes, true)) {
                $this->db->rollback();
                return null;
            }
            if (in_array($qualityType, $incomingQualityTypes, true)) {
                $this->db->rollback();
                return null;
            }
            $incomingQualityTypes[] = $qualityType;

            $inputSheetCount = (int)($item['input_sheet_count'] ?? 0);
            if ($inputSheetCount < 0) {
                $this->db->rollback();
                return null;
            }

            $maxAvailable = (int)($rollingOutputMap[$qualityType] ?? 0);
            if ($maxAvailable > 0 && $inputSheetCount > $maxAvailable) {
                $this->db->rollback();
                return null;
            }
        }

        $now = date('Y-m-d H:i:s', time());
        $maxPoles = (int)($gongCart['max_poles'] ?? 0);

        $this->db->where('rolling_run_id', $rollingRunId);
        $this->db->where('deleted_by', 0);
        $hangingRun = $this->db->getOne('eudr_production_hanging_runs', '*');

        if (empty($hangingRun)) {
            $this->db->insert('eudr_production_hanging_runs', [
                'rolling_run_id' => $rollingRunId,
                'production_order_id' => (int)($rollingRun['production_order_id'] ?? 0),
                'company_id' => (int)($rollingRun['company_id'] ?? 0),
                'factory_id' => (int)($rollingRun['factory_id'] ?? 0),
                'gong_cart_id' => $gongCartId,
                'gong_max_poles_snapshot' => $maxPoles,
                'started_at' => $now,
                'status' => 'in_progress',
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
            $hangingRunId = (int)$this->db->getInsertId();
        } else {
            $hangingRunId = (int)$hangingRun['hanging_run_id'];
            $this->db->where('hanging_run_id', $hangingRunId);
            $this->db->update('eudr_production_hanging_runs', [
                'gong_cart_id' => $gongCartId,
                'gong_max_poles_snapshot' => $maxPoles,
                'status' => 'in_progress',
                'notes' => $notes,
                'updated_by' => $updatedBy,
                'updated_at' => $now,
            ]);
            if ($this->db->getLastErrno() !== 0) {
                $this->db->rollback();
                return null;
            }
        }

        $this->db->where('hanging_run_id', $hangingRunId);
        $allPoles = $this->db->get('eudr_production_hanging_run_poles', null, 'hanging_run_pole_id, pole_no');
        $poleIdByNo = [];
        foreach ($allPoles as $pole) {
            $poleIdByNo[(int)$pole['pole_no']] = (int)$pole['hanging_run_pole_id'];
        }

        for ($poleNo = 1; $poleNo <= $maxPoles; $poleNo++) {
            if (!isset($poleIdByNo[$poleNo])) {
                $this->db->insert('eudr_production_hanging_run_poles', [
                    'hanging_run_id' => $hangingRunId,
                    'pole_no' => $poleNo,
                    'pole_status' => 'empty',
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
                $poleIdByNo[$poleNo] = (int)$this->db->getInsertId();
            }
        }

        $this->db->where('hanging_run_id', $hangingRunId);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_production_hanging_run_poles', [
            'pole_status' => 'empty',
            'updated_by' => $updatedBy,
            'updated_at' => $now,
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->where('hanging_run_id', $hangingRunId);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_production_hanging_quality_pole_assignments', [
            'deleted_by' => $updatedBy,
            'deleted_at' => $now,
            'updated_by' => $updatedBy,
            'updated_at' => $now,
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $resultDetails = [];

        foreach ($details as $item) {
            $qualityType = (string)$item['quality_type'];
            $gradeId = (int)($item['grade_id'] ?? 0);
            $inputSheetCount = (int)($item['input_sheet_count'] ?? 0);
            $notesDetail = $item['notes'] ?? null;
            $poleNumbers = $item['pole_numbers'] ?? [];
            $poleNumbers = is_array($poleNumbers) ? $poleNumbers : [];
            $poleNumbers = array_values(array_unique(array_map('intval', $poleNumbers)));

            $this->db->where('hanging_run_id', $hangingRunId);
            $this->db->where('quality_type', $qualityType);
            $existingQuality = $this->db->getOne('eudr_production_hanging_run_quality_details', '*');

            if (!empty($existingQuality)) {
                $this->db->where('hanging_quality_detail_id', (int)$existingQuality['hanging_quality_detail_id']);
                $this->db->update('eudr_production_hanging_run_quality_details', [
                    'grade_id' => $gradeId,
                    'input_sheet_count' => $inputSheetCount,
                    'allocated_pole_count' => count($poleNumbers),
                    'notes' => $notesDetail,
                    'updated_by' => $updatedBy,
                    'updated_at' => $now,
                    'deleted_by' => 0,
                    'deleted_at' => null,
                ]);
                if ($this->db->getLastErrno() !== 0) {
                    $this->db->rollback();
                    return null;
                }
                $hangingQualityDetailId = (int)$existingQuality['hanging_quality_detail_id'];
            } else {
                $this->db->insert('eudr_production_hanging_run_quality_details', [
                    'hanging_run_id' => $hangingRunId,
                    'grade_id' => $gradeId,
                    'quality_type' => $qualityType,
                    'input_sheet_count' => $inputSheetCount,
                    'output_sheet_count' => 0,
                    'allocated_pole_count' => count($poleNumbers),
                    'notes' => $notesDetail,
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
                $hangingQualityDetailId = (int)$this->db->getInsertId();
            }

            foreach ($poleNumbers as $poleNo) {
                if ($poleNo <= 0 || $poleNo > $maxPoles || !isset($poleIdByNo[$poleNo])) {
                    $this->db->rollback();
                    return null;
                }

                $this->db->where('hanging_run_pole_id', $poleIdByNo[$poleNo]);
                $this->db->update('eudr_production_hanging_run_poles', [
                    'pole_status' => 'occupied',
                    'updated_by' => $updatedBy,
                    'updated_at' => $now,
                    'deleted_by' => 0,
                    'deleted_at' => null,
                ]);
                if ($this->db->getLastErrno() !== 0) {
                    $this->db->rollback();
                    return null;
                }

                $this->db->where('hanging_run_id', $hangingRunId);
                $this->db->where('pole_no', $poleNo);
                $existingAssignment = $this->db->getOne('eudr_production_hanging_quality_pole_assignments', '*');

                if (!empty($existingAssignment)) {
                    $this->db->where('hanging_pole_assignment_id', (int)$existingAssignment['hanging_pole_assignment_id']);
                    $this->db->update('eudr_production_hanging_quality_pole_assignments', [
                        'hanging_run_pole_id' => $poleIdByNo[$poleNo],
                        'hanging_quality_detail_id' => $hangingQualityDetailId,
                        'grade_id' => $gradeId,
                        'quality_type' => $qualityType,
                        'assigned_sheet_count' => 0,
                        'notes' => $notesDetail,
                        'updated_by' => $updatedBy,
                        'updated_at' => $now,
                        'deleted_by' => 0,
                        'deleted_at' => null,
                    ]);
                } else {
                    $this->db->insert('eudr_production_hanging_quality_pole_assignments', [
                        'hanging_run_id' => $hangingRunId,
                        'hanging_run_pole_id' => $poleIdByNo[$poleNo],
                        'hanging_quality_detail_id' => $hangingQualityDetailId,
                        'grade_id' => $gradeId,
                        'quality_type' => $qualityType,
                        'pole_no' => $poleNo,
                        'assigned_sheet_count' => 0,
                        'notes' => $notesDetail,
                        'created_by' => $updatedBy,
                        'created_at' => $now,
                        'updated_by' => 0,
                        'updated_at' => null,
                        'deleted_by' => 0,
                        'deleted_at' => null,
                    ]);
                }
                if ($this->db->getLastErrno() !== 0) {
                    $this->db->rollback();
                    return null;
                }
            }

            $resultDetails[] = [
                'quality_type' => $qualityType,
                'grade_id' => $gradeId,
                'input_sheet_count' => $inputSheetCount,
                'allocated_pole_count' => count($poleNumbers),
                'pole_numbers' => $poleNumbers,
            ];
        }

        $this->db->where('gong_cart_id', $gongCartId);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_production_gong_carts', [
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
            'hanging_run_id' => $hangingRunId,
            'rolling_run_id' => $rollingRunId,
            'gong_cart_id' => $gongCartId,
            'status' => 'in_progress',
            'started_at' => $now,
            'quality_details' => $resultDetails,
        ];
    }

    /**
     * Complete the hanging run quality details and update related records.
     *
     * @param array $data
     * @return array|null
     */
    public function completeHangingRunQualityDetails(array $data): ?array
    {
        $hangingRunId = (int)($data['hanging_run_id'] ?? 0);
        $details = $data['details'] ?? [];
        $startedAt = !empty($data['started_at']) ? (string)$data['started_at'] : null;
        $endedAt = !empty($data['ended_at']) ? (string)$data['ended_at'] : null;
        $updatedBy = (int)($data['updated_by'] ?? 0);

        if ($hangingRunId <= 0 || !is_array($details) || count($details) === 0 || $updatedBy <= 0) {
            return null;
        }

        $this->db->where('hanging_run_id', $hangingRunId);
        $this->db->where('deleted_by', 0);
        $hangingRun = $this->db->getOne('eudr_production_hanging_runs', '*');
        if (empty($hangingRun)) {
            return null;
        }

        $now = date('Y-m-d H:i:s', time());
        $runEndedAt = $endedAt ?? $now;
        $resultDetails = [];
        $allowedQualityTypes = Utils::getAllowedQualityTypes();

        $this->db->startTransaction();

        foreach ($details as $item) {
            $qualityType = (string)($item['quality_type'] ?? '');
            $outputSheetCount = (int)($item['output_sheet_count'] ?? -1);
            $notes = $item['notes'] ?? null;

            if (!in_array($qualityType, $allowedQualityTypes, true) || $outputSheetCount < 0) {
                $this->db->rollback();
                return null;
            }

            $this->db->where('hanging_run_id', $hangingRunId);
            $this->db->where('quality_type', $qualityType);
            $qualityDetail = $this->db->getOne('eudr_production_hanging_run_quality_details', '*');
            if (empty($qualityDetail)) {
                $this->db->rollback();
                return null;
            }

            $this->db->where('hanging_quality_detail_id', (int)$qualityDetail['hanging_quality_detail_id']);
            $this->db->update('eudr_production_hanging_run_quality_details', [
                'output_sheet_count' => $outputSheetCount,
                'notes' => $notes,
                'updated_by' => $updatedBy,
                'updated_at' => $now,
            ]);
            if ($this->db->getLastErrno() !== 0) {
                $this->db->rollback();
                return null;
            }

            $resultDetails[] = [
                'quality_type' => $qualityType,
                'output_sheet_count' => $outputSheetCount,
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
        $this->db->where('hanging_run_id', $hangingRunId);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_production_hanging_runs', $runUpdateData);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->where('hanging_run_id', $hangingRunId);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_production_hanging_run_poles', [
            'pole_status' => 'empty',
            'updated_by' => $updatedBy,
            'updated_at' => $now,
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->where('hanging_run_id', $hangingRunId);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_production_hanging_quality_pole_assignments', [
            'deleted_by' => $updatedBy,
            'deleted_at' => $now,
            'updated_by' => $updatedBy,
            'updated_at' => $now,
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->where('gong_cart_id', (int)($hangingRun['gong_cart_id'] ?? 0));
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_production_gong_carts', [
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
            'hanging_run_id' => $hangingRunId,
            'status' => 'completed',
            'ended_at' => $runEndedAt,
            'quality_details' => $resultDetails,
        ];
    }
}
