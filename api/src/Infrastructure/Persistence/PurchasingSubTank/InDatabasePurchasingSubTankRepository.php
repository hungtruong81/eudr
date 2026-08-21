<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PurchasingSubTank;

use App\Application\Utility\CurrentUserContext;
use App\Application\Utility\Utils;
use App\Domain\PurchasingSubTank\PurchasingSubTank;
use App\Domain\PurchasingSubTank\PurchasingSubTankNotFoundException;
use App\Domain\PurchasingSubTank\PurchasingSubTankRepository;

class InDatabasePurchasingSubTankRepository implements PurchasingSubTankRepository
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
    private function scopeWhere(string $scope, int $authUserId, int $companyId, ?int $companyIdParam = null, string $alias = 's'): void
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
        $rubber_type = $params['rubber_type'] ?? 'all';
        $status = $params['status'] ?? 'all';
        $companyIdParam = $company_id_param ?? 0;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 's');
        if (!empty($search)) {
            $this->db->where('(s.sub_tank_name LIKE ? OR s.sub_tank_code LIKE ?)', ["%$search%", "%$search%"]);
        }
        if ($rubber_type !== 'all') {
            $this->db->where('s.rubber_type', $rubber_type);
        }
        if ($status !== 'all') {
            $this->db->where('s.status', $status);
        }
        $total_records = (int)$this->db->getValue('eudr_purchasing_sub_tanks s', 'count(*)');

        $this->db->pageLimit = $page_limit;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 's');
        if (!empty($search)) {
            $this->db->where('(s.sub_tank_name LIKE ? OR s.sub_tank_code LIKE ?)', ["%$search%", "%$search%"]);
        }
        if ($rubber_type !== 'all') {
            $this->db->where('s.rubber_type', $rubber_type);
        }
        if ($status !== 'all') {
            $this->db->where('s.status', $status);
        }

        $cols = 's.*, f.factory_name';
        if (!empty($params['order_by'])) {
            $this->db->orderBy('s.' . $params['order_by'], $params['order_type'] ?? 'ASC');
        } else {
            $this->db->orderBy('s.sub_tank_id', 'DESC');
        }

        $this->db->join('eudr_factories f', 'f.factory_id = s.factory_id', 'LEFT');
        $records = $this->db->arrayBuilder()->paginate('eudr_purchasing_sub_tanks s', $page, $cols);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = new PurchasingSubTank((int)$item['sub_tank_id'], $item);
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
     * @param int $sub_tank_id
     * @return PurchasingSubTank|null
     * @throws PurchasingSubTankNotFoundException
     */
    public function findPurchasingSubTankOfId(int $sub_tank_id): ?PurchasingSubTank
    {
        $this->db->where('s.sub_tank_id', $sub_tank_id);
        $this->db->where('s.deleted_by', 0);
        $this->db->join('eudr_factories f', 'f.factory_id = s.factory_id', 'LEFT');
        $tank = $this->db->getOne('eudr_purchasing_sub_tanks s', 's.*, f.factory_name');
        if (empty($tank)) {
            return null;
        }

        return new PurchasingSubTank((int)$tank['sub_tank_id'], $tank);
    }

    /**
     * @param int $sub_tank_id
     * @return PurchasingSubTank|null
     * @throws PurchasingSubTankNotFoundException
     */
    public function findPurchasingSubTankOfIdWithPermission(int $sub_tank_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?PurchasingSubTank
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 's');
        $this->db->where('s.sub_tank_id', $sub_tank_id);
        $this->db->join('eudr_factories f', 'f.factory_id = s.factory_id', 'LEFT');

        $tank = $this->db->getOne('eudr_purchasing_sub_tanks s', 's.*, f.factory_name');
        if (empty($tank)) {
            return null;
        }

        return new PurchasingSubTank((int)$tank['sub_tank_id'], $tank);
    }

    /**
     * @param string $code
     * @return PurchasingSubTank|null
     */
    public function findPurchasingSubTankOfCode(string $code): ?PurchasingSubTank
    {
        $this->db->where('s.sub_tank_code', $code);
        $this->db->where('s.deleted_by', 0);
        $this->db->join('eudr_factories f', 'f.factory_id = s.factory_id', 'LEFT');
        $tank = $this->db->getOne('eudr_purchasing_sub_tanks s', 's.*, f.factory_name');
        if (empty($tank)) {
            return null;
        }

        return new PurchasingSubTank((int)$tank['sub_tank_id'], $tank);
    }

    /**
     * @param string $code
     * @return PurchasingSubTank|null
     */
    public function findPurchasingSubTankOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?PurchasingSubTank
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 's');
        $this->db->where('s.sub_tank_code', $code);
        $this->db->join('eudr_factories f', 'f.factory_id = s.factory_id', 'LEFT');

        $tank = $this->db->getOne('eudr_purchasing_sub_tanks s', 's.*, f.factory_name');
        if (empty($tank)) {
            return null;
        }

        return new PurchasingSubTank((int)$tank['sub_tank_id'], $tank);
    }

    /**
     * @return string
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = 'sbtk-' . date('ymd') . '-' . Utils::generateRandomString(8);
            $tank = $this->findPurchasingSubTankOfCode($code);
            if (!$tank) {
                break;
            }
        }

        return $code;
    }

    /**
     * @param array $data
     * @return PurchasingSubTank|null
     */
    public function createPurchasingSubTank(array $data): ?PurchasingSubTank
    {
        $this->db->insert('eudr_purchasing_sub_tanks', $data);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }

        $id = (int)$this->db->getInsertId();
        return $this->findPurchasingSubTankOfId($id);
    }

    /**
     * @param int $sub_tank_id
     * @param array $data_update
     * @return PurchasingSubTank
     */
    public function updatePurchasingSubTank(int $sub_tank_id, array $data_update): PurchasingSubTank
    {
        $this->db->where('sub_tank_id', $sub_tank_id);
        $this->db->update('eudr_purchasing_sub_tanks', $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new PurchasingSubTankNotFoundException("Purchasing sub tank not found with ID: $sub_tank_id");
        }

        return $this->findPurchasingSubTankOfId($sub_tank_id);
    }

    /**
     * @param int $sub_tank_id
     * @param array $data_update
     * @return PurchasingSubTank
     */
    public function updatePurchasingSubTankWithPermission(int $sub_tank_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): PurchasingSubTank
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, '');
        $this->db->where('sub_tank_id', $sub_tank_id);
        $this->db->update('eudr_purchasing_sub_tanks', $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new PurchasingSubTankNotFoundException("Purchasing sub tank not found with ID: $sub_tank_id");
        }

        return $this->findPurchasingSubTankOfId($sub_tank_id);
    }

    /**
     * @param int $sub_tank_id
     * @param int $deleted_by
     * @return void
     */
    public function deletePurchasingSubTank(int $sub_tank_id, int $deleted_by): void
    {
        $this->db->where('sub_tank_id', $sub_tank_id);
        $this->db->update('eudr_purchasing_sub_tanks', [
            'deleted_by' => $deleted_by,
            'deleted_at' => date('Y-m-d H:i:s', time()),
        ]);
    }

    /**
     * @param int $sub_tank_id
     * @param int $deleted_by
     * @return void
     */
    public function deletePurchasingSubTankWithPermission(int $sub_tank_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, '');
        $this->db->where('sub_tank_id', $sub_tank_id);
        $this->db->update('eudr_purchasing_sub_tanks', [
            'deleted_by' => $deleted_by,
            'deleted_at' => date('Y-m-d H:i:s', time()),
        ]);
    }

    /**
     * @param int $sub_tank_id
     * @param int $company_id
     * @param float $weight_kg
     * @param string $rubber_type
     * @param string $event_time
     * @param string|null $notes
     * @param int $user_id
     * @param string $source_type
     * @return PurchasingSubTank
     * @throws \RuntimeException
     */
    public function recordStockMovement(
        int $sub_tank_id,
        int $company_id,
        float $weight_kg,
        string $rubber_type,
        string $event_time,
        ?string $notes,
        int $user_id,
        string $source_type = 'supplier_delivery'
    ): PurchasingSubTank {
        if ($weight_kg <= 0) {
            throw new \RuntimeException('Khối lượng nhập kho phải lớn hơn 0');
        }

        $this->db->startTransaction();
        try {
            $tank = $this->db->rawQueryOne(
                'SELECT *
                 FROM eudr_purchasing_sub_tanks
                 WHERE sub_tank_id = ? AND company_id = ? AND deleted_by = 0
                 FOR UPDATE',
                [$sub_tank_id, $company_id]
            );
            if (empty($tank)) {
                throw new \RuntimeException('Không tìm thấy bình con thuộc công ty hiện tại');
            }
            if (in_array($tank['status'], ['inactive', 'maintenance', 'damaged'], true)) {
                throw new \RuntimeException('Bình con không sẵn sàng để nhập tồn');
            }
            if ($tank['rubber_type'] !== 'mixed' && $tank['rubber_type'] !== $rubber_type) {
                throw new \RuntimeException('Loại mủ không phù hợp với loại mủ của bình con');
            }

            $before = (float)$tank['current_volume_kg'];
            $capacity = (float)$tank['capacity_kg'];
            $after = $before + $weight_kg;
            if ($after > $capacity + 0.0001) {
                throw new \RuntimeException('Khối lượng nhập tồn vượt quá dung tích bình con');
            }

            $now = date('Y-m-d H:i:s');
            $this->db->where('sub_tank_id', $sub_tank_id);
            $this->db->where('company_id', $company_id);
            if (!$this->db->update('eudr_purchasing_sub_tanks', [
                'current_volume_kg' => $after,
                'status' => $after >= $capacity - 0.0001 ? 'full' : 'in_use',
                'updated_at' => $now,
                'updated_by' => $user_id,
            ]) || $this->db->count !== 1) {
                throw new \RuntimeException($this->db->getLastError());
            }

            $history = [
                'sub_tank_id' => $sub_tank_id,
                'entity_type' => 'manual_adjustment',
                'entity_id' => 0,
                'action_type' => 'input',
                'rubber_type' => $rubber_type,
                'qty_in_kg' => $weight_kg,
                'weight_kg' => $weight_kg,
                'volume_before_kg' => $before,
                'volume_after_kg' => $after,
                'event_time' => $event_time,
                'operator_user_id' => $user_id,
                'notes' => trim($source_type . ($notes ? ': ' . $notes : '')),
                'created_at' => $now,
                'created_by' => $user_id,
            ];
            if (!$this->db->insert('eudr_purchasing_sub_tank_history', $history)) {
                throw new \RuntimeException($this->db->getLastError());
            }

            $this->db->commit();
            $updated = $this->findPurchasingSubTankOfId($sub_tank_id);
            if ($updated === null) {
                throw new \RuntimeException('Không thể đọc lại bình con sau khi nhập tồn');
            }
            return $updated;
        } catch (\Throwable $exception) {
            $this->db->rollback();
            throw $exception;
        }
    }

    /**
     * @param int $sub_tank_id
     * @param int $company_id
     * @param float $weight_delta_kg
     * @param string $rubber_type
     * @param string $event_time
     * @param string $reason
     * @param int $user_id
     * @return PurchasingSubTank
     * @throws \RuntimeException
     */
    public function recordStockAdjustment(
        int $sub_tank_id,
        int $company_id,
        float $weight_delta_kg,
        string $rubber_type,
        string $event_time,
        string $reason,
        int $user_id
    ): PurchasingSubTank {
        if (abs($weight_delta_kg) < 0.0001) {
            throw new \RuntimeException('Khối lượng điều chỉnh phải khác 0');
        }

        $this->db->startTransaction();
        try {
            $tank = $this->db->rawQueryOne(
                'SELECT *
                 FROM eudr_purchasing_sub_tanks
                 WHERE sub_tank_id = ? AND company_id = ? AND deleted_by = 0
                 FOR UPDATE',
                [$sub_tank_id, $company_id]
            );
            if (empty($tank)) {
                throw new \RuntimeException('Không tìm thấy bình con thuộc công ty hiện tại');
            }
            if (in_array($tank['status'], ['inactive', 'maintenance', 'damaged'], true)) {
                throw new \RuntimeException('Bình con không sẵn sàng để điều chỉnh tồn');
            }
            if ($tank['rubber_type'] !== 'mixed' && $tank['rubber_type'] !== $rubber_type) {
                throw new \RuntimeException('Loại mủ không phù hợp với loại mủ của bình con');
            }

            $before = (float)$tank['current_volume_kg'];
            $capacity = (float)$tank['capacity_kg'];
            $after = $before + $weight_delta_kg;
            if ($after < -0.0001) {
                throw new \RuntimeException('Điều chỉnh làm tồn kho bình con nhỏ hơn 0');
            }
            if ($after > $capacity + 0.0001) {
                throw new \RuntimeException('Điều chỉnh làm tồn kho vượt quá dung tích bình con');
            }
            $after = max(0.0, $after);

            $now = date('Y-m-d H:i:s');
            $status = $after <= 0.0001
                ? 'idle'
                : ($after >= $capacity - 0.0001 ? 'full' : 'in_use');
            $this->db->where('sub_tank_id', $sub_tank_id);
            $this->db->where('company_id', $company_id);
            if (!$this->db->update('eudr_purchasing_sub_tanks', [
                'current_volume_kg' => $after,
                'status' => $status,
                'updated_at' => $now,
                'updated_by' => $user_id,
            ]) || $this->db->count !== 1) {
                throw new \RuntimeException($this->db->getLastError());
            }

            if (!$this->db->insert('eudr_purchasing_sub_tank_history', [
                'sub_tank_id' => $sub_tank_id,
                'entity_type' => 'manual_adjustment',
                'entity_id' => 0,
                'action_type' => 'adjustment',
                'rubber_type' => $rubber_type,
                'qty_in_kg' => $weight_delta_kg > 0 ? $weight_delta_kg : 0,
                'qty_out_kg' => $weight_delta_kg < 0 ? abs($weight_delta_kg) : 0,
                'weight_kg' => abs($weight_delta_kg),
                'volume_before_kg' => $before,
                'volume_after_kg' => $after,
                'event_time' => $event_time,
                'operator_user_id' => $user_id,
                'notes' => $reason,
                'created_at' => $now,
                'created_by' => $user_id,
            ])) {
                throw new \RuntimeException($this->db->getLastError());
            }

            $this->db->commit();
            $updated = $this->findPurchasingSubTankOfId($sub_tank_id);
            if ($updated === null) {
                throw new \RuntimeException('Không thể đọc lại bình con sau khi điều chỉnh tồn');
            }
            return $updated;
        } catch (\Throwable $exception) {
            $this->db->rollback();
            throw $exception;
        }
    }

    /**
     * @param int $sub_tank_id
     * @param array $params
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return array
     */
    public function getHistoryOfPurchasingSubTank(int $sub_tank_id, array $params, ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $action_type = $params['action_type'] ?? 'all';
        $rubber_type = $params['rubber_type'] ?? 'all';
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $companyIdParam = $company_id_param ?? 0;

        $this->db->join('eudr_purchasing_sub_tanks s', 's.sub_tank_id = h.sub_tank_id', 'LEFT');
        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 's');
        $this->db->where('h.sub_tank_id', $sub_tank_id);
        if ($action_type !== 'all') {
            $this->db->where('h.action_type', $action_type);
        }
        if ($rubber_type !== 'all') {
            $this->db->where('h.rubber_type', $rubber_type);
        }
        $total_records = (int)$this->db->getValue('eudr_purchasing_sub_tank_history h', 'count(*)');

        $this->db->pageLimit = $page_limit;

        $this->db->join('eudr_purchasing_sub_tanks s', 's.sub_tank_id = h.sub_tank_id', 'LEFT');
        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 's');
        $this->db->where('h.sub_tank_id', $sub_tank_id);
        if ($action_type !== 'all') {
            $this->db->where('h.action_type', $action_type);
        }
        if ($rubber_type !== 'all') {
            $this->db->where('h.rubber_type', $rubber_type);
        }

        $this->db->orderBy('h.event_time', 'DESC');
        $records = $this->db->arrayBuilder()->paginate('eudr_purchasing_sub_tank_history h', $page, 'h.*');

        return [
            'current_page' => $page,
            'total_pages' => $this->db->totalPages,
            'total_records' => $total_records,
            'page_limit' => $this->db->pageLimit,
            'records' => $records ?? [],
        ];
    }
}
