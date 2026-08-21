<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PurchasingSubTankIntake;

use App\Domain\PurchasingSubTankIntake\PurchasingSubTankIntake;
use App\Domain\PurchasingSubTankIntake\PurchasingSubTankIntakeRepository;
use RuntimeException;

final class InDatabasePurchasingSubTankIntakeRepository implements PurchasingSubTankIntakeRepository
{
    /**
     * @param \MysqliDb $db
     */
    public function __construct(private \MysqliDb $db) {}

    /**
     * @param array $data
     * @return PurchasingSubTankIntake
     * @throws RuntimeException
     */
    public function create(array $data): PurchasingSubTankIntake
    {
        $this->db->startTransaction();
        try {
            $tank = $this->queryOne(
                'SELECT sub_tank_id, rubber_type, capacity_kg, current_volume_kg, status
                 FROM eudr_purchasing_sub_tanks
                 WHERE sub_tank_id = ? AND company_id = ? AND deleted_by = 0
                 FOR UPDATE',
                [$data['sub_tank_id'], $data['company_id']]
            );
            if (empty($tank)) {
                throw new RuntimeException('Lỗi khối lượng tiếp nhận mủ vào bình con: không tìm thấy bình con');
            }

            $before = (float)$tank['current_volume_kg'];
            $weight = (float)$data['received_weight_kg'];
            $capacity = (float)$tank['capacity_kg'];
            if (in_array($tank['status'], ['inactive', 'maintenance'], true)) {
                throw new RuntimeException('Lỗi khối lượng tiếp nhận mủ vào bình con: bình con không sẵn sàng để tiếp nhận');
            }
            if ($tank['rubber_type'] !== 'mixed' && $tank['rubber_type'] !== $data['rubber_type']) {
                throw new RuntimeException('Lỗi khối lượng tiếp nhận mủ vào bình con: loại mủ không phù hợp với loại mủ hiện có trong bình con');
            }
            if ($weight <= 0 || $before + $weight > $capacity) {
                throw new RuntimeException('Lỗi khối lượng tiếp nhận mủ vào bình con: khối lượng phải lớn hơn 0 và không vượt quá dung tích bình con');
            }

            $purchaseOrder = null;
            if (!empty($data['purchase_order_id'])) {
                $purchaseOrder = $this->queryOne(
                    'SELECT seller_account_type, seller_source_type
                     FROM eudr_purchasing_orders
                     WHERE purchase_order_id = ?
                              AND buyer_company_id = ?
                       AND deleted_by = 0
                     FOR UPDATE',
                    [$data['purchase_order_id'], $data['company_id']]
                );
                if (empty($purchaseOrder)) {
                    throw new RuntimeException('Phiếu thu mua không thuộc công ty tiếp nhận');
                }
                if ($purchaseOrder['seller_source_type'] !== $data['seller_source_type']) {
                    throw new RuntimeException('Nguồn bên bán không khớp phiếu thu mua');
                }
                if ($purchaseOrder['seller_account_type'] === 'farmer' && empty($data['land_allocations'])) {
                    throw new RuntimeException('Phiếu Nông Hộ bắt buộc khai báo land_allocations khi tiếp nhận');
                }
                if ($purchaseOrder['seller_account_type'] === 'farmer' && !empty($data['mapping_allocations'])) {
                    throw new RuntimeException('Phiếu Nông Hộ không hỗ trợ mapping_allocations');
                }
                if (
                    in_array($purchaseOrder['seller_account_type'], ['purchaser', 'trader', 'company'], true)
                    && !empty($data['land_allocations'])
                ) {
                    throw new RuntimeException('Phiếu công ty/đơn vị thu mua không hỗ trợ land_allocations');
                }
                $arrivedTransport = $this->queryOne(
                    'SELECT t.purchase_transport_id
                     FROM eudr_purchasing_transports t
                     INNER JOIN eudr_purchasing_transport_sub_tanks l
                        ON l.purchase_transport_id = t.purchase_transport_id
                       AND l.loaded_weight_kg > 0
                       AND l.loaded_at IS NOT NULL
                     WHERE t.purchase_order_id = ?
                       AND t.company_id = ?
                       AND t.deleted_by = 0
                       AND t.status IN (\'arrived\', \'closed\')
                     ORDER BY CASE WHEN t.status = \'arrived\' THEN 0 ELSE 1 END,
                              t.arrived_at DESC, t.purchase_transport_id DESC
                     LIMIT 1',
                    [$data['purchase_order_id'], $data['company_id']]
                );
                if ($arrivedTransport === null) {
                    throw new RuntimeException('Chưa thể tiếp nhận mủ: chuyến xe của phiếu thu mua chưa đến nhà máy');
                }
            }
            if (
                empty($data['purchase_order_id'])
                && (!empty($data['land_allocations']) || !empty($data['mapping_allocations']))
            ) {
                throw new RuntimeException('purchase_order_id là bắt buộc khi có allocation');
            }
            $landMappings = [];
            $seenLandMapIds = [];
            foreach ((array)($data['land_allocations'] ?? []) as $allocation) {
                $landMapId = (int)($allocation['purchase_order_buyer_land_map_id'] ?? 0);
                $allocationWeight = (float)($allocation['received_weight_kg'] ?? 0);
                if ($landMapId <= 0 || $allocationWeight <= 0) {
                    throw new RuntimeException('Mỗi land allocation cần mapping hợp lệ và khối lượng lớn hơn 0');
                }
                if (isset($seenLandMapIds[$landMapId])) {
                    throw new RuntimeException('Không được phân bổ trùng mapping vườn trong cùng một intake');
                }
                $seenLandMapIds[$landMapId] = true;
                $landMapping = $this->queryOne(
                    'SELECT map.purchase_order_buyer_land_map_id, map.purchase_order_id,
                            map.purchase_order_land_id, map.purchase_order_item_id,
                            map.planned_receive_weight_kg, map.actual_receive_weight_kg,
                            buyer.purchase_order_buyer_sub_tank_id
                     FROM eudr_purchasing_order_buyer_land_maps map
                     INNER JOIN eudr_purchasing_order_buyer_sub_tanks buyer
                        ON buyer.purchase_order_buyer_sub_tank_id = map.purchase_order_buyer_sub_tank_id
                       AND buyer.purchase_order_id = map.purchase_order_id
                       AND buyer.deleted_by = 0
                            INNER JOIN eudr_purchasing_orders purchase_order
                                ON purchase_order.purchase_order_id = map.purchase_order_id
                              AND purchase_order.buyer_company_id = ?
                              AND purchase_order.seller_account_type = \'farmer\'
                              AND purchase_order.deleted_by = 0
                            INNER JOIN eudr_purchasing_order_items item
                                ON item.purchase_order_item_id = map.purchase_order_item_id
                              AND item.purchase_order_id = map.purchase_order_id
                              AND item.rubber_type = ?
                              AND item.deleted_by = 0
                     WHERE map.purchase_order_buyer_land_map_id = ?
                       AND map.purchase_order_id = ?
                       AND buyer.sub_tank_id = ?
                       AND map.deleted_by = 0
                     FOR UPDATE',
                    [
                        $data['company_id'],
                        $data['rubber_type'],
                        $landMapId,
                        $data['purchase_order_id'],
                        $data['sub_tank_id'],
                    ]
                );
                if (empty($landMapping)) {
                    throw new RuntimeException('Mapping vườn không thuộc phiếu hoặc bình con tiếp nhận');
                }
                if ((float)$landMapping['actual_receive_weight_kg'] + $allocationWeight - (float)$landMapping['planned_receive_weight_kg'] > 0.001) {
                    throw new RuntimeException('Khối lượng nhập vượt quá khối lượng còn lại của mapping vườn');
                }
                $landMapping['allocation_weight_kg'] = $allocationWeight;
                $landMappings[] = $landMapping;
            }
            $sellerBuyerMappings = [];
            $seenSellerBuyerMapIds = [];
            foreach ((array)($data['mapping_allocations'] ?? []) as $allocation) {
                $sellerBuyerMapId = (int)($allocation['purchase_order_buyer_seller_sub_tank_map_id'] ?? 0);
                $allocationWeight = (float)($allocation['received_weight_kg'] ?? 0);
                if ($sellerBuyerMapId <= 0 || $allocationWeight <= 0) {
                    throw new RuntimeException('Mỗi mapping allocation cần mapping hợp lệ và khối lượng lớn hơn 0');
                }
                if (isset($seenSellerBuyerMapIds[$sellerBuyerMapId])) {
                    throw new RuntimeException('Không được phân bổ trùng mapping seller-buyer trong cùng một intake');
                }
                $seenSellerBuyerMapIds[$sellerBuyerMapId] = true;
                $mapping = $this->queryOne(
                    'SELECT map.purchase_order_buyer_seller_sub_tank_map_id, map.purchase_order_id,
                            map.purchase_order_buyer_sub_tank_id, map.purchase_order_seller_sub_tank_id,
                            map.planned_transfer_weight_kg, map.actual_transfer_weight_kg
                     FROM eudr_purchasing_order_buyer_seller_sub_tank_maps map
                     INNER JOIN eudr_purchasing_order_buyer_sub_tanks buyer
                        ON buyer.purchase_order_buyer_sub_tank_id = map.purchase_order_buyer_sub_tank_id
                       AND buyer.purchase_order_id = map.purchase_order_id
                       AND buyer.sub_tank_id = ?
                       AND buyer.deleted_by = 0
                     INNER JOIN eudr_purchasing_orders purchase_order
                        ON purchase_order.purchase_order_id = map.purchase_order_id
                                             AND purchase_order.buyer_company_id = ?
                       AND purchase_order.seller_account_type IN (\'purchaser\', \'trader\', \'company\')
                       AND purchase_order.deleted_by = 0
                     WHERE map.purchase_order_buyer_seller_sub_tank_map_id = ?
                       AND map.purchase_order_id = ?
                       AND map.deleted_by = 0
                     FOR UPDATE',
                    [
                        $data['sub_tank_id'],
                        $data['company_id'],
                        $sellerBuyerMapId,
                        $data['purchase_order_id'],
                    ]
                );
                if (empty($mapping)) {
                    throw new RuntimeException('Mapping seller-buyer không thuộc phiếu hoặc bình con tiếp nhận');
                }
                if ((float)$mapping['actual_transfer_weight_kg'] + $allocationWeight - (float)$mapping['planned_transfer_weight_kg'] > 0.001) {
                    throw new RuntimeException('Khối lượng nhập vượt quá khối lượng còn lại của mapping seller-buyer');
                }
                $mapping['allocation_weight_kg'] = $allocationWeight;
                $sellerBuyerMappings[] = $mapping;
            }
            $allocationTotal = array_sum(array_map(
                static fn(array $allocation): float => (float)$allocation['received_weight_kg'],
                array_merge((array)($data['land_allocations'] ?? []), (array)($data['mapping_allocations'] ?? []))
            ));
            if (($purchaseOrder !== null) && abs($allocationTotal - $weight) > 0.001) {
                throw new RuntimeException('Tổng allocation phải bằng received_weight_kg');
            }
            if (
                $purchaseOrder !== null
                && in_array($purchaseOrder['seller_account_type'], ['purchaser', 'trader', 'company'], true)
                && empty($sellerBuyerMappings)
            ) {
                throw new RuntimeException('Phiếu công ty/đơn vị thu mua bắt buộc có mapping seller-buyer khi tiếp nhận');
            }

            $sequence = $this->queryOne(
                'SELECT COALESCE(MAX(intake_no), 0) + 1 AS next_no
                 FROM eudr_purchasing_sub_tank_intakes
                 WHERE sub_tank_id = ? AND deleted_by = 0',
                [$tank['sub_tank_id']]
            );
            $next = (int)($sequence['next_no'] ?? 1);
            $now = date('Y-m-d H:i:s');
            $after = $before + $weight;
            $intakeData = $this->filterIntakeData($data);
            $intakeData = array_merge($intakeData, [
                'intake_no' => $next,
                'volume_before_kg' => $before,
                'volume_after_kg' => $after,
                'received_by' => $data['purchaser_user_id'],
                'created_at' => $now,
                'created_by' => $data['purchaser_user_id'],
            ]);
            if (!$this->db->insert('eudr_purchasing_sub_tank_intakes', $intakeData)) {
                throw new RuntimeException($this->db->getLastError());
            }
            $id = (int)$this->db->getInsertId();
            $this->db->where('sub_tank_id', (int)$tank['sub_tank_id']);
            if (!$this->db->update('eudr_purchasing_sub_tanks', [
                'current_volume_kg' => $after,
                'status' => $after >= $capacity ? 'full' : 'in_use',
                'updated_at' => $now,
                'updated_by' => $data['purchaser_user_id'],
            ])) {
                throw new RuntimeException($this->db->getLastError());
            }
            if (!$this->db->insert('eudr_purchasing_sub_tank_history', [
                'sub_tank_id' => $tank['sub_tank_id'],
                'entity_type' => 'intake',
                'entity_id' => $id,
                'action_type' => 'input',
                'rubber_type' => $data['rubber_type'],
                'weight_kg' => $weight,
                'ph_value' => $data['ph_value'] ?? null,
                'nh3_percent' => $data['nh3_percent'] ?? null,
                'impurity_percent' => $data['impurity_percent'] ?? null,
                'volume_before_kg' => $before,
                'volume_after_kg' => $after,
                'event_time' => $data['received_at'],
                'notes' => $data['notes'] ?? null,
                'created_at' => $now,
                'created_by' => $data['purchaser_user_id'],
            ])) {
                throw new RuntimeException($this->db->getLastError());
            }
            foreach ($landMappings as $landMapping) {
                $allocationWeight = (float)$landMapping['allocation_weight_kg'];
                if (!$this->db->insert('eudr_purchasing_sub_tank_intake_land_allocations', [
                    'sub_tank_intake_id' => $id,
                    'purchase_order_id' => $landMapping['purchase_order_id'],
                    'purchase_order_item_id' => $landMapping['purchase_order_item_id'],
                    'purchase_order_land_id' => $landMapping['purchase_order_land_id'],
                    'purchase_order_buyer_land_map_id' => $landMapping['purchase_order_buyer_land_map_id'],
                    'received_weight_kg' => $allocationWeight,
                    'created_at' => $now,
                    'created_by' => $data['purchaser_user_id'],
                ])) {
                    throw new RuntimeException($this->db->getLastError());
                }
                $mappingActual = (float)$landMapping['actual_receive_weight_kg'] + $allocationWeight;
                $this->db->where('purchase_order_buyer_land_map_id', (int)$landMapping['purchase_order_buyer_land_map_id']);
                if (!$this->db->update('eudr_purchasing_order_buyer_land_maps', [
                    'actual_receive_weight_kg' => $mappingActual,
                    'received_at' => $data['received_at'],
                    'confirmed_by' => $data['purchaser_user_id'],
                    'updated_at' => $now,
                    'updated_by' => $data['purchaser_user_id'],
                ])) {
                    throw new RuntimeException($this->db->getLastError());
                }
            }
            foreach ($sellerBuyerMappings as $mapping) {
                $allocationWeight = (float)$mapping['allocation_weight_kg'];
                if (!$this->db->insert('eudr_purchasing_sub_tank_intake_mapping_allocations', [
                    'sub_tank_intake_id' => $id,
                    'purchase_order_id' => $mapping['purchase_order_id'],
                    'purchase_order_buyer_seller_sub_tank_map_id' => $mapping['purchase_order_buyer_seller_sub_tank_map_id'],
                    'purchase_order_buyer_sub_tank_id' => $mapping['purchase_order_buyer_sub_tank_id'],
                    'purchase_order_seller_sub_tank_id' => $mapping['purchase_order_seller_sub_tank_id'],
                    'received_weight_kg' => $allocationWeight,
                    'created_at' => $now,
                    'created_by' => $data['purchaser_user_id'],
                ])) {
                    throw new RuntimeException($this->db->getLastError());
                }
                $mappingActual = (float)$mapping['actual_transfer_weight_kg'] + $allocationWeight;
                $this->db->where(
                    'purchase_order_buyer_seller_sub_tank_map_id',
                    (int)$mapping['purchase_order_buyer_seller_sub_tank_map_id']
                );
                if (!$this->db->update('eudr_purchasing_order_buyer_seller_sub_tank_maps', [
                    'actual_transfer_weight_kg' => $mappingActual,
                    'transferred_at' => $data['received_at'],
                    'confirmed_by' => $data['purchaser_user_id'],
                    'updated_at' => $now,
                    'updated_by' => $data['purchaser_user_id'],
                ])) {
                    throw new RuntimeException($this->db->getLastError());
                }
            }
            $buyerIds = array_values(array_unique(array_merge(
                array_map(
                    static fn(array $mapping): int => (int)$mapping['purchase_order_buyer_sub_tank_id'],
                    $landMappings
                ),
                array_map(
                    static fn(array $mapping): int => (int)$mapping['purchase_order_buyer_sub_tank_id'],
                    $sellerBuyerMappings
                )
            )));
            foreach ($buyerIds as $buyerId) {
                $buyerActual = $purchaseOrder['seller_account_type'] === 'farmer'
                    ? $this->queryOne(
                        'SELECT COALESCE(SUM(actual_receive_weight_kg), 0) AS actual_weight
                         FROM eudr_purchasing_order_buyer_land_maps
                         WHERE purchase_order_buyer_sub_tank_id = ? AND deleted_by = 0',
                        [$buyerId]
                    )
                    : $this->queryOne(
                        'SELECT COALESCE(SUM(actual_transfer_weight_kg), 0) AS actual_weight
                         FROM eudr_purchasing_order_buyer_seller_sub_tank_maps
                         WHERE purchase_order_buyer_sub_tank_id = ? AND deleted_by = 0',
                        [$buyerId]
                    );
                $buyerPlan = $this->queryOne(
                    'SELECT planned_receive_weight_kg FROM eudr_purchasing_order_buyer_sub_tanks
                     WHERE purchase_order_buyer_sub_tank_id = ? AND deleted_by = 0',
                    [$buyerId]
                );
                $this->db->where('purchase_order_buyer_sub_tank_id', $buyerId);
                if (!$this->db->update('eudr_purchasing_order_buyer_sub_tanks', [
                    'actual_receive_weight_kg' => (float)($buyerActual['actual_weight'] ?? 0),
                    'received_at' => $data['received_at'],
                    'status' => (float)($buyerActual['actual_weight'] ?? 0) + 0.001
                        >= (float)($buyerPlan['planned_receive_weight_kg'] ?? 0)
                        ? 'received'
                        : 'receiving',
                    'updated_at' => $now,
                    'updated_by' => $data['purchaser_user_id'],
                ])) {
                    throw new RuntimeException($this->db->getLastError());
                }
            }
            $this->db->commit();
            $created = $this->findById($id, (int)$data['company_id']);
            if ($created === null) {
                throw new RuntimeException('Lỗi khi tạo nhật ký tiếp nhận mủ vào bình con');
            }
            return $created;
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * @param int $id
     * @param int $companyId
     * @return PurchasingSubTankIntake|null
     */
    public function findById(int $id, int $companyId): ?PurchasingSubTankIntake
    {
        $row = $this->queryOne(
            'SELECT i.*, t.sub_tank_code, t.sub_tank_name
             FROM eudr_purchasing_sub_tank_intakes i
             INNER JOIN eudr_purchasing_sub_tanks t ON t.sub_tank_id = i.sub_tank_id
             WHERE i.sub_tank_intake_id = ? AND i.company_id = ? AND i.deleted_by = 0',
            [$id, $companyId]
        );
        if (empty($row)) {
            return null;
        }
        $row['land_allocations'] = $this->getLandAllocations([(int)$row['sub_tank_intake_id']])[(int)$row['sub_tank_intake_id']] ?? [];
        $row['mapping_allocations'] = $this->getMappingAllocations([(int)$row['sub_tank_intake_id']])[(int)$row['sub_tank_intake_id']] ?? [];
        return $this->entity($row);
    }

    /**
     * @param array $params
     * @param int $companyId
     * @return array
     */
    public function findAll(array $params, int $companyId): array
    {
        $page = max(1, (int)($params['page'] ?? 1));
        $limit = min(100, max(1, (int)($params['page_limit'] ?? $params['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $where = ['i.company_id = ?', 'i.deleted_by = 0'];
        $bindings = [$companyId];
        $filters = [
            'sub_tank_id' => 'i.sub_tank_id',
            'purchase_order_id' => 'i.purchase_order_id',
            'vendor_id' => 'i.vendor_id',
            'farmer_user_id' => 'i.farmer_user_id',
        ];
        foreach ($filters as $key => $column) {
            if (isset($params[$key]) && $params[$key] !== '') {
                $where[] = $column . ' = ?';
                $bindings[] = (int)$params[$key];
            }
        }
        if (!empty($params['rubber_type']) && $params['rubber_type'] !== 'all') {
            $where[] = 'i.rubber_type = ?';
            $bindings[] = $params['rubber_type'];
        }
        if (!empty($params['date_from'])) {
            $where[] = 'i.received_at >= ?';
            $bindings[] = $params['date_from'] . ' 00:00:00';
        }
        if (!empty($params['date_to'])) {
            $where[] = 'i.received_at <= ?';
            $bindings[] = $params['date_to'] . ' 23:59:59';
        }
        $whereSql = implode(' AND ', $where);
        $count = $this->queryOne('SELECT COUNT(*) AS total FROM eudr_purchasing_sub_tank_intakes i WHERE ' . $whereSql, $bindings);
        $rows = $this->db->rawQuery(
            'SELECT i.*, t.sub_tank_code, t.sub_tank_name
             FROM eudr_purchasing_sub_tank_intakes i
             INNER JOIN eudr_purchasing_sub_tanks t ON t.sub_tank_id = i.sub_tank_id
             WHERE ' . $whereSql . '
             ORDER BY i.received_at DESC, i.sub_tank_intake_id DESC
             LIMIT ?, ?',
            array_merge($bindings, [$offset, $limit])
        );
        $total = (int)($count['total'] ?? 0);
        $allocationMap = $this->getLandAllocations(array_map(
            static fn(array $row): int => (int)$row['sub_tank_intake_id'],
            $rows ?: []
        ));
        $mappingAllocationMap = $this->getMappingAllocations(array_map(
            static fn(array $row): int => (int)$row['sub_tank_intake_id'],
            $rows ?: []
        ));
        return [
            'current_page' => $page,
            'page_limit' => $limit,
            'total_records' => $total,
            'total_pages' => (int)ceil($total / $limit),
            'records' => array_map(function (array $row) use ($allocationMap, $mappingAllocationMap): PurchasingSubTankIntake {
                $row['land_allocations'] = $allocationMap[(int)$row['sub_tank_intake_id']] ?? [];
                $row['mapping_allocations'] = $mappingAllocationMap[(int)$row['sub_tank_intake_id']] ?? [];
                return $this->entity($row);
            }, $rows ?: []),
        ];
    }

    private function getLandAllocations(array $intakeIds): array
    {
        if (empty($intakeIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($intakeIds), '?'));
        $rows = $this->db->rawQuery(
            'SELECT allocation.sub_tank_intake_id,
                    allocation.sub_tank_intake_land_allocation_id,
                    allocation.purchase_order_buyer_land_map_id,
                    allocation.purchase_order_land_id,
                    allocation.purchase_order_item_id,
                    allocation.received_weight_kg,
                    land.land_code, land.land_name, land.farmer_name
             FROM eudr_purchasing_sub_tank_intake_land_allocations allocation
             LEFT JOIN eudr_purchasing_order_lands land
                ON land.purchase_order_land_id = allocation.purchase_order_land_id
             WHERE allocation.sub_tank_intake_id IN (' . $placeholders . ')
               AND allocation.deleted_by = 0
             ORDER BY allocation.sub_tank_intake_id, allocation.sub_tank_intake_land_allocation_id',
            $intakeIds
        );
        $result = [];
        foreach ((array)$rows as $row) {
            $intakeId = (int)$row['sub_tank_intake_id'];
            unset($row['sub_tank_intake_id']);
            $result[$intakeId][] = $row;
        }
        return $result;
    }

    private function getMappingAllocations(array $intakeIds): array
    {
        if (empty($intakeIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($intakeIds), '?'));
        $rows = $this->db->rawQuery(
            'SELECT allocation.sub_tank_intake_id,
                    allocation.sub_tank_intake_mapping_allocation_id,
                    allocation.purchase_order_buyer_seller_sub_tank_map_id,
                    allocation.purchase_order_buyer_sub_tank_id,
                    allocation.purchase_order_seller_sub_tank_id,
                    allocation.received_weight_kg
             FROM eudr_purchasing_sub_tank_intake_mapping_allocations allocation
             WHERE allocation.sub_tank_intake_id IN (' . $placeholders . ')
             ORDER BY allocation.sub_tank_intake_id, allocation.sub_tank_intake_mapping_allocation_id',
            $intakeIds
        );
        $result = [];
        foreach ((array)$rows as $row) {
            $intakeId = (int)$row['sub_tank_intake_id'];
            unset($row['sub_tank_intake_id']);
            $result[$intakeId][] = $row;
        }
        return $result;
    }

    private function entity(array $row): PurchasingSubTankIntake
    {
        $id = (int)$row['sub_tank_intake_id'];
        unset($row['sub_tank_intake_id']);
        return new PurchasingSubTankIntake($id, $row);
    }

    private function queryOne(string $sql, array $bindings): ?array
    {
        $rows = $this->db->rawQuery($sql, $bindings);
        return empty($rows) ? null : $rows[0];
    }

    private function filterIntakeData(array $data): array
    {
        $columns = [
            'sub_tank_id',
            'purchase_order_id',
            'company_id',
            'purchaser_user_id',
            'seller_source_type',
            'farmer_user_id',
            'vendor_id',
            'transaction_ticket_id',
            'transaction_ticket_code',
            'rubber_type',
            'received_weight_kg',
            'latex_color',
            'ph_value',
            'nh3_percent',
            'impurity_percent',
            'tsc_percent',
            'temperature_c',
            'received_at',
            'harvested_at',
            'notes',
        ];
        return array_intersect_key($data, array_flip($columns));
    }
}
