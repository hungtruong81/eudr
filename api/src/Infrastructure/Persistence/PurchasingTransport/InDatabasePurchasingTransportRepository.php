<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PurchasingTransport;

use App\Application\Utility\Utils;
use App\Domain\PurchasingTransport\PurchasingTransport;
use App\Domain\PurchasingTransport\PurchasingTransportRepository;
use RuntimeException;

final class InDatabasePurchasingTransportRepository implements PurchasingTransportRepository
{
    /**
     * @var \MysqliDb
     */
    public function __construct(private \MysqliDb $db) {}

    /**
     * @param array $data
     * @return PurchasingTransport
     * @throws RuntimeException
     */
    public function create(array $data): PurchasingTransport
    {
        $this->db->startTransaction();
        try {
            $order = $this->lockOrder((int)$data['purchase_order_id'], (int)$data['company_id']);
            if (!in_array($order['status'], ['buyer_reconfirmed', 'transport_planned'], true)) {
                throw new RuntimeException('Phiếu thu mua phải ở trạng thái buyer_reconfirmed hoặc transport_planned để tạo chuyến xe');
            }
            $this->sourceCompanyId($order);
            $this->assertFactory((int)$data['destination_factory_id'], (int)$data['company_id']);
            $vehicle = $this->assertVehicle((int)$data['vehicle_id'], (int)$data['company_id']);
            $now = $this->now();
            $id = $this->insertOrFail('eudr_purchasing_transports', [
                'purchase_transport_code' => $this->generateCode(),
                'purchase_order_id' => $order['purchase_order_id'],
                'company_id' => $data['company_id'],
                'source_location' => $data['source_location'] ?? null,
                'destination_factory_id' => $data['destination_factory_id'],
                'vehicle_id' => $data['vehicle_id'],
                'vehicle_license_plate' => $vehicle['license_plate'] ?? null,
                'driver_user_id' => $data['driver_user_id'] ?? null,
                'driver_name' => $data['driver_name'] ?? null,
                'driver_phone' => $data['driver_phone'] ?? null,
                'seal_no' => $data['seal_no'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'planned',
                'created_at' => $now,
                'created_by' => $data['created_by'],
            ]);
            if ($order['status'] === 'buyer_reconfirmed') {
                $this->setOrderStatus($order, 'transport_planned', (int)$data['created_by'], 'transport_create', $data['notes'] ?? null);
            }
            $this->db->commit();
            return $this->findById($id, (int)$data['company_id']);
        } catch (\Throwable $exception) {
            $this->db->rollback();
            throw $exception;
        }
    }

    /**
     * @param string $code
     * @param int $companyId
     * @return PurchasingTransport|null
     */
    public function findByCode(string $code, int $companyId): ?PurchasingTransport
    {
        $row = $this->queryOne(
            'SELECT t.*, po.purchase_order_code, po.status AS purchase_order_status,
                    f.factory_code, f.factory_name, v.vehicle_code, v.vehicle_name
             FROM eudr_purchasing_transports t
             INNER JOIN eudr_purchasing_orders po ON po.purchase_order_id = t.purchase_order_id AND po.deleted_by = 0
             INNER JOIN eudr_factories f ON f.factory_id = t.destination_factory_id AND f.deleted_by = 0
             LEFT JOIN eudr_transportation_vehicle v ON v.vehicle_id = t.vehicle_id AND v.deleted_by = 0
             WHERE t.purchase_transport_code = ? AND t.company_id = ? AND t.deleted_by = 0',
            [$code, $companyId]
        );
        return $row === null ? null : $this->entity($row, true);
    }

    /**
     * @param array $params
     * @param int $companyId
     * @param int $userId
     * @param string $scope
     * @return array
     */
    public function findAll(array $params, int $companyId, int $userId, string $scope): array
    {
        $page = max(1, (int)($params['page'] ?? 1));
        $limit = min(100, max(1, (int)($params['page_limit'] ?? $params['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $where = ['t.company_id = ?', 't.deleted_by = 0'];
        $bindings = [$companyId];
        if ($scope === 'self') {
            $where[] = 't.created_by = ?';
            $bindings[] = $userId;
        }
        foreach (['purchase_order_id', 'destination_factory_id', 'vehicle_id'] as $field) {
            if (isset($params[$field]) && $params[$field] !== '') {
                $where[] = 't.' . $field . ' = ?';
                $bindings[] = (int)$params[$field];
            }
        }
        if (!empty($params['status']) && $params['status'] !== 'all') {
            $where[] = 't.status = ?';
            $bindings[] = (string)$params['status'];
        }
        if (!empty($params['date_from'])) {
            $where[] = 't.created_at >= ?';
            $bindings[] = $params['date_from'] . ' 00:00:00';
        }
        if (!empty($params['date_to'])) {
            $where[] = 't.created_at <= ?';
            $bindings[] = $params['date_to'] . ' 23:59:59';
        }
        if (!empty($params['search'])) {
            $where[] = '(t.purchase_transport_code LIKE ? OR po.purchase_order_code LIKE ? OR v.license_plate LIKE ?)';
            $search = '%' . trim((string)$params['search']) . '%';
            array_push($bindings, $search, $search, $search);
        }
        $whereSql = implode(' AND ', $where);
        $count = $this->queryOne(
            'SELECT COUNT(*) AS total
             FROM eudr_purchasing_transports t
             INNER JOIN eudr_purchasing_orders po ON po.purchase_order_id = t.purchase_order_id AND po.deleted_by = 0
             LEFT JOIN eudr_transportation_vehicle v ON v.vehicle_id = t.vehicle_id AND v.deleted_by = 0
             WHERE ' . $whereSql,
            $bindings
        );
        $rows = $this->db->rawQuery(
            'SELECT t.*, po.purchase_order_code, po.status AS purchase_order_status,
                    f.factory_code, f.factory_name, v.vehicle_code, v.vehicle_name,
                    COUNT(l.purchase_transport_sub_tank_id) AS line_count,
                    COALESCE(SUM(l.loaded_weight_kg), 0) AS loaded_weight_total_kg
             FROM eudr_purchasing_transports t
             INNER JOIN eudr_purchasing_orders po ON po.purchase_order_id = t.purchase_order_id AND po.deleted_by = 0
             INNER JOIN eudr_factories f ON f.factory_id = t.destination_factory_id AND f.deleted_by = 0
             LEFT JOIN eudr_transportation_vehicle v ON v.vehicle_id = t.vehicle_id AND v.deleted_by = 0
             LEFT JOIN eudr_purchasing_transport_sub_tanks l ON l.purchase_transport_id = t.purchase_transport_id
             WHERE ' . $whereSql . '
             GROUP BY t.purchase_transport_id
             ORDER BY t.created_at DESC, t.purchase_transport_id DESC
             LIMIT ?, ?',
            array_merge($bindings, [$offset, $limit])
        );
        $total = (int)($count['total'] ?? 0);
        return [
            'current_page' => $page,
            'page_limit' => $limit,
            'total_records' => $total,
            'total_pages' => (int)ceil($total / $limit),
            'records' => array_map(fn(array $row): PurchasingTransport => $this->entity($row, false), $rows ?: []),
        ];
    }

    /**
     * @param string $code
     * @param int $companyId
     * @param array $data
     * @param int $userId
     * @return PurchasingTransport
     * @throws RuntimeException
     */
    public function update(string $code, int $companyId, array $data, int $userId): PurchasingTransport
    {
        $this->db->startTransaction();
        try {
            $transport = $this->lockTransport($code, $companyId);
            $this->assertDraft($transport, 'cập nhật chuyến xe');
            if (array_key_exists('destination_factory_id', $data)) {
                $this->assertFactory((int)$data['destination_factory_id'], $companyId);
            }
            $update = $this->filterHeader($data);
            if (array_key_exists('vehicle_id', $update)) {
                $vehicle = $this->assertVehicle((int)$update['vehicle_id'], $companyId);
                $update['vehicle_license_plate'] = $vehicle['license_plate'] ?? null;
                $this->assertNoVehicleTankLinesForDifferentVehicle((int)$transport['purchase_transport_id'], (int)$update['vehicle_id']);
            }
            if (!empty($update)) {
                $update['updated_at'] = $this->now();
                $update['updated_by'] = $userId;
                $this->updateOrFail('eudr_purchasing_transports', 'purchase_transport_id', (int)$transport['purchase_transport_id'], $update);
            }
            $this->db->commit();
            return $this->findById((int)$transport['purchase_transport_id'], $companyId);
        } catch (\Throwable $exception) {
            $this->db->rollback();
            throw $exception;
        }
    }

    /**
     * @param string $code
     * @param int $companyId
     * @param array $data
     * @param int $userId
     * @return PurchasingTransport
     * @throws RuntimeException
     */
    public function addLine(string $code, int $companyId, array $data, int $userId): PurchasingTransport
    {
        $this->db->startTransaction();
        try {
            $transport = $this->lockTransport($code, $companyId);
            $this->assertDraft($transport, 'thêm dòng chuyến xe');
            $line = $this->validateLine($transport, $data, $companyId);
            $this->assertNoDuplicateSourceLine((int)$transport['purchase_transport_id'], (int)$line['sub_tank_id']);
            $this->insertOrFail('eudr_purchasing_transport_sub_tanks', array_merge($line, [
                'purchase_transport_id' => $transport['purchase_transport_id'],
                'created_at' => $this->now(),
                'created_by' => $userId,
            ]));
            $this->db->commit();
            return $this->findById((int)$transport['purchase_transport_id'], $companyId);
        } catch (\Throwable $exception) {
            $this->db->rollback();
            throw $exception;
        }
    }

    /**
     * @param string $code
     * @param int $lineId
     * @param int $companyId
     * @param array $data
     * @param int $userId
     * @return PurchasingTransport
     * @throws RuntimeException
     */
    public function updateLine(string $code, int $lineId, int $companyId, array $data, int $userId): PurchasingTransport
    {
        $this->db->startTransaction();
        try {
            $transport = $this->lockTransport($code, $companyId);
            $this->assertDraft($transport, 'cập nhật dòng chuyến xe');
            $existing = $this->lockLine((int)$transport['purchase_transport_id'], $lineId);
            $line = $this->validateLine($transport, array_merge($existing, $data), $companyId, $lineId);
            $this->assertNoDuplicateSourceLine((int)$transport['purchase_transport_id'], (int)$line['sub_tank_id'], $lineId);
            $this->updateOrFail('eudr_purchasing_transport_sub_tanks', 'purchase_transport_sub_tank_id', $lineId, $line);
            $this->db->commit();
            return $this->findById((int)$transport['purchase_transport_id'], $companyId);
        } catch (\Throwable $exception) {
            $this->db->rollback();
            throw $exception;
        }
    }

    /**
     * @param string $code
     * @param int $lineId
     * @param int $companyId
     * @param int $userId
     * @return PurchasingTransport
     * @throws RuntimeException
     */
    public function deleteLine(string $code, int $lineId, int $companyId, int $userId): PurchasingTransport
    {
        $this->db->startTransaction();
        try {
            $transport = $this->lockTransport($code, $companyId);
            $this->assertDraft($transport, 'xóa dòng chuyến xe');
            $this->lockLine((int)$transport['purchase_transport_id'], $lineId);
            $this->db->where('purchase_transport_sub_tank_id', $lineId);
            if (!$this->db->delete('eudr_purchasing_transport_sub_tanks')) {
                throw new RuntimeException($this->db->getLastError());
            }
            $this->db->commit();
            return $this->findById((int)$transport['purchase_transport_id'], $companyId);
        } catch (\Throwable $exception) {
            $this->db->rollback();
            throw $exception;
        }
    }

    /**
     * @param string $code
     * @param int $companyId
     * @param array $data
     * @param int $userId
     * @return PurchasingTransport
     * @throws RuntimeException
     */
    public function dispatch(string $code, int $companyId, array $data, int $userId): PurchasingTransport
    {
        $this->db->startTransaction();
        try {
            $transport = $this->lockTransport($code, $companyId);
            if ($transport['status'] !== 'planned') {
                throw new RuntimeException('Chỉ chuyến xe ở trạng thái planned mới có thể dispatch');
            }
            $order = $this->lockOrder((int)$transport['purchase_order_id'], $companyId);
            if (!in_array($order['status'], ['transport_planned', 'in_transit'], true)) {
                throw new RuntimeException('Phiếu thu mua không sẵn sàng để dispatch');
            }
            $lines = $this->lockLines((int)$transport['purchase_transport_id']);
            if (empty($lines)) {
                throw new RuntimeException('Chuyến xe phải có ít nhất một dòng bình con trước khi dispatch');
            }
            $sourceTanks = $this->lockSourceTanks($lines, $this->sourceCompanyId($order));
            $vehicleTanks = $this->lockVehicleTanks($lines, (int)$transport['vehicle_id']);
            $departedAt = $data['departed_at'] ?? $this->now();
            $now = $this->now();
            foreach ($lines as $line) {
                $weight = (float)$line['loaded_weight_kg'];
                if ($weight <= 0) {
                    throw new RuntimeException('Khối lượng load phải lớn hơn 0');
                }
                $tank = $sourceTanks[(int)$line['sub_tank_id']] ?? null;
                if ($tank === null || (float)$tank['current_volume_kg'] + 0.0001 < $weight) {
                    throw new RuntimeException('Khối lượng tồn bình con không đủ để dispatch');
                }
                $before = (float)$tank['current_volume_kg'];
                $after = $before - $weight;
                $this->updateOrFail('eudr_purchasing_sub_tanks', 'sub_tank_id', (int)$tank['sub_tank_id'], [
                    'current_volume_kg' => $after,
                    'status' => $after <= 0.0001 ? 'idle' : 'in_use',
                    'updated_at' => $now,
                    'updated_by' => $userId,
                ]);
                $this->insertOrFail('eudr_purchasing_sub_tank_history', [
                    'sub_tank_id' => $tank['sub_tank_id'],
                    'purchase_order_id' => $transport['purchase_order_id'],
                    'entity_type' => 'transport',
                    'entity_id' => $transport['purchase_transport_id'],
                    'action_type' => 'output',
                    'rubber_type' => $tank['rubber_type'],
                    'qty_out_kg' => $weight,
                    'weight_kg' => $weight,
                    'volume_before_kg' => $before,
                    'volume_after_kg' => $after,
                    'event_time' => $departedAt,
                    'operator_user_id' => $userId,
                    'notes' => $line['notes'] ?? null,
                    'created_at' => $now,
                    'created_by' => $userId,
                ]);
                $this->updateOrFail('eudr_purchasing_transport_sub_tanks', 'purchase_transport_sub_tank_id', (int)$line['purchase_transport_sub_tank_id'], [
                    'loaded_at' => $departedAt,
                ]);
            }
            foreach ($vehicleTanks as $vehicleTank) {
                $this->updateOrFail('eudr_vehicle_tanks', 'vehicle_tank_id', (int)$vehicleTank['vehicle_tank_id'], [
                    'current_weight_kg' => $vehicleTank['after_weight_kg'],
                    'status' => 'in_transit',
                    'updated_at' => $now,
                    'updated_by' => $userId,
                ]);
            }
            $this->updateOrFail('eudr_purchasing_transports', 'purchase_transport_id', (int)$transport['purchase_transport_id'], [
                'status' => 'in_transit',
                'departed_at' => $departedAt,
                'seal_no' => $data['seal_no'] ?? $transport['seal_no'],
                'notes' => $data['notes'] ?? $transport['notes'],
                'updated_at' => $now,
                'updated_by' => $userId,
            ]);
            if ($order['status'] !== 'in_transit') {
                $this->setOrderStatus($order, 'in_transit', $userId, 'transport_dispatch', $data['notes'] ?? null);
            }
            $this->db->commit();
            return $this->findById((int)$transport['purchase_transport_id'], $companyId);
        } catch (\Throwable $exception) {
            $this->db->rollback();
            throw $exception;
        }
    }

    /**
     * @param string $code
     * @param int $companyId
     * @param array $data
     * @param int $userId
     * @return PurchasingTransport
     * @throws RuntimeException
     */
    public function arrive(string $code, int $companyId, array $data, int $userId): PurchasingTransport
    {
        $this->db->startTransaction();
        try {
            $transport = $this->lockTransport($code, $companyId);
            if ($transport['status'] !== 'in_transit') {
                throw new RuntimeException('Chỉ chuyến xe ở trạng thái in_transit mới có thể xác nhận đến nhà máy');
            }
            $order = $this->lockOrder((int)$transport['purchase_order_id'], $companyId);
            $arrivedAt = $data['arrived_at'] ?? $this->now();
            $now = $this->now();
            $this->updateOrFail('eudr_purchasing_transports', 'purchase_transport_id', (int)$transport['purchase_transport_id'], [
                'status' => 'arrived',
                'arrived_at' => $arrivedAt,
                'notes' => $data['notes'] ?? $transport['notes'],
                'updated_at' => $now,
                'updated_by' => $userId,
            ]);
            foreach (
                $this->lockVehicleTanksForArrival(
                    $this->lockLines((int)$transport['purchase_transport_id']),
                    (int)$transport['vehicle_id']
                ) as $vehicleTank
            ) {
                $this->updateOrFail('eudr_vehicle_tanks', 'vehicle_tank_id', (int)$vehicleTank['vehicle_tank_id'], [
                    'status' => 'unloading',
                    'updated_at' => $now,
                    'updated_by' => $userId,
                ]);
            }
            if ($this->countOpenTransports((int)$order['purchase_order_id']) === 0 && $order['status'] !== 'arrived_factory') {
                $this->setOrderStatus($order, 'arrived_factory', $userId, 'transport_arrive', $data['notes'] ?? null);
            }
            $this->db->commit();
            return $this->findById((int)$transport['purchase_transport_id'], $companyId);
        } catch (\Throwable $exception) {
            $this->db->rollback();
            throw $exception;
        }
    }

    /**
     * @param string $code
     * @param int $companyId
     * @param array $data
     * @param int $userId
     * @return PurchasingTransport
     * @throws RuntimeException
     */
    public function cancel(string $code, int $companyId, array $data, int $userId): PurchasingTransport
    {
        $this->db->startTransaction();
        try {
            $transport = $this->lockTransport($code, $companyId);
            $this->assertDraft($transport, 'hủy chuyến xe');
            $order = $this->lockOrder((int)$transport['purchase_order_id'], $companyId);
            $now = $this->now();
            $this->updateOrFail('eudr_purchasing_transports', 'purchase_transport_id', (int)$transport['purchase_transport_id'], [
                'status' => 'cancelled',
                'notes' => $data['notes'] ?? $transport['notes'],
                'updated_at' => $now,
                'updated_by' => $userId,
            ]);
            if ($this->countOpenTransports((int)$order['purchase_order_id']) === 0) {
                if (
                    $this->countArrivedTransports((int)$order['purchase_order_id']) > 0
                    && in_array($order['status'], ['transport_planned', 'in_transit'], true)
                ) {
                    $this->setOrderStatus($order, 'arrived_factory', $userId, 'transport_cancel', $data['notes'] ?? null);
                } elseif (
                    $this->countActiveTransports((int)$order['purchase_order_id']) === 0
                    && $order['status'] === 'transport_planned'
                ) {
                    $this->setOrderStatus($order, 'buyer_reconfirmed', $userId, 'transport_cancel', $data['notes'] ?? null);
                }
            }
            $this->db->commit();
            return $this->findById((int)$transport['purchase_transport_id'], $companyId);
        } catch (\Throwable $exception) {
            $this->db->rollback();
            throw $exception;
        }
    }

    /**
     * @param int $id
     * @param int $companyId
     * @return PurchasingTransport
     * @throws RuntimeException
     */
    private function findById(int $id, int $companyId): PurchasingTransport
    {
        $row = $this->queryOne(
            'SELECT t.*, po.purchase_order_code, po.status AS purchase_order_status,
                    f.factory_code, f.factory_name, v.vehicle_code, v.vehicle_name
             FROM eudr_purchasing_transports t
             INNER JOIN eudr_purchasing_orders po ON po.purchase_order_id = t.purchase_order_id AND po.deleted_by = 0
             INNER JOIN eudr_factories f ON f.factory_id = t.destination_factory_id AND f.deleted_by = 0
             LEFT JOIN eudr_transportation_vehicle v ON v.vehicle_id = t.vehicle_id AND v.deleted_by = 0
             WHERE t.purchase_transport_id = ? AND t.company_id = ? AND t.deleted_by = 0',
            [$id, $companyId]
        );
        if ($row === null) {
            throw new RuntimeException('Không tìm thấy chuyến xe thu mua');
        }
        return $this->entity($row, true);
    }

    /**
     * @param array $row
     * @param bool $withLines
     * @return PurchasingTransport
     */
    private function entity(array $row, bool $withLines): PurchasingTransport
    {
        $id = (int)$row['purchase_transport_id'];
        unset($row['purchase_transport_id']);
        if ($withLines) {
            $row['lines'] = $this->db->rawQuery(
                'SELECT l.*, s.sub_tank_code, s.sub_tank_name, s.rubber_type,
                        vt.vehicle_tank_code, vt.vehicle_tank_name
                 FROM eudr_purchasing_transport_sub_tanks l
                 INNER JOIN eudr_purchasing_sub_tanks s ON s.sub_tank_id = l.sub_tank_id AND s.deleted_by = 0
                 LEFT JOIN eudr_vehicle_tanks vt ON vt.vehicle_tank_id = l.vehicle_tank_id AND vt.deleted_by = 0
                 WHERE l.purchase_transport_id = ?
                 ORDER BY l.purchase_transport_sub_tank_id ASC',
                [$id]
            ) ?: [];
        }
        return new PurchasingTransport($id, $row);
    }

    /**
     * @param string $code
     * @param int $companyId
     * @return array
     * @throws RuntimeException
     */
    private function lockTransport(string $code, int $companyId): array
    {
        $transport = $this->queryOne(
            'SELECT * FROM eudr_purchasing_transports
             WHERE purchase_transport_code = ? AND company_id = ? AND deleted_by = 0
             FOR UPDATE',
            [$code, $companyId]
        );
        if ($transport === null) {
            throw new RuntimeException('Không tìm thấy chuyến xe thu mua');
        }
        return $transport;
    }

    /**
     * @param int $orderId
     * @param int $companyId
     * @return array
     * @throws RuntimeException
     */
    private function lockOrder(int $orderId, int $companyId): array
    {
        $order = $this->queryOne(
            'SELECT * FROM eudr_purchasing_orders
             WHERE purchase_order_id = ? AND company_id = ? AND deleted_by = 0
             FOR UPDATE',
            [$orderId, $companyId]
        );
        if ($order === null) {
            throw new RuntimeException('Không tìm thấy phiếu thu mua thuộc công ty hiện tại');
        }
        return $order;
    }

    /**
     * @param int $transportId
     * @param int $lineId
     * @return array
     * @throws RuntimeException
     */
    private function lockLine(int $transportId, int $lineId): array
    {
        $line = $this->queryOne(
            'SELECT * FROM eudr_purchasing_transport_sub_tanks
             WHERE purchase_transport_sub_tank_id = ? AND purchase_transport_id = ?
             FOR UPDATE',
            [$lineId, $transportId]
        );
        if ($line === null) {
            throw new RuntimeException('Không tìm thấy dòng bình con của chuyến xe');
        }
        return $line;
    }

    /**
     * @param int $transportId
     * @return array
     */
    private function lockLines(int $transportId): array
    {
        return $this->db->rawQuery(
            'SELECT * FROM eudr_purchasing_transport_sub_tanks
             WHERE purchase_transport_id = ?
             ORDER BY sub_tank_id ASC
             FOR UPDATE',
            [$transportId]
        ) ?: [];
    }

    /**
     * @param array $lines
     * @param int $sellerCompanyId
     * @return array
     * @throws RuntimeException
     */
    private function lockSourceTanks(array $lines, int $sellerCompanyId): array
    {
        $ids = array_values(array_unique(array_map(fn(array $line): int => (int)$line['sub_tank_id'], $lines)));
        sort($ids);
        $tanks = [];
        foreach ($ids as $id) {
            $tank = $this->queryOne(
                'SELECT * FROM eudr_purchasing_sub_tanks
                 WHERE sub_tank_id = ? AND company_id = ? AND deleted_by = 0
                 FOR UPDATE',
                [$id, $sellerCompanyId]
            );
            if ($tank === null || in_array($tank['status'], ['inactive', 'maintenance', 'damaged'], true)) {
                throw new RuntimeException('Bình con nguồn không sẵn sàng để dispatch');
            }
            $tanks[$id] = $tank;
        }
        return $tanks;
    }

    /**
     * @param array $lines
     * @param int $vehicleId
     * @return array
     * @throws RuntimeException
     */
    private function lockVehicleTanks(array $lines, int $vehicleId): array
    {
        $weights = [];
        foreach ($lines as $line) {
            if (!empty($line['vehicle_tank_id'])) {
                $id = (int)$line['vehicle_tank_id'];
                $weights[$id] = ($weights[$id] ?? 0.0) + (float)$line['loaded_weight_kg'];
            }
        }
        ksort($weights);
        $tanks = [];
        foreach ($weights as $id => $weight) {
            $tank = $this->queryOne(
                'SELECT * FROM eudr_vehicle_tanks
                 WHERE vehicle_tank_id = ? AND vehicle_id = ? AND deleted_by = 0
                 FOR UPDATE',
                [$id, $vehicleId]
            );
            if ($tank === null || $tank['status'] !== 'idle') {
                throw new RuntimeException('Bồn xe không sẵn sàng để vận chuyển');
            }
            $after = (float)$tank['current_weight_kg'] + $weight;
            if ($after > (float)$tank['capacity_kg'] + 0.0001) {
                throw new RuntimeException('Khối lượng load vượt quá sức chứa bồn xe');
            }
            $tank['after_weight_kg'] = $after;
            $tanks[$id] = $tank;
        }
        return $tanks;
    }

    /**
     * @param array $lines
     * @param int $vehicleId
     * @return array
     * @throws RuntimeException
     */
    private function lockVehicleTanksForArrival(array $lines, int $vehicleId): array
    {
        $ids = [];
        foreach ($lines as $line) {
            if (!empty($line['vehicle_tank_id'])) {
                $ids[(int)$line['vehicle_tank_id']] = true;
            }
        }
        $tanks = [];
        foreach (array_keys($ids) as $id) {
            $tank = $this->queryOne(
                'SELECT * FROM eudr_vehicle_tanks
                 WHERE vehicle_tank_id = ? AND vehicle_id = ? AND deleted_by = 0
                 FOR UPDATE',
                [$id, $vehicleId]
            );
            if ($tank === null || $tank['status'] !== 'in_transit') {
                throw new RuntimeException('Bồn xe không ở trạng thái sẵn sàng để dỡ hàng');
            }
            $tanks[$id] = $tank;
        }
        return $tanks;
    }

    /**
     * @param array $transport
     * @param array $data
     * @param int $companyId
     * @param int|null $exceptLineId
     * @return array
     * @throws RuntimeException
     */
    private function validateLine(
        array $transport,
        array $data,
        int $companyId,
        ?int $exceptLineId = null
    ): array {
        $subTankId = (int)($data['sub_tank_id'] ?? 0);
        $weight = (float)($data['loaded_weight_kg'] ?? 0);
        if ($subTankId <= 0 || $weight <= 0) {
            throw new RuntimeException('sub_tank_id và loaded_weight_kg phải hợp lệ');
        }
        $order = $this->lockOrder((int)$transport['purchase_order_id'], $companyId);
        $isFarmerOrder = ($order['seller_account_type'] ?? '') === 'farmer';
        $sourceCompanyId = $isFarmerOrder
            ? (int)$order['buyer_company_id']
            : $this->sourceCompanyId($order);
        $sourceTank = $this->queryOne(
            'SELECT sub_tank_id, rubber_type, status, current_volume_kg FROM eudr_purchasing_sub_tanks
             WHERE sub_tank_id = ? AND company_id = ? AND deleted_by = 0
             FOR UPDATE',
            [$subTankId, $sourceCompanyId]
        );
        if ($sourceTank === null || in_array($sourceTank['status'], ['inactive', 'maintenance', 'damaged'], true)) {
            throw new RuntimeException('Bình con nguồn không tồn tại hoặc không sẵn sàng');
        }
        $vehicleTankId = !empty($data['vehicle_tank_id']) ? (int)$data['vehicle_tank_id'] : null;
        if ($vehicleTankId !== null) {
            $vehicleTank = $this->queryOne(
                'SELECT vehicle_tank_id, tank_type, status FROM eudr_vehicle_tanks
                 WHERE vehicle_tank_id = ? AND vehicle_id = ? AND deleted_by = 0
                 FOR UPDATE',
                [$vehicleTankId, $transport['vehicle_id']]
            );
            if ($vehicleTank === null || $vehicleTank['status'] !== 'idle') {
                throw new RuntimeException('Bồn xe không thuộc chuyến xe hoặc không sẵn sàng');
            }
            if ($vehicleTank['tank_type'] !== 'mixed' && $sourceTank['rubber_type'] !== 'mixed' && $vehicleTank['tank_type'] !== $sourceTank['rubber_type']) {
                throw new RuntimeException('Loại mủ bình con không phù hợp với bồn xe');
            }
        }
        $sellerRefId = !empty($data['seller_sub_tank_ref_id']) ? (int)$data['seller_sub_tank_ref_id'] : null;
        $buyerRefId = !empty($data['buyer_sub_tank_ref_id']) ? (int)$data['buyer_sub_tank_ref_id'] : null;
        if ($buyerRefId === null) {
            throw new RuntimeException('Chuyến xe phải gắn bình buyer của phiếu thu mua');
        }
        $this->assertBuyerSubTankReference($buyerRefId, $transport, $companyId);
        if ($isFarmerOrder) {
            $this->assertFarmerBuyerSource($buyerRefId, $subTankId, $transport, $weight, $exceptLineId);
        } else {
            if ($sellerRefId === null) {
                throw new RuntimeException('Chuyến xe giữa các công ty phải gắn bình seller');
            }
            $this->assertSellerSubTankReference($sellerRefId, $transport, $subTankId, $sourceCompanyId);
            $plannedMappingWeight = $this->assertSubTankReferenceMapping($sellerRefId, $buyerRefId, $transport);
            $this->assertMappingAllocation($transport, $sellerRefId, $buyerRefId, $weight, $plannedMappingWeight, $exceptLineId);
        }
        $this->assertPendingSourceStockReservation(
            $subTankId,
            $sourceCompanyId,
            (float)$sourceTank['current_volume_kg'],
            $weight,
            $exceptLineId
        );
        return [
            'sub_tank_id' => $subTankId,
            'seller_sub_tank_ref_id' => $sellerRefId,
            'buyer_sub_tank_ref_id' => $buyerRefId,
            'vehicle_tank_id' => $vehicleTankId,
            'loaded_weight_kg' => $weight,
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function assertFarmerBuyerSource(
        int $buyerRefId,
        int $subTankId,
        array $transport,
        float $weight,
        ?int $exceptLineId
    ): void {
        $buyer = $this->queryOne(
            'SELECT sub_tank_id FROM eudr_purchasing_order_buyer_sub_tanks
             WHERE purchase_order_buyer_sub_tank_id = ? AND purchase_order_id = ? AND deleted_by = 0
             FOR UPDATE',
            [$buyerRefId, $transport['purchase_order_id']]
        );
        if ($buyer === null || (int)$buyer['sub_tank_id'] !== $subTankId) {
            throw new RuntimeException('Bình nguồn phải là bình buyer đã gắn cho phiếu Nông Hộ');
        }
        $sql = 'SELECT COALESCE(SUM(planned_receive_weight_kg), 0) AS planned_weight
                FROM eudr_purchasing_order_buyer_land_maps
                WHERE purchase_order_buyer_sub_tank_id = ? AND purchase_order_id = ?';
        $planned = $this->queryOne($sql, [$buyerRefId, $transport['purchase_order_id']]);
        $plannedWeight = (float)($planned['planned_weight'] ?? 0);
        $allocated = $this->queryOne(
            'SELECT COALESCE(SUM(loaded_weight_kg), 0) AS allocated_weight
             FROM eudr_purchasing_transport_sub_tanks l
             INNER JOIN eudr_purchasing_transports t ON t.purchase_transport_id = l.purchase_transport_id
             WHERE t.purchase_order_id = ? AND t.status <> \'cancelled\' AND l.buyer_sub_tank_ref_id = ?'
                . ($exceptLineId === null ? '' : ' AND l.purchase_transport_sub_tank_id <> ' . (int)$exceptLineId),
            [$transport['purchase_order_id'], $buyerRefId]
        );
        if ((float)($allocated['allocated_weight'] ?? 0) + $weight > $plannedWeight + 0.0001) {
            throw new RuntimeException('Khối lượng vận chuyển vượt quá tổng khối lượng mapping vườn đã duyệt');
        }
    }

    /**
     * @param int $subTankId
     * @param int $sellerCompanyId
     * @param float $currentVolume
     * @param float $requestedWeight
     * @param int|null $exceptLineId
     * @throws RuntimeException
     */
    private function assertPendingSourceStockReservation(
        int $subTankId,
        int $sellerCompanyId,
        float $currentVolume,
        float $requestedWeight,
        ?int $exceptLineId
    ): void {
        $sql = 'SELECT l.loaded_weight_kg
                FROM eudr_purchasing_transport_sub_tanks l
                INNER JOIN eudr_purchasing_transports t
                    ON t.purchase_transport_id = l.purchase_transport_id
                   AND t.deleted_by = 0
                   AND t.status IN (\'planned\', \'loading\')
                INNER JOIN eudr_purchasing_orders po
                    ON po.purchase_order_id = t.purchase_order_id
                   AND po.deleted_by = 0
                   AND (
                        (po.seller_account_type = \'farmer\' AND po.buyer_company_id = ?)
                        OR (po.seller_account_type <> \'farmer\' AND po.seller_company_id = ?)
                   )
                WHERE l.sub_tank_id = ?';
        $bindings = [$sellerCompanyId, $sellerCompanyId, $subTankId];
        if ($exceptLineId !== null) {
            $sql .= ' AND l.purchase_transport_sub_tank_id <> ?';
            $bindings[] = $exceptLineId;
        }
        $sql .= ' FOR UPDATE';
        $rows = $this->db->rawQuery($sql, $bindings) ?: [];
        $reservedWeight = array_sum(array_map(
            static fn(array $row): float => (float)$row['loaded_weight_kg'],
            $rows
        ));
        if ($reservedWeight + $requestedWeight > $currentVolume + 0.0001) {
            throw new RuntimeException('Tổng khối lượng các chuyến xe đang chờ vượt quá tồn kho bình con nguồn');
        }
    }

    /**
     * @param int $transportId
     * @param int $subTankId
     * @param int|null $exceptLineId
     * @throws RuntimeException
     */
    private function assertNoDuplicateSourceLine(int $transportId, int $subTankId, ?int $exceptLineId = null): void
    {
        $sql = 'SELECT purchase_transport_sub_tank_id
                FROM eudr_purchasing_transport_sub_tanks
                WHERE purchase_transport_id = ? AND sub_tank_id = ?';
        $bindings = [$transportId, $subTankId];
        if ($exceptLineId !== null) {
            $sql .= ' AND purchase_transport_sub_tank_id <> ?';
            $bindings[] = $exceptLineId;
        }
        $sql .= ' FOR UPDATE';
        if ($this->queryOne($sql, $bindings) !== null) {
            throw new RuntimeException('Bình con nguồn đã tồn tại trong chuyến xe');
        }
    }

    /**
     * @param array $order
     * @return int
     * @throws RuntimeException
     */
    private function sourceCompanyId(array $order): int
    {
        if (($order['seller_source_type'] ?? '') !== 'system_user') {
            throw new RuntimeException('Chuyến xe bình nguồn hiện chỉ hỗ trợ bên bán là người dùng thuộc công ty; farmer/vendor dùng quy trình tiếp nhận trực tiếp');
        }
        $sellerAccountType = (string)($order['seller_account_type'] ?? '');
        if ($sellerAccountType === 'farmer') {
            $buyerCompanyId = (int)($order['buyer_company_id'] ?? 0);
            if ($buyerCompanyId <= 0) {
                throw new RuntimeException('Phiếu thu mua thiếu công ty bên mua để xác định bình nguồn');
            }
            return $buyerCompanyId;
        }
        $sellerCompanyId = (int)($order['seller_company_id'] ?? 0);
        if (!in_array($sellerAccountType, ['farmer', 'purchaser', 'trader', 'company'], true)) {
            throw new RuntimeException('Chuyến xe bình nguồn hiện chỉ hỗ trợ bên bán là farmer, purchaser, trader hoặc company');
        }
        if ($sellerCompanyId <= 0) {
            throw new RuntimeException('Phiếu thu mua thiếu công ty bên bán để xác định bình nguồn');
        }
        $buyerCompanyId = (int)($order['buyer_company_id'] ?? 0);
        if ($buyerCompanyId <= 0) {
            throw new RuntimeException('Phiếu thu mua thiếu công ty bên mua để xác định phạm vi vận chuyển');
        }
        if ($sellerCompanyId === $buyerCompanyId) {
            throw new RuntimeException('Công ty bên bán và bên mua phải khác nhau để xác định bình nguồn');
        }
        return $sellerCompanyId;
    }

    /**
     * @param int $referenceId
     * @param array $transport
     * @param int $subTankId
     * @param int $sellerCompanyId
     * @throws RuntimeException
     */
    private function assertSellerSubTankReference(
        int $referenceId,
        array $transport,
        int $subTankId,
        int $sellerCompanyId
    ): void {
        $reference = $this->queryOne(
            'SELECT purchase_order_seller_sub_tank_id
             FROM eudr_purchasing_order_seller_sub_tanks
             WHERE purchase_order_seller_sub_tank_id = ? AND purchase_order_id = ?
               AND sub_tank_id = ? AND seller_company_id = ? AND deleted_by = 0
             FOR UPDATE',
            [$referenceId, $transport['purchase_order_id'], $subTankId, $sellerCompanyId]
        );
        if ($reference === null) {
            throw new RuntimeException('Bình con seller không thuộc phiếu thu mua hoặc không khớp bình nguồn');
        }
    }

    /**
     * @param int $referenceId
     * @param array $transport
     * @param int $buyerCompanyId
     * @throws RuntimeException
     */
    private function assertBuyerSubTankReference(int $referenceId, array $transport, int $buyerCompanyId): void
    {
        $reference = $this->queryOne(
            'SELECT purchase_order_buyer_sub_tank_id
             FROM eudr_purchasing_order_buyer_sub_tanks
             WHERE purchase_order_buyer_sub_tank_id = ? AND purchase_order_id = ?
               AND buyer_company_id = ? AND deleted_by = 0
             FOR UPDATE',
            [$referenceId, $transport['purchase_order_id'], $buyerCompanyId]
        );
        if ($reference === null) {
            throw new RuntimeException('Bình con buyer không thuộc phiếu thu mua');
        }
    }

    /**
     * @param int $sellerRefId
     * @param int $buyerRefId
     * @param array $transport
     * @return float
     * @throws RuntimeException
     */
    private function assertSubTankReferenceMapping(int $sellerRefId, int $buyerRefId, array $transport): float
    {
        $mapping = $this->queryOne(
            'SELECT purchase_order_buyer_seller_sub_tank_map_id, planned_transfer_weight_kg
             FROM eudr_purchasing_order_buyer_seller_sub_tank_maps
             WHERE purchase_order_id = ?
               AND purchase_order_seller_sub_tank_id = ?
               AND purchase_order_buyer_sub_tank_id = ?
               AND deleted_by = 0
             FOR UPDATE',
            [$transport['purchase_order_id'], $sellerRefId, $buyerRefId]
        );
        if ($mapping === null) {
            throw new RuntimeException('Cặp bình seller và buyer chưa được mapping trên phiếu thu mua');
        }
        return (float)$mapping['planned_transfer_weight_kg'];
    }

    /**
     * @param array $transport
     * @param int $sellerRefId
     * @param int $buyerRefId
     * @param float $weight
     * @param float $plannedWeight
     * @param int|null $exceptLineId
     * @throws RuntimeException
     */
    private function assertMappingAllocation(
        array $transport,
        int $sellerRefId,
        int $buyerRefId,
        float $weight,
        float $plannedWeight,
        ?int $exceptLineId
    ): void {
        $sql = 'SELECT COALESCE(SUM(l.loaded_weight_kg), 0) AS allocated_weight_kg
                FROM eudr_purchasing_transports t
                INNER JOIN eudr_purchasing_transport_sub_tanks l
                    ON l.purchase_transport_id = t.purchase_transport_id
                WHERE t.purchase_order_id = ?
                  AND t.deleted_by = 0
                  AND t.status <> \'cancelled\'
                  AND l.seller_sub_tank_ref_id = ?
                  AND l.buyer_sub_tank_ref_id = ?';
        $bindings = [$transport['purchase_order_id'], $sellerRefId, $buyerRefId];
        if ($exceptLineId !== null) {
            $sql .= ' AND l.purchase_transport_sub_tank_id <> ?';
            $bindings[] = $exceptLineId;
        }
        $allocated = $this->queryOne($sql, $bindings);
        $allocatedWeight = (float)($allocated['allocated_weight_kg'] ?? 0);
        if ($allocatedWeight + $weight > $plannedWeight + 0.0001) {
            throw new RuntimeException('Tổng khối lượng các chuyến xe vượt quá khối lượng mapping đã duyệt');
        }
    }

    /**
     * @param int $factoryId
     * @param int $companyId
     * @throws RuntimeException
     */
    private function assertFactory(int $factoryId, int $companyId): void
    {
        $factory = $this->queryOne(
            'SELECT factory_id FROM eudr_factories
             WHERE factory_id = ? AND company_id = ? AND status = ? AND deleted_by = 0
             FOR UPDATE',
            [$factoryId, $companyId, 'active']
        );
        if ($factory === null) {
            throw new RuntimeException('Nhà máy đích không tồn tại hoặc không hoạt động');
        }
    }

    /**
     * @param int $vehicleId
     * @param int $companyId
     * @return array
     * @throws RuntimeException
     */
    private function assertVehicle(int $vehicleId, int $companyId): array
    {
        $vehicle = $this->queryOne(
            'SELECT vehicle_id, license_plate FROM eudr_transportation_vehicle
             WHERE vehicle_id = ? AND company_id = ? AND deleted_by = 0
             FOR UPDATE',
            [$vehicleId, $companyId]
        );
        if ($vehicle === null) {
            throw new RuntimeException('Xe vận chuyển không tồn tại hoặc không thuộc công ty hiện tại');
        }
        return $vehicle;
    }

    /**
     * @param int $transportId
     * @param int $vehicleId
     * @throws RuntimeException
     */
    private function assertNoVehicleTankLinesForDifferentVehicle(int $transportId, int $vehicleId): void
    {
        $invalid = $this->queryOne(
            'SELECT l.purchase_transport_sub_tank_id
             FROM eudr_purchasing_transport_sub_tanks l
             INNER JOIN eudr_vehicle_tanks vt ON vt.vehicle_tank_id = l.vehicle_tank_id
             WHERE l.purchase_transport_id = ? AND l.vehicle_tank_id IS NOT NULL AND vt.vehicle_id <> ?
             LIMIT 1
             FOR UPDATE',
            [$transportId, $vehicleId]
        );
        if ($invalid !== null) {
            throw new RuntimeException('Không thể đổi xe khi các dòng đã chọn bồn xe của xe khác');
        }
    }

    /**
     * @param array $transport
     * @param string $operation
     * @throws RuntimeException
     */
    private function assertDraft(array $transport, string $operation): void
    {
        if ($transport['status'] !== 'planned') {
            throw new RuntimeException('Chỉ chuyến xe ở trạng thái planned mới có thể ' . $operation);
        }
    }

    /**
     * @param array $order
     * @param string $toStatus
     * @param int $userId
     * @param string $action
     * @param string|null $notes
     * @throws RuntimeException
     */
    private function setOrderStatus(array $order, string $toStatus, int $userId, string $action, ?string $notes): void
    {
        $now = $this->now();
        $this->db->where('purchase_order_id', (int)$order['purchase_order_id']);
        $this->db->where('status', $order['status']);
        if (!$this->db->update('eudr_purchasing_orders', [
            'status' => $toStatus,
            'updated_at' => $now,
            'updated_by' => $userId,
        ]) || $this->db->count !== 1) {
            throw new RuntimeException('Không thể cập nhật trạng thái phiếu thu mua');
        }
        $this->insertOrFail('eudr_purchasing_order_status_logs', [
            'purchase_order_id' => $order['purchase_order_id'],
            'from_status' => $order['status'],
            'to_status' => $toStatus,
            'actor_user_id' => $userId,
            'actor_role' => 'buyer',
            'action_name' => $action,
            'notes' => $notes,
            'created_at' => $now,
        ]);
    }

    /**
     * @param int $orderId
     * @return int
     */
    private function countOpenTransports(int $orderId): int
    {
        $row = $this->queryOne(
            "SELECT COUNT(*) AS total FROM eudr_purchasing_transports
             WHERE purchase_order_id = ? AND deleted_by = 0 AND status IN ('planned', 'loading', 'in_transit')",
            [$orderId]
        );
        return (int)($row['total'] ?? 0);
    }

    /**
     * @param int $orderId
     * @return int
     */
    private function countActiveTransports(int $orderId): int
    {
        $row = $this->queryOne(
            "SELECT COUNT(*) AS total FROM eudr_purchasing_transports
             WHERE purchase_order_id = ? AND deleted_by = 0 AND status <> 'cancelled'",
            [$orderId]
        );
        return (int)($row['total'] ?? 0);
    }

    /**
     * @param int $orderId
     * @return int
     */
    private function countArrivedTransports(int $orderId): int
    {
        $row = $this->queryOne(
            "SELECT COUNT(*) AS total FROM eudr_purchasing_transports
             WHERE purchase_order_id = ? AND deleted_by = 0 AND status IN ('arrived', 'closed')",
            [$orderId]
        );
        return (int)($row['total'] ?? 0);
    }

    /**
     * @param array $data
     * @return array
     */
    private function filterHeader(array $data): array
    {
        $fields = [
            'source_location',
            'destination_factory_id',
            'vehicle_id',
            'driver_user_id',
            'driver_name',
            'driver_phone',
            'seal_no',
            'notes',
        ];
        return array_intersect_key($data, array_flip($fields));
    }

    /**
     * @return string
     */
    private function generateCode(): string
    {
        do {
            $code = 'ptrn-' . date('ymd') . '-' . Utils::generateRandomString(8);
            $existing = $this->queryOne(
                'SELECT purchase_transport_id FROM eudr_purchasing_transports WHERE purchase_transport_code = ?',
                [$code]
            );
        } while ($existing !== null);
        return $code;
    }

    /**
     * @param string $table
     * @param array $data
     * @return int
     * @throws RuntimeException
     */
    private function insertOrFail(string $table, array $data): int
    {
        $id = $this->db->insert($table, $data);
        if ($id === false || $this->db->getLastErrno() !== 0) {
            throw new RuntimeException($this->db->getLastError() ?: 'Không thể tạo dữ liệu');
        }
        return (int)$id;
    }

    /**
     * @param string $table
     * @param string $idColumn
     * @param int $id
     * @param array $data
     * @throws RuntimeException
     */
    private function updateOrFail(string $table, string $idColumn, int $id, array $data): void
    {
        $this->db->where($idColumn, $id);
        if (!$this->db->update($table, $data) || $this->db->getLastErrno() !== 0) {
            throw new RuntimeException($this->db->getLastError() ?: 'Không thể cập nhật dữ liệu');
        }
    }

    /**
     * @param string $sql
     * @param array $bindings
     * @return array|null
     */
    private function queryOne(string $sql, array $bindings): ?array
    {
        $rows = $this->db->rawQuery($sql, $bindings);
        return empty($rows) ? null : $rows[0];
    }

    /**
     * @return string
     */
    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
