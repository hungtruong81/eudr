<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PurchasingOrder\BuyerSubTank;

trait PurchasingOrderBuyerSubTankRepositoryTrait
{
    public function createBuyerSubTankByOrderId(int $purchase_order_id, array $data): ?array
    {
        $order = $this->findOrderOfId($purchase_order_id);
        if (empty($order) || $order->getStatus() !== 'seller_confirmed') {
            return null;
        }
        $mappings = isset($data['mappings']) && is_array($data['mappings'])
            ? $data['mappings']
            : [];
        $landMappings = isset($data['land_mappings']) && is_array($data['land_mappings'])
            ? $data['land_mappings']
            : [];
        foreach ($mappings as $mapping) {
            $sellerSubTankId = (int)($mapping['purchase_order_seller_sub_tank_id'] ?? 0);
            if (empty($this->findSellerSubTankById($purchase_order_id, $sellerSubTankId))) {
                return null;
            }
        }
        unset($data['mappings'], $data['land_mappings']);
        $this->db->startTransaction();
        $this->db->insert('eudr_purchasing_order_buyer_sub_tanks', $data);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }
        $insertedId = (int)$this->db->getInsertId();
        if (
            !$this->syncBuyerSellerSubTankMappings(
                $purchase_order_id,
                $insertedId,
                $mappings,
                (int)($data['created_by'] ?? 0)
            )
            || !$this->syncBuyerLandMappings(
                $purchase_order_id,
                $insertedId,
                $landMappings,
                (int)($data['purchase_order_item_id'] ?? 0),
                (int)($data['created_by'] ?? 0)
            )
        ) {
            $this->db->rollback();
            return null;
        }
        $this->db->commit();
        return $this->findBuyerSubTankById($purchase_order_id, $insertedId);
    }

    public function updateBuyerSubTankByOrderId(
        int $purchase_order_id,
        int $purchase_order_buyer_sub_tank_id,
        array $data
    ): ?array {
        $order = $this->findOrderOfId($purchase_order_id);
        if (empty($order) || $order->getStatus() !== 'seller_confirmed') {
            return null;
        }
        $mappings = array_key_exists('mappings', $data) && is_array($data['mappings'])
            ? $data['mappings']
            : null;
        $landMappings = array_key_exists('land_mappings', $data) && is_array($data['land_mappings'])
            ? $data['land_mappings']
            : null;
        if ($mappings !== null) {
            foreach ($mappings as $mapping) {
                $sellerSubTankId = (int)($mapping['purchase_order_seller_sub_tank_id'] ?? 0);
                if (empty($this->findSellerSubTankById($purchase_order_id, $sellerSubTankId))) {
                    return null;
                }
            }
        }
        unset($data['mappings'], $data['land_mappings']);
        $this->db->startTransaction();
        if (($landMappings !== null || array_key_exists('sub_tank_id', $data)) && $this->buyerLandMappingsHaveIntakes(
            $purchase_order_id,
            $purchase_order_buyer_sub_tank_id
        )) {
            $this->db->rollback();
            return null;
        }
        $this->db->where('purchase_order_buyer_sub_tank_id', $purchase_order_buyer_sub_tank_id);
        $this->db->where('purchase_order_id', $purchase_order_id);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_purchasing_order_buyer_sub_tanks', $data);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }
        if (
            $mappings !== null && !$this->syncBuyerSellerSubTankMappings(
                $purchase_order_id,
                $purchase_order_buyer_sub_tank_id,
                $mappings,
                (int)($data['updated_by'] ?? 0)
            )
        ) {
            $this->db->rollback();
            return null;
        }
        if ($landMappings !== null) {
            $buyerSubTank = $this->findBuyerSubTankById(
                $purchase_order_id,
                $purchase_order_buyer_sub_tank_id
            );
            if (
                empty($buyerSubTank)
                || !$this->syncBuyerLandMappings(
                    $purchase_order_id,
                    $purchase_order_buyer_sub_tank_id,
                    $landMappings,
                    (int)($buyerSubTank['purchase_order_item_id'] ?? 0),
                    (int)($data['updated_by'] ?? 0)
                )
            ) {
                $this->db->rollback();
                return null;
            }
        }
        $this->db->commit();
        return $this->findBuyerSubTankById($purchase_order_id, $purchase_order_buyer_sub_tank_id);
    }

    public function deleteBuyerSubTankByOrderId(
        int $purchase_order_id,
        int $purchase_order_buyer_sub_tank_id,
        int $deleted_by
    ): bool {
        $order = $this->findOrderOfId($purchase_order_id);
        if (
            empty($order)
            || $order->getStatus() !== 'seller_confirmed'
            || empty($this->findBuyerSubTankById($purchase_order_id, $purchase_order_buyer_sub_tank_id))
        ) {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        $auditData = [
            'deleted_by' => $deleted_by,
            'deleted_at' => $now,
            'updated_by' => $deleted_by,
            'updated_at' => $now,
        ];
        $this->db->startTransaction();
        if ($this->buyerLandMappingsHaveIntakes($purchase_order_id, $purchase_order_buyer_sub_tank_id)) {
            $this->db->rollback();
            return false;
        }
        foreach (
            [
                'eudr_purchasing_order_buyer_seller_sub_tank_maps',
                'eudr_purchasing_order_buyer_land_maps',
                'eudr_purchasing_order_buyer_sub_tanks',
            ] as $table
        ) {
            $this->db->where('purchase_order_id', $purchase_order_id);
            $this->db->where('purchase_order_buyer_sub_tank_id', $purchase_order_buyer_sub_tank_id);
            $this->db->where('deleted_by', 0);
            $this->db->update($table, $auditData);
            if ($this->db->getLastErrno() !== 0) {
                $this->db->rollback();
                return false;
            }
        }
        $this->db->commit();
        return true;
    }

    public function listBuyerSubTanksByOrderId(int $purchase_order_id, array $params = []): array
    {
        $page = (int)($params['page'] ?? 1);
        $pageLimit = (int)($params['page_limit'] ?? 20);
        $status = (string)($params['status'] ?? 'all');
        $this->applyBuyerSubTankListFilters($purchase_order_id, $status);
        $totalRecords = (int)$this->db->getValue(
            'eudr_purchasing_order_buyer_sub_tanks pobt',
            'COUNT(*)'
        );
        $this->db->pageLimit = $pageLimit;
        $this->applyBuyerSubTankListFilters($purchase_order_id, $status);
        $this->db->join(
            'eudr_purchasing_sub_tanks pst',
            'pst.sub_tank_id = pobt.sub_tank_id',
            'LEFT'
        );
        $this->db->orderBy('pobt.purchase_order_buyer_sub_tank_id', 'DESC');
        $rows = $this->db->arraybuilder()->paginate(
            'eudr_purchasing_order_buyer_sub_tanks pobt',
            $page,
            'pobt.*, pst.sub_tank_code, pst.sub_tank_name'
        );
        $ids = array_map(
            static fn(array $row): int => (int)$row['purchase_order_buyer_sub_tank_id'],
            (array)$rows
        );
        $mappingMap = $this->getBuyerSellerSubTankMappingsByBuyerSubTankIds($purchase_order_id, $ids);
        $landMappingMap = $this->getBuyerLandMappingsByBuyerSubTankIds($purchase_order_id, $ids);
        $records = [];
        foreach ((array)$rows as $row) {
            $id = (int)$row['purchase_order_buyer_sub_tank_id'];
            $mapped = $mappingMap[$id] ?? [];
            $records[] = [
                'purchase_order_buyer_sub_tank_id' => $id,
                'purchase_order_id' => (int)$row['purchase_order_id'],
                'sub_tank_id' => (int)$row['sub_tank_id'],
                'sub_tank_code' => $row['sub_tank_code'] ?? null,
                'sub_tank_name' => $row['sub_tank_name'] ?? null,
                'buyer_company_id' => (int)($row['buyer_company_id'] ?? 0),
                'assigned_by' => (int)($row['assigned_by'] ?? 0),
                'mapped_seller_sub_tank_ids' => array_map(
                    static fn(array $item): int => (int)$item['purchase_order_seller_sub_tank_id'],
                    $mapped
                ),
                'mapped_seller_sub_tanks' => $mapped,
                'mapped_lands' => $landMappingMap[$id] ?? [],
                'purchase_order_item_id' => isset($row['purchase_order_item_id'])
                    ? (int)$row['purchase_order_item_id']
                    : null,
                'planned_receive_weight_kg' => (float)($row['planned_receive_weight_kg'] ?? 0),
                'actual_receive_weight_kg' => (float)($row['actual_receive_weight_kg'] ?? 0),
                'received_at' => $row['received_at'] ?? null,
                'status' => (string)($row['status'] ?? 'assigned'),
                'notes' => $row['notes'] ?? null,
                'created_at' => $row['created_at'] ?? null,
                'updated_at' => $row['updated_at'] ?? null,
            ];
        }

        return [
            'current_page' => $page,
            'total_pages' => (int)$this->db->totalPages,
            'total_records' => $totalRecords,
            'page_limit' => $pageLimit,
            'records' => $records,
        ];
    }

    private function applyBuyerSubTankListFilters(int $purchase_order_id, string $status): void
    {
        $this->db->where('pobt.purchase_order_id', $purchase_order_id);
        $this->db->where('pobt.deleted_by', 0);
        if ($status !== 'all') {
            $this->db->where('pobt.status', $status);
        }
    }

    private function findBuyerSubTankById(
        int $purchase_order_id,
        int $id
    ): ?array {
        $this->db->where('purchase_order_buyer_sub_tank_id', $id);
        $this->db->where('purchase_order_id', $purchase_order_id);
        $this->db->where('deleted_by', 0);
        $row = $this->db->getOne('eudr_purchasing_order_buyer_sub_tanks');
        if (empty($row)) {
            return null;
        }
        $mappingMap = $this->getBuyerSellerSubTankMappingsByBuyerSubTankIds(
            $purchase_order_id,
            [$id]
        );
        $mapped = $mappingMap[$id] ?? [];
        $landMappingMap = $this->getBuyerLandMappingsByBuyerSubTankIds(
            $purchase_order_id,
            [$id]
        );
        return [
            'purchase_order_buyer_sub_tank_id' => (int)$row['purchase_order_buyer_sub_tank_id'],
            'purchase_order_id' => (int)$row['purchase_order_id'],
            'sub_tank_id' => (int)$row['sub_tank_id'],
            'buyer_company_id' => (int)($row['buyer_company_id'] ?? 0),
            'assigned_by' => (int)($row['assigned_by'] ?? 0),
            'mapped_seller_sub_tank_ids' => array_map(
                static fn(array $item): int => (int)$item['purchase_order_seller_sub_tank_id'],
                $mapped
            ),
            'mapped_seller_sub_tanks' => $mapped,
            'mapped_lands' => $landMappingMap[$id] ?? [],
            'purchase_order_item_id' => isset($row['purchase_order_item_id'])
                ? (int)$row['purchase_order_item_id']
                : null,
            'planned_receive_weight_kg' => (float)($row['planned_receive_weight_kg'] ?? 0),
            'actual_receive_weight_kg' => (float)($row['actual_receive_weight_kg'] ?? 0),
            'received_at' => $row['received_at'] ?? null,
            'status' => (string)($row['status'] ?? 'assigned'),
            'notes' => $row['notes'] ?? null,
        ];
    }

    private function syncBuyerSellerSubTankMappings(
        int $purchase_order_id,
        int $buyerId,
        array $mappings,
        int $actorUserId
    ): bool {
        $now = date('Y-m-d H:i:s');
        $this->db->where('purchase_order_id', $purchase_order_id);
        $this->db->where('purchase_order_buyer_sub_tank_id', $buyerId);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_purchasing_order_buyer_seller_sub_tank_maps', [
            'deleted_by' => $actorUserId,
            'deleted_at' => $now,
            'updated_by' => $actorUserId,
            'updated_at' => $now,
        ]);
        if ($this->db->getLastErrno() !== 0) {
            return false;
        }
        foreach ($mappings as $mapping) {
            $this->db->insert('eudr_purchasing_order_buyer_seller_sub_tank_maps', [
                'purchase_order_id' => $purchase_order_id,
                'purchase_order_buyer_sub_tank_id' => $buyerId,
                'purchase_order_seller_sub_tank_id' => (int)$mapping['purchase_order_seller_sub_tank_id'],
                'planned_transfer_weight_kg' => (float)($mapping['planned_transfer_weight_kg'] ?? 0),
                'actual_transfer_weight_kg' => 0.0,
                'transferred_at' => null,
                'confirmed_by' => 0,
                'created_at' => $now,
                'created_by' => $actorUserId,
                'updated_at' => $now,
                'updated_by' => $actorUserId,
            ]);
            if ($this->db->getLastErrno() !== 0) {
                return false;
            }
        }
        return true;
    }

    private function getBuyerSellerSubTankMappingsByBuyerSubTankIds(
        int $purchase_order_id,
        array $ids
    ): array {
        if (empty($ids)) {
            return [];
        }

        $this->db->where('map.purchase_order_id', $purchase_order_id);
        $this->db->where('map.purchase_order_buyer_sub_tank_id', $ids, 'IN');
        $this->db->where('map.deleted_by', 0);
        $this->db->join(
            'eudr_purchasing_order_seller_sub_tanks post',
            'post.purchase_order_seller_sub_tank_id = map.purchase_order_seller_sub_tank_id AND post.deleted_by = 0',
            'LEFT'
        );
        $this->db->join(
            'eudr_purchasing_sub_tanks pst',
            'pst.sub_tank_id = post.sub_tank_id',
            'LEFT'
        );
        $this->db->orderBy('map.purchase_order_buyer_sub_tank_id', 'ASC');
        $this->db->orderBy('map.purchase_order_seller_sub_tank_id', 'ASC');
        $rows = $this->db->arraybuilder()->get(
            'eudr_purchasing_order_buyer_seller_sub_tank_maps map',
            null,
            'map.purchase_order_buyer_seller_sub_tank_map_id, map.purchase_order_buyer_sub_tank_id, '
                . 'map.purchase_order_seller_sub_tank_id, '
                . 'map.planned_transfer_weight_kg, map.actual_transfer_weight_kg, map.transferred_at, '
                . 'map.confirmed_by, post.sub_tank_id, pst.sub_tank_code, pst.sub_tank_name'
        );
        $result = [];
        foreach ((array)$rows as $row) {
            $id = (int)$row['purchase_order_buyer_sub_tank_id'];
            $result[$id][] = [
                'purchase_order_buyer_seller_sub_tank_map_id' => (int)$row['purchase_order_buyer_seller_sub_tank_map_id'],
                'purchase_order_seller_sub_tank_id' => (int)$row['purchase_order_seller_sub_tank_id'],
                'sub_tank_id' => isset($row['sub_tank_id']) ? (int)$row['sub_tank_id'] : null,
                'sub_tank_code' => $row['sub_tank_code'] ?? null,
                'sub_tank_name' => $row['sub_tank_name'] ?? null,
                'planned_transfer_weight_kg' => (float)($row['planned_transfer_weight_kg'] ?? 0),
                'actual_transfer_weight_kg' => (float)($row['actual_transfer_weight_kg'] ?? 0),
                'transferred_at' => $row['transferred_at'] ?? null,
                'confirmed_by' => (int)($row['confirmed_by'] ?? 0),
            ];
        }
        return $result;
    }

    private function syncBuyerLandMappings(
        int $purchaseOrderId,
        int $buyerSubTankId,
        array $mappings,
        int $purchaseOrderItemId,
        int $actorUserId
    ): bool {
        $now = date('Y-m-d H:i:s');
        $this->db->where('purchase_order_id', $purchaseOrderId);
        $this->db->where('purchase_order_buyer_sub_tank_id', $buyerSubTankId);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_purchasing_order_buyer_land_maps', [
            'deleted_by' => $actorUserId,
            'deleted_at' => $now,
            'updated_by' => $actorUserId,
            'updated_at' => $now,
        ]);
        if ($this->db->getLastErrno() !== 0) {
            return false;
        }

        $seenLandIds = [];
        foreach ($mappings as $mapping) {
            $landId = (int)($mapping['purchase_order_land_id'] ?? 0);
            $plannedWeight = (float)($mapping['planned_receive_weight_kg'] ?? 0);
            $lockedLand = $this->db->rawQueryOne(
                'SELECT purchase_order_land_id
                 FROM eudr_purchasing_order_lands
                 WHERE purchase_order_land_id = ? AND purchase_order_id = ? AND deleted_by = 0
                 FOR UPDATE',
                [$landId, $purchaseOrderId]
            );
            $land = $this->findOrderLandById($purchaseOrderId, $landId);
            if (
                $landId <= 0
                || isset($seenLandIds[$landId])
                || empty($lockedLand)
                || empty($land)
                || $plannedWeight <= 0
                || ($purchaseOrderItemId > 0 && (int)$land['purchase_order_item_id'] !== $purchaseOrderItemId)
            ) {
                return false;
            }
            $seenLandIds[$landId] = true;

            $this->db->where('purchase_order_id', $purchaseOrderId);
            $this->db->where('purchase_order_land_id', $landId);
            $this->db->where('deleted_by', 0);
            $alreadyPlanned = (float)$this->db->getValue(
                'eudr_purchasing_order_buyer_land_maps',
                'COALESCE(SUM(planned_receive_weight_kg), 0)'
            );
            if ($alreadyPlanned + $plannedWeight - (float)$land['purchased_weight_kg'] > 0.001) {
                return false;
            }

            $this->db->insert('eudr_purchasing_order_buyer_land_maps', [
                'purchase_order_id' => $purchaseOrderId,
                'purchase_order_item_id' => (int)$land['purchase_order_item_id'],
                'purchase_order_buyer_sub_tank_id' => $buyerSubTankId,
                'purchase_order_land_id' => $landId,
                'planned_receive_weight_kg' => $plannedWeight,
                'actual_receive_weight_kg' => 0.0,
                'received_at' => null,
                'confirmed_by' => 0,
                'created_at' => $now,
                'created_by' => $actorUserId,
                'updated_at' => $now,
                'updated_by' => $actorUserId,
            ]);
            if ($this->db->getLastErrno() !== 0) {
                return false;
            }
        }

        return true;
    }

    private function buyerLandMappingsHaveIntakes(int $purchaseOrderId, int $buyerSubTankId): bool
    {
        $this->db->rawQuery(
            'SELECT purchase_order_buyer_land_map_id
             FROM eudr_purchasing_order_buyer_land_maps
             WHERE purchase_order_id = ?
               AND purchase_order_buyer_sub_tank_id = ?
               AND deleted_by = 0
             FOR UPDATE',
            [$purchaseOrderId, $buyerSubTankId]
        );
        $row = $this->db->rawQueryOne(
            'SELECT allocation.sub_tank_intake_land_allocation_id
             FROM eudr_purchasing_sub_tank_intake_land_allocations allocation
             INNER JOIN eudr_purchasing_order_buyer_land_maps map
                ON map.purchase_order_buyer_land_map_id = allocation.purchase_order_buyer_land_map_id
             WHERE map.purchase_order_id = ?
               AND map.purchase_order_buyer_sub_tank_id = ?
               AND allocation.deleted_by = 0
             LIMIT 1 FOR UPDATE',
            [$purchaseOrderId, $buyerSubTankId]
        );
        return !empty($row);
    }

    private function getBuyerLandMappingsByBuyerSubTankIds(int $purchaseOrderId, array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $this->db->where('map.purchase_order_id', $purchaseOrderId);
        $this->db->where('map.purchase_order_buyer_sub_tank_id', $ids, 'IN');
        $this->db->where('map.deleted_by', 0);
        $this->db->join(
            'eudr_purchasing_order_lands land',
            'land.purchase_order_land_id = map.purchase_order_land_id AND land.deleted_by = 0',
            'INNER'
        );
        $this->db->orderBy('map.purchase_order_buyer_sub_tank_id', 'ASC');
        $this->db->orderBy('map.purchase_order_land_id', 'ASC');
        $rows = $this->db->arraybuilder()->get(
            'eudr_purchasing_order_buyer_land_maps map',
            null,
            'map.purchase_order_buyer_land_map_id, map.purchase_order_buyer_sub_tank_id, map.purchase_order_land_id, '
                . 'map.purchase_order_item_id, map.planned_receive_weight_kg, '
                . 'map.actual_receive_weight_kg, map.received_at, map.confirmed_by, '
                . 'land.plot_id, land.land_code, land.land_name, land.farmer_name'
        );
        $result = [];
        foreach ((array)$rows as $row) {
            $result[(int)$row['purchase_order_buyer_sub_tank_id']][] = [
                'purchase_order_buyer_land_map_id' => (int)$row['purchase_order_buyer_land_map_id'],
                'purchase_order_land_id' => (int)$row['purchase_order_land_id'],
                'purchase_order_item_id' => (int)$row['purchase_order_item_id'],
                'plot_id' => (int)$row['plot_id'],
                'land_code' => $row['land_code'] ?? null,
                'land_name' => $row['land_name'] ?? null,
                'farmer_name' => $row['farmer_name'] ?? null,
                'planned_receive_weight_kg' => (float)$row['planned_receive_weight_kg'],
                'actual_receive_weight_kg' => (float)$row['actual_receive_weight_kg'],
                'received_at' => $row['received_at'] ?? null,
                'confirmed_by' => (int)($row['confirmed_by'] ?? 0),
            ];
        }
        return $result;
    }
}
