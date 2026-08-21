<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PurchasingFactoryReceipt;

use App\Application\Utility\Utils;
use App\Domain\PurchasingFactoryReceipt\PurchasingFactoryReceipt;
use App\Domain\PurchasingFactoryReceipt\PurchasingFactoryReceiptRepository;
use RuntimeException;

final class InDatabasePurchasingFactoryReceiptRepository implements PurchasingFactoryReceiptRepository
{
    private const EPSILON = 0.0001;

    /**
     * @param \MysqliDb $db
     */
    public function __construct(private \MysqliDb $db) {}

    /**
     * @param array<string, mixed> $data
     */
    public function createForTransport(string $transportCode, int $companyId, array $data, int $userId): PurchasingFactoryReceipt
    {
        $this->db->startTransaction();
        try {
            $transport = $this->lockTransportByCode($transportCode, $companyId);
            if ($transport['status'] !== 'arrived') {
                throw new RuntimeException('Chỉ chuyến xe đã đến nhà máy mới có thể lập phiếu nhập');
            }
            $order = $this->lockOrder((int)$transport['purchase_order_id'], $companyId);
            $this->assertNoOpenReceiptForTransport((int)$transport['purchase_transport_id']);
            $lines = $this->lockTransportLines((int)$transport['purchase_transport_id']);
            if (empty($lines)) {
                throw new RuntimeException('Chuyến xe không có dòng hàng để nhập nhà máy');
            }

            $items = $this->normalizeItems($data['items'] ?? null, $lines, $transport, $companyId);
            $now = $this->now();
            $receiptId = $this->insertOrFail('eudr_purchasing_factory_receipts', [
                'factory_receipt_code' => $this->generateCode(),
                'purchase_order_id' => $order['purchase_order_id'],
                'purchase_transport_id' => $transport['purchase_transport_id'],
                'company_id' => $companyId,
                'factory_id' => $transport['destination_factory_id'],
                'receipt_date' => $data['receipt_date'] ?? $now,
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'created_at' => $now,
                'created_by' => $userId,
            ]);
            foreach ($items as $item) {
                $this->insertOrFail('eudr_purchasing_factory_receipt_items', array_merge($item, [
                    'factory_receipt_id' => $receiptId,
                    'volume_before_kg' => 0,
                    'volume_after_kg' => 0,
                    'created_at' => $now,
                    'created_by' => $userId,
                ]));
            }

            $this->db->commit();
            return $this->findById($receiptId, $companyId);
        } catch (\Throwable $exception) {
            $this->db->rollback();
            throw $exception;
        }
    }

    /**
     * @param string $code
     * @param int $companyId
     * @return PurchasingFactoryReceipt|null
     */
    public function findByCode(string $code, int $companyId): ?PurchasingFactoryReceipt
    {
        $row = $this->queryOne(
            'SELECT r.*, po.purchase_order_code, po.status AS purchase_order_status,
                    t.purchase_transport_code, t.status AS purchase_transport_status,
                    f.factory_code, f.factory_name
             FROM eudr_purchasing_factory_receipts r
             INNER JOIN eudr_purchasing_orders po ON po.purchase_order_id = r.purchase_order_id AND po.deleted_by = 0
             INNER JOIN eudr_purchasing_transports t ON t.purchase_transport_id = r.purchase_transport_id AND t.deleted_by = 0
             INNER JOIN eudr_factories f ON f.factory_id = r.factory_id AND f.deleted_by = 0
             WHERE r.factory_receipt_code = ? AND r.company_id = ? AND r.deleted_by = 0',
            [$code, $companyId]
        );
        return $row === null ? null : $this->entity($row, true);
    }

    /**
     * @param array<string, mixed> $params
     * @param int $companyId
     * @param int $userId
     * @param string $scope
     * @return array<string, mixed>
     */
    public function findAll(array $params, int $companyId, int $userId, string $scope): array
    {
        $page = max(1, (int)($params['page'] ?? 1));
        $limit = min(100, max(1, (int)($params['page_limit'] ?? $params['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $where = ['r.company_id = ?', 'r.deleted_by = 0'];
        $bindings = [$companyId];
        if ($scope === 'self') {
            $where[] = 'r.created_by = ?';
            $bindings[] = $userId;
        }
        foreach (['purchase_order_id', 'purchase_transport_id', 'factory_id'] as $field) {
            if (isset($params[$field]) && $params[$field] !== '') {
                $where[] = 'r.' . $field . ' = ?';
                $bindings[] = (int)$params[$field];
            }
        }
        if (!empty($params['status']) && $params['status'] !== 'all') {
            $where[] = 'r.status = ?';
            $bindings[] = (string)$params['status'];
        }
        if (!empty($params['date_from'])) {
            $where[] = 'r.receipt_date >= ?';
            $bindings[] = (string)$params['date_from'] . ' 00:00:00';
        }
        if (!empty($params['date_to'])) {
            $where[] = 'r.receipt_date <= ?';
            $bindings[] = (string)$params['date_to'] . ' 23:59:59';
        }
        if (!empty($params['search'])) {
            $where[] = '(r.factory_receipt_code LIKE ? OR t.purchase_transport_code LIKE ? OR po.purchase_order_code LIKE ?)';
            $search = '%' . trim((string)$params['search']) . '%';
            array_push($bindings, $search, $search, $search);
        }
        $whereSql = implode(' AND ', $where);
        $count = $this->queryOne(
            'SELECT COUNT(*) AS total
             FROM eudr_purchasing_factory_receipts r
             INNER JOIN eudr_purchasing_orders po ON po.purchase_order_id = r.purchase_order_id AND po.deleted_by = 0
             INNER JOIN eudr_purchasing_transports t ON t.purchase_transport_id = r.purchase_transport_id AND t.deleted_by = 0
             WHERE ' . $whereSql,
            $bindings
        );
        $rows = $this->db->rawQuery(
            'SELECT r.*, po.purchase_order_code, po.status AS purchase_order_status,
                    t.purchase_transport_code, t.status AS purchase_transport_status,
                    f.factory_code, f.factory_name,
                    COUNT(i.factory_receipt_item_id) AS item_count,
                    COALESCE(SUM(i.received_weight_kg), 0) AS received_weight_total_kg,
                    COALESCE(SUM(i.accepted_weight_kg), 0) AS accepted_weight_total_kg,
                    COALESCE(SUM(i.rejected_weight_kg), 0) AS rejected_weight_total_kg
             FROM eudr_purchasing_factory_receipts r
             INNER JOIN eudr_purchasing_orders po ON po.purchase_order_id = r.purchase_order_id AND po.deleted_by = 0
             INNER JOIN eudr_purchasing_transports t ON t.purchase_transport_id = r.purchase_transport_id AND t.deleted_by = 0
             INNER JOIN eudr_factories f ON f.factory_id = r.factory_id AND f.deleted_by = 0
             LEFT JOIN eudr_purchasing_factory_receipt_items i ON i.factory_receipt_id = r.factory_receipt_id
             WHERE ' . $whereSql . '
             GROUP BY r.factory_receipt_id
             ORDER BY r.receipt_date DESC, r.factory_receipt_id DESC
             LIMIT ?, ?',
            array_merge($bindings, [$offset, $limit])
        ) ?: [];
        $total = (int)($count['total'] ?? 0);
        return [
            'current_page' => $page,
            'page_limit' => $limit,
            'total_records' => $total,
            'total_pages' => (int)ceil($total / $limit),
            'records' => array_map(fn(array $row): PurchasingFactoryReceipt => $this->entity($row, false), $rows),
        ];
    }

    /**
     * @param string $code
     * @param int $companyId
     * @param int $userId
     * @return PurchasingFactoryReceipt
     */
    public function post(string $code, int $companyId, int $userId): PurchasingFactoryReceipt
    {
        $this->db->startTransaction();
        try {
            $receipt = $this->lockReceiptByCode($code, $companyId);
            if ($receipt['status'] === 'posted') {
                $this->db->commit();
                return $this->findById((int)$receipt['factory_receipt_id'], $companyId);
            }
            if ($receipt['status'] !== 'draft') {
                throw new RuntimeException('Chỉ phiếu nhập ở trạng thái draft mới có thể post');
            }

            $transport = $this->lockTransportById((int)$receipt['purchase_transport_id'], $companyId);
            if ($transport['status'] !== 'arrived') {
                throw new RuntimeException('Chuyến xe phải ở trạng thái arrived trước khi post phiếu nhập');
            }
            $order = $this->lockOrder((int)$receipt['purchase_order_id'], $companyId);
            $lines = $this->lockTransportLines((int)$transport['purchase_transport_id']);
            $items = $this->lockReceiptItems((int)$receipt['factory_receipt_id']);
            if (count($items) !== count($lines) || empty($items)) {
                throw new RuntimeException('Phiếu nhập phải chứa đúng một dòng cho mỗi dòng hàng của chuyến xe');
            }
            $itemByLineId = [];
            foreach ($items as $item) {
                $lineId = (int)$item['purchase_transport_sub_tank_id'];
                if ($lineId <= 0 || isset($itemByLineId[$lineId])) {
                    throw new RuntimeException('Dòng phiếu nhập bị trùng hoặc thiếu liên kết dòng chuyến xe');
                }
                $itemByLineId[$lineId] = $item;
            }
            $lineById = [];
            foreach ($lines as $line) {
                $lineId = (int)$line['purchase_transport_sub_tank_id'];
                if (!isset($itemByLineId[$lineId])) {
                    throw new RuntimeException('Phiếu nhập không bao phủ toàn bộ dòng hàng của chuyến xe');
                }
                $lineById[$lineId] = $line;
            }

            $rawTanks = $this->lockRawMaterialTanks($items, (int)$receipt['factory_id'], $companyId);
            $vehicleTanks = $this->lockVehicleTanks($lines, (int)$transport['vehicle_id']);
            $this->assertVehicleTankBalances($vehicleTanks, $lines);

            $now = $this->now();
            $receiptDate = (string)$receipt['receipt_date'];
            foreach ($items as $item) {
                $line = $lineById[(int)$item['purchase_transport_sub_tank_id']];
                $this->assertPostedItem($item, $line);
                $rawTankId = (int)$item['raw_material_tank_id'];
                $rawTank = $rawTanks[$rawTankId];
                $acceptedWeight = (float)$item['accepted_weight_kg'];
                $before = (float)$rawTank['current_volume'];
                $after = $before + $acceptedWeight;
                if ($after > (float)$rawTank['capacity'] + self::EPSILON) {
                    throw new RuntimeException('Khối lượng nhập vượt quá sức chứa bồn nguyên liệu thô');
                }
                $this->assertRawTankAcceptsRubber($rawTank, (string)$item['rubber_type']);
                $rawTanks[$rawTankId]['current_volume'] = $after;

                $this->updateOrFail('eudr_tanks_raw_material', 'raw_material_tank_id', $rawTankId, [
                    'current_volume' => $after,
                    'current_tsc' => $item['tsc_percent'] ?? $rawTank['current_tsc'],
                    'status' => $after >= (float)$rawTank['capacity'] - self::EPSILON ? 'full' : 'active',
                    'updated_at' => $now,
                    'updated_by' => $userId,
                ]);
                $this->insertOrFail('eudr_tanks_raw_material_history', [
                    'raw_material_tank_id' => $rawTankId,
                    'entity_type' => 'purchasing_factory_receipt',
                    'entity_id' => $receipt['factory_receipt_id'],
                    'action_type' => 'input',
                    'rubber_type' => $item['rubber_type'],
                    'weight' => $acceptedWeight,
                    'tsc' => $item['tsc_percent'] ?? null,
                    'volume_before' => $before,
                    'volume_after' => $after,
                    'notes' => $item['notes'] ?? $receipt['notes'],
                    'created_at' => $now,
                    'created_by' => $userId,
                ]);
                $this->updateOrFail('eudr_purchasing_factory_receipt_items', 'factory_receipt_item_id', (int)$item['factory_receipt_item_id'], [
                    'volume_before_kg' => $before,
                    'volume_after_kg' => $after,
                ]);

                $receivedWeight = (float)$item['received_weight_kg'];
                $loadedWeight = (float)$line['loaded_weight_kg'];
                $this->updateOrFail('eudr_purchasing_transport_sub_tanks', 'purchase_transport_sub_tank_id', (int)$line['purchase_transport_sub_tank_id'], [
                    'unloaded_weight_kg' => $receivedWeight,
                    'loss_weight_kg' => $loadedWeight - $receivedWeight,
                    'unloaded_at' => $receiptDate,
                ]);
            }

            foreach ($vehicleTanks as $vehicleTank) {
                $this->updateOrFail('eudr_vehicle_tanks', 'vehicle_tank_id', (int)$vehicleTank['vehicle_tank_id'], [
                    'current_weight_kg' => 0,
                    'status' => 'idle',
                    'updated_at' => $now,
                    'updated_by' => $userId,
                ]);
            }
            $this->updateOrFail('eudr_purchasing_factory_receipts', 'factory_receipt_id', (int)$receipt['factory_receipt_id'], [
                'status' => 'posted',
                'posted_at' => $now,
                'posted_by' => $userId,
                'updated_at' => $now,
                'updated_by' => $userId,
            ]);
            $this->updateOrFail('eudr_purchasing_transports', 'purchase_transport_id', (int)$transport['purchase_transport_id'], [
                'status' => 'closed',
                'updated_at' => $now,
                'updated_by' => $userId,
            ]);
            if ($this->countActiveTransports((int)$order['purchase_order_id']) === 0) {
                $this->closeOrder($order, $userId, $receipt['notes'] ?? null);
            }

            $this->db->commit();
            return $this->findById((int)$receipt['factory_receipt_id'], $companyId);
        } catch (\Throwable $exception) {
            $this->db->rollback();
            throw $exception;
        }
    }

    /**
     * @param string $code
     * @param int $companyId
     * @param string|null $notes
     * @param int $userId
     * @return PurchasingFactoryReceipt
     */
    public function cancel(string $code, int $companyId, ?string $notes, int $userId): PurchasingFactoryReceipt
    {
        $this->db->startTransaction();
        try {
            $receipt = $this->lockReceiptByCode($code, $companyId);
            if ($receipt['status'] === 'posted') {
                throw new RuntimeException('Không thể hủy phiếu nhập đã post');
            }
            if ($receipt['status'] === 'draft') {
                $this->updateOrFail('eudr_purchasing_factory_receipts', 'factory_receipt_id', (int)$receipt['factory_receipt_id'], [
                    'status' => 'cancelled',
                    'notes' => $notes ?? $receipt['notes'],
                    'updated_at' => $this->now(),
                    'updated_by' => $userId,
                ]);
            }
            $this->db->commit();
            return $this->findById((int)$receipt['factory_receipt_id'], $companyId);
        } catch (\Throwable $exception) {
            $this->db->rollback();
            throw $exception;
        }
    }

    /**
     * @param int $id
     * @param int $companyId
     * @return PurchasingFactoryReceipt
     */
    private function findById(int $id, int $companyId): PurchasingFactoryReceipt
    {
        $row = $this->queryOne(
            'SELECT r.*, po.purchase_order_code, po.status AS purchase_order_status,
                    t.purchase_transport_code, t.status AS purchase_transport_status,
                    f.factory_code, f.factory_name
             FROM eudr_purchasing_factory_receipts r
             INNER JOIN eudr_purchasing_orders po ON po.purchase_order_id = r.purchase_order_id AND po.deleted_by = 0
             INNER JOIN eudr_purchasing_transports t ON t.purchase_transport_id = r.purchase_transport_id AND t.deleted_by = 0
             INNER JOIN eudr_factories f ON f.factory_id = r.factory_id AND f.deleted_by = 0
             WHERE r.factory_receipt_id = ? AND r.company_id = ? AND r.deleted_by = 0',
            [$id, $companyId]
        );
        if ($row === null) {
            throw new RuntimeException('Không tìm thấy phiếu nhập nhà máy');
        }
        return $this->entity($row, true);
    }

    /**
     * @param array<string, mixed> $row
     * @param bool $withItems
     * @return PurchasingFactoryReceipt
     */
    private function entity(array $row, bool $withItems): PurchasingFactoryReceipt
    {
        $id = (int)$row['factory_receipt_id'];
        unset($row['factory_receipt_id']);
        if ($withItems) {
            $row['items'] = $this->db->rawQuery(
                'SELECT i.*, l.loaded_weight_kg, l.unloaded_weight_kg, l.loss_weight_kg,
                        s.sub_tank_code, s.sub_tank_name,
                        vt.vehicle_tank_code, vt.vehicle_tank_name,
                        rt.raw_material_tank_code, rt.raw_material_tank_name
                 FROM eudr_purchasing_factory_receipt_items i
                 INNER JOIN eudr_purchasing_transport_sub_tanks l
                     ON l.purchase_transport_sub_tank_id = i.purchase_transport_sub_tank_id
                 INNER JOIN eudr_purchasing_sub_tanks s ON s.sub_tank_id = l.sub_tank_id AND s.deleted_by = 0
                 LEFT JOIN eudr_vehicle_tanks vt ON vt.vehicle_tank_id = l.vehicle_tank_id AND vt.deleted_by = 0
                 INNER JOIN eudr_tanks_raw_material rt ON rt.raw_material_tank_id = i.raw_material_tank_id AND rt.deleted_by = 0
                 WHERE i.factory_receipt_id = ?
                 ORDER BY i.factory_receipt_item_id ASC',
                [$id]
            ) ?: [];
        }
        return new PurchasingFactoryReceipt($id, $row);
    }

    /**
     * @param string $code
     * @param int $companyId
     * @return array<string, mixed>
     * @throws RuntimeException if the receipt is not found
     */
    private function lockReceiptByCode(string $code, int $companyId): array
    {
        $receipt = $this->queryOne(
            'SELECT * FROM eudr_purchasing_factory_receipts
             WHERE factory_receipt_code = ? AND company_id = ? AND deleted_by = 0
             FOR UPDATE',
            [$code, $companyId]
        );
        if ($receipt === null) {
            throw new RuntimeException('Không tìm thấy phiếu nhập nhà máy');
        }
        return $receipt;
    }

    /**
     * @param string $code
     * @param int $companyId
     * @return array<string, mixed>
     * @throws RuntimeException if the receipt is not found
     */
    private function lockTransportByCode(string $code, int $companyId): array
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
     * @param int $id
     * @param int $companyId
     * @return array<string, mixed>
     * @throws RuntimeException if the transport is not found
     */
    private function lockTransportById(int $id, int $companyId): array
    {
        $transport = $this->queryOne(
            'SELECT * FROM eudr_purchasing_transports
             WHERE purchase_transport_id = ? AND company_id = ? AND deleted_by = 0
             FOR UPDATE',
            [$id, $companyId]
        );
        if ($transport === null) {
            throw new RuntimeException('Không tìm thấy chuyến xe thu mua');
        }
        return $transport;
    }

    /**
     * @param int $orderId
     * @param int $companyId
     * @return array<string, mixed>
     * @throws RuntimeException if the order is not found
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
     * @throws RuntimeException if there is an open receipt for the transport
     */
    private function assertNoOpenReceiptForTransport(int $transportId): void
    {
        $existing = $this->queryOne(
            "SELECT factory_receipt_id FROM eudr_purchasing_factory_receipts
             WHERE purchase_transport_id = ? AND deleted_by = 0 AND status IN ('draft', 'posted')
             FOR UPDATE",
            [$transportId]
        );
        if ($existing !== null) {
            throw new RuntimeException('Chuyến xe đã có phiếu nhập đang xử lý hoặc đã post');
        }
    }

    /**
     * @param int $transportId
     * @return array<int, array<string, mixed>>
     */
    private function lockTransportLines(int $transportId): array
    {
        return $this->db->rawQuery(
            'SELECT l.*, s.rubber_type AS source_rubber_type,
                    COALESCE(buyer.purchase_order_item_id, seller.purchase_order_item_id) AS linked_purchase_order_item_id
             FROM eudr_purchasing_transport_sub_tanks l
             INNER JOIN eudr_purchasing_sub_tanks s ON s.sub_tank_id = l.sub_tank_id AND s.deleted_by = 0
             LEFT JOIN eudr_purchasing_order_buyer_sub_tanks buyer
                 ON buyer.purchase_order_buyer_sub_tank_id = l.buyer_sub_tank_ref_id AND buyer.deleted_by = 0
             LEFT JOIN eudr_purchasing_order_seller_sub_tanks seller
                 ON seller.purchase_order_seller_sub_tank_id = l.seller_sub_tank_ref_id AND seller.deleted_by = 0
             WHERE l.purchase_transport_id = ?
             ORDER BY l.purchase_transport_sub_tank_id ASC
             FOR UPDATE',
            [$transportId]
        ) ?: [];
    }

    /**
     * @param int $receiptId
     * @return array<int, array<string, mixed>>
     */
    private function lockReceiptItems(int $receiptId): array
    {
        return $this->db->rawQuery(
            'SELECT * FROM eudr_purchasing_factory_receipt_items
             WHERE factory_receipt_id = ?
             ORDER BY purchase_transport_sub_tank_id ASC, factory_receipt_item_id ASC
             FOR UPDATE',
            [$receiptId]
        ) ?: [];
    }

    /**
     * @param mixed $itemsInput
     * @param array<int, array<string, mixed>> $lines
     * @param array<string, mixed> $transport
     * @param int $companyId
     * @return array<int, array<string, mixed>>
     */
    private function normalizeItems(mixed $itemsInput, array $lines, array $transport, int $companyId): array
    {
        if (!is_array($itemsInput) || empty($itemsInput)) {
            throw new RuntimeException('items phải là mảng không rỗng');
        }
        $lineById = [];
        foreach ($lines as $line) {
            $lineById[(int)$line['purchase_transport_sub_tank_id']] = $line;
        }
        if (count($itemsInput) !== count($lineById)) {
            throw new RuntimeException('items phải có đúng một dòng cho mỗi dòng bình con của chuyến xe');
        }
        $normalized = [];
        $seenLineIds = [];
        foreach ($itemsInput as $itemInput) {
            if (!is_array($itemInput)) {
                throw new RuntimeException('Mỗi item phiếu nhập phải là đối tượng');
            }
            $lineId = (int)($itemInput['purchase_transport_sub_tank_id'] ?? 0);
            $rawTankId = (int)($itemInput['raw_material_tank_id'] ?? 0);
            $line = $lineById[$lineId] ?? null;
            if ($line === null || $rawTankId <= 0 || isset($seenLineIds[$lineId])) {
                throw new RuntimeException('purchase_transport_sub_tank_id và raw_material_tank_id phải hợp lệ, không trùng');
            }
            $seenLineIds[$lineId] = true;
            $receivedWeight = $this->weight($itemInput, 'received_weight_kg');
            $acceptedWeight = array_key_exists('accepted_weight_kg', $itemInput)
                ? $this->weight($itemInput, 'accepted_weight_kg')
                : $receivedWeight;
            $rejectedWeight = array_key_exists('rejected_weight_kg', $itemInput)
                ? $this->weight($itemInput, 'rejected_weight_kg')
                : 0.0;
            if (abs($acceptedWeight + $rejectedWeight - $receivedWeight) > self::EPSILON) {
                throw new RuntimeException('accepted_weight_kg + rejected_weight_kg phải bằng received_weight_kg');
            }
            if ($receivedWeight > (float)$line['loaded_weight_kg'] + self::EPSILON) {
                throw new RuntimeException('received_weight_kg không được lớn hơn khối lượng đã load');
            }
            $rubberType = (string)($itemInput['rubber_type'] ?? $line['source_rubber_type']);
            if (
                !in_array($rubberType, ['latex', 'cup_lump', 'scrap_rubber', 'mixed'], true)
                || $rubberType !== (string)$line['source_rubber_type']
            ) {
                throw new RuntimeException('Loại mủ phiếu nhập phải khớp dòng bình con nguồn');
            }
            $linkedItemId = !empty($line['linked_purchase_order_item_id'])
                ? (int)$line['linked_purchase_order_item_id']
                : null;
            if (
                isset($itemInput['purchase_order_item_id'])
                && (int)$itemInput['purchase_order_item_id'] !== (int)$linkedItemId
            ) {
                throw new RuntimeException('purchase_order_item_id không khớp dòng chuyến xe');
            }
            $this->assertRawTankExists($rawTankId, (int)$transport['destination_factory_id'], $companyId);
            $normalized[] = [
                'purchase_transport_sub_tank_id' => $lineId,
                'purchase_order_item_id' => $linkedItemId,
                'sub_tank_id' => (int)$line['sub_tank_id'],
                'vehicle_tank_id' => !empty($line['vehicle_tank_id']) ? (int)$line['vehicle_tank_id'] : null,
                'raw_material_tank_id' => $rawTankId,
                'rubber_type' => $rubberType,
                'received_weight_kg' => $receivedWeight,
                'accepted_weight_kg' => $acceptedWeight,
                'rejected_weight_kg' => $rejectedWeight,
                'ph_value' => $this->nullableNumber($itemInput, 'ph_value'),
                'nh3_percent' => $this->nullableNumber($itemInput, 'nh3_percent'),
                'impurity_percent' => $this->nullableNumber($itemInput, 'impurity_percent'),
                'tsc_percent' => $this->nullableNumber($itemInput, 'tsc_percent'),
                'notes' => isset($itemInput['notes']) ? (string)$itemInput['notes'] : null,
            ];
        }
        ksort($seenLineIds);
        if (array_keys($seenLineIds) !== array_keys($lineById)) {
            throw new RuntimeException('items phải bao phủ toàn bộ dòng bình con của chuyến xe');
        }
        usort(
            $normalized,
            static fn(array $left, array $right): int =>
            $left['purchase_transport_sub_tank_id'] <=> $right['purchase_transport_sub_tank_id']
        );
        return $normalized;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param int $factoryId
     * @param int $companyId
     * @return array<int, array<string, mixed>>
     */
    private function lockRawMaterialTanks(array $items, int $factoryId, int $companyId): array
    {
        $ids = [];
        foreach ($items as $item) {
            $ids[(int)$item['raw_material_tank_id']] = true;
        }
        ksort($ids);
        $tanks = [];
        foreach (array_keys($ids) as $id) {
            $tank = $this->queryOne(
                'SELECT * FROM eudr_tanks_raw_material
                 WHERE raw_material_tank_id = ? AND factory_id = ? AND company_id = ? AND deleted_by = 0
                 FOR UPDATE',
                [$id, $factoryId, $companyId]
            );
            if ($tank === null || !in_array($tank['status'], ['active', 'full'], true)) {
                throw new RuntimeException('Bồn nguyên liệu thô không thuộc nhà máy đích hoặc không sẵn sàng');
            }
            $tanks[$id] = $tank;
        }
        return $tanks;
    }

    /**
     * @param array<int, array<string, mixed>> $lines
     * @param int $vehicleId
     * @return array<int, array<string, mixed>>
     */
    private function lockVehicleTanks(array $lines, int $vehicleId): array
    {
        $weights = [];
        foreach ($lines as $line) {
            if (!empty($line['vehicle_tank_id'])) {
                $vehicleTankId = (int)$line['vehicle_tank_id'];
                $weights[$vehicleTankId] = ($weights[$vehicleTankId] ?? 0.0) + (float)$line['loaded_weight_kg'];
            }
        }
        ksort($weights);
        $tanks = [];
        foreach ($weights as $id => $loadedWeight) {
            $tank = $this->queryOne(
                'SELECT * FROM eudr_vehicle_tanks
                 WHERE vehicle_tank_id = ? AND vehicle_id = ? AND deleted_by = 0
                 FOR UPDATE',
                [$id, $vehicleId]
            );
            if ($tank === null || $tank['status'] !== 'unloading') {
                throw new RuntimeException('Bồn xe không sẵn sàng để hoàn tất dỡ hàng');
            }
            $tank['loaded_weight_kg'] = $loadedWeight;
            $tanks[$id] = $tank;
        }
        return $tanks;
    }

    /**
     * @param array<int, array<string, mixed>> $vehicleTanks
     * @param array<int, array<string, mixed>> $lines
     */
    private function assertVehicleTankBalances(array $vehicleTanks, array $lines): void
    {
        foreach ($vehicleTanks as $vehicleTank) {
            if (abs((float)$vehicleTank['current_weight_kg'] - (float)$vehicleTank['loaded_weight_kg']) > self::EPSILON) {
                throw new RuntimeException('Khối lượng thực tế bồn xe không khớp tổng khối lượng đã load của chuyến xe');
            }
        }
        foreach ($lines as $line) {
            if ((float)$line['unloaded_weight_kg'] > self::EPSILON) {
                throw new RuntimeException('Dòng chuyến xe đã có khối lượng dỡ trước đó');
            }
        }
    }

    /**
     * @param array<string, mixed> $item
     * @param array<string, mixed> $line
     */
    private function assertPostedItem(array $item, array $line): void
    {
        $receivedWeight = (float)$item['received_weight_kg'];
        $acceptedWeight = (float)$item['accepted_weight_kg'];
        $rejectedWeight = (float)$item['rejected_weight_kg'];
        if (
            $receivedWeight < 0 || $acceptedWeight < 0 || $rejectedWeight < 0
            || $receivedWeight > (float)$line['loaded_weight_kg'] + self::EPSILON
            || abs($acceptedWeight + $rejectedWeight - $receivedWeight) > self::EPSILON
        ) {
            throw new RuntimeException('Khối lượng dòng phiếu nhập không hợp lệ');
        }
        if (
            (int)$item['sub_tank_id'] !== (int)$line['sub_tank_id']
            || (!empty($item['vehicle_tank_id']) && (int)$item['vehicle_tank_id'] !== (int)$line['vehicle_tank_id'])
            || (string)$item['rubber_type'] !== (string)$line['source_rubber_type']
        ) {
            throw new RuntimeException('Dòng phiếu nhập không còn khớp với dòng chuyến xe');
        }
    }

    /**
     * @param int $rawTankId
     * @param int $factoryId
     * @param int $companyId
     * @throws RuntimeException if the raw material tank does not exist or is not ready
     */
    private function assertRawTankExists(int $rawTankId, int $factoryId, int $companyId): void
    {
        $tank = $this->queryOne(
            'SELECT raw_material_tank_id, status FROM eudr_tanks_raw_material
             WHERE raw_material_tank_id = ? AND factory_id = ? AND company_id = ? AND deleted_by = 0
             FOR UPDATE',
            [$rawTankId, $factoryId, $companyId]
        );
        if ($tank === null || !in_array($tank['status'], ['active', 'full'], true)) {
            throw new RuntimeException('Bồn nguyên liệu thô không thuộc nhà máy đích hoặc không sẵn sàng');
        }
    }

    /**
     * @param array<string, mixed> $tank
     * @param string $rubberType
     * @throws RuntimeException if the rubber type is not compatible with the raw material tank
     */
    private function assertRawTankAcceptsRubber(array $tank, string $rubberType): void
    {
        if ($tank['tank_type'] !== 'mixed' && $tank['tank_type'] !== $rubberType) {
            throw new RuntimeException('Loại mủ không phù hợp với bồn nguyên liệu thô');
        }
    }

    /**
     * @param int $orderId
     * @return int
     */
    private function countActiveTransports(int $orderId): int
    {
        $row = $this->queryOne(
            "SELECT COUNT(*) AS total FROM eudr_purchasing_transports
             WHERE purchase_order_id = ? AND deleted_by = 0
               AND status IN ('planned', 'loading', 'in_transit', 'arrived')",
            [$orderId]
        );
        return (int)($row['total'] ?? 0);
    }

    /**
     * @param array<string, mixed> $order
     * @param int $userId
     * @param string|null $notes
     */
    private function closeOrder(array $order, int $userId, ?string $notes): void
    {
        if (in_array($order['status'], ['received_closed', 'cancelled'], true)) {
            return;
        }
        $now = $this->now();
        $this->db->where('purchase_order_id', (int)$order['purchase_order_id']);
        $this->db->where('status', $order['status']);
        if (!$this->db->update('eudr_purchasing_orders', [
            'status' => 'received_closed',
            'closed_at' => $now,
            'updated_at' => $now,
            'updated_by' => $userId,
        ]) || $this->db->count !== 1) {
            throw new RuntimeException('Không thể đóng phiếu thu mua');
        }
        $this->insertOrFail('eudr_purchasing_order_status_logs', [
            'purchase_order_id' => $order['purchase_order_id'],
            'from_status' => $order['status'],
            'to_status' => 'received_closed',
            'actor_user_id' => $userId,
            'actor_role' => 'buyer',
            'action_name' => 'factory_receipt_post',
            'notes' => $notes,
            'created_at' => $now,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     * @param string $field
     * @return float
     * @throws RuntimeException if the field is not a non-negative number
     */
    private function weight(array $data, string $field): float
    {
        $value = $data[$field] ?? null;
        if (!is_numeric($value) || (float)$value < 0) {
            throw new RuntimeException($field . ' phải là số không âm');
        }
        return (float)$value;
    }

    /**
     * @param array<string, mixed> $data
     * @param string $field
     * @return float|null
     * @throws RuntimeException if the field is not a non-negative number or null/empty
     */
    private function nullableNumber(array $data, string $field): ?float
    {
        if (!array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '') {
            return null;
        }
        if (!is_numeric($data[$field]) || (float)$data[$field] < 0) {
            throw new RuntimeException($field . ' phải là số không âm');
        }
        return (float)$data[$field];
    }

    /**
     * @return string
     */
    private function generateCode(): string
    {
        do {
            $code = 'pfrc-' . date('ymd') . '-' . Utils::generateRandomString(8);
        } while ($this->queryOne(
            'SELECT factory_receipt_id FROM eudr_purchasing_factory_receipts WHERE factory_receipt_code = ?',
            [$code]
        ) !== null);
        return $code;
    }

    /**
     * @param string $table
     * @param array<string, mixed> $data
     * @return int
     * @throws RuntimeException if the insert fails
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
     * @param array<string, mixed> $data
     * @throws RuntimeException if the update fails
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
     * @param array<mixed> $bindings
     * @return array<string, mixed>|null
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
