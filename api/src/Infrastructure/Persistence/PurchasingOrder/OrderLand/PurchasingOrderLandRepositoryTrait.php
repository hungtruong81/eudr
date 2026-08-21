<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PurchasingOrder\OrderLand;

trait PurchasingOrderLandRepositoryTrait
{

    public function listOrderLandsByOrderId(int $purchaseOrderId): array
    {
        $this->db->where('pol.purchase_order_id', $purchaseOrderId);
        $this->db->where('pol.deleted_by', 0);
        $this->db->where('pol.deleted_at', null, 'IS');
        $this->db->orderBy('pol.purchase_order_land_id', 'ASC');
        $rows = $this->db->get('eudr_purchasing_order_lands pol');

        return array_map(fn(array $row): array => $this->normalizeOrderLand($row), (array)$rows);
    }

    public function findOrderLandById(int $purchaseOrderId, int $purchaseOrderLandId): ?array
    {
        $this->db->where('purchase_order_id', $purchaseOrderId);
        $this->db->where('purchase_order_land_id', $purchaseOrderLandId);
        $this->db->where('deleted_by', 0);
        $this->db->where('deleted_at', null, 'IS');
        $row = $this->db->getOne('eudr_purchasing_order_lands');

        return empty($row) ? null : $this->normalizeOrderLand($row);
    }

    public function createOrderLandByOrderId(int $purchaseOrderId, array $data): ?array
    {
        if (!$this->isOrderLandEditable($purchaseOrderId)) {
            return null;
        }

        $this->db->insert('eudr_purchasing_order_lands', $data);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }

        return $this->findOrderLandById($purchaseOrderId, (int)$this->db->getInsertId());
    }

    public function updateOrderLandByOrderId(
        int $purchaseOrderId,
        int $purchaseOrderLandId,
        array $data
    ): ?array {
        if (
            !$this->isOrderLandEditable($purchaseOrderId)
            || $this->findOrderLandById($purchaseOrderId, $purchaseOrderLandId) === null
        ) {
            return null;
        }

        $this->db->where('purchase_order_id', $purchaseOrderId);
        $this->db->where('purchase_order_land_id', $purchaseOrderLandId);
        $this->db->where('deleted_by', 0);
        $this->db->where('deleted_at', null, 'IS');
        $this->db->update('eudr_purchasing_order_lands', $data);

        return $this->db->getLastErrno() === 0
            ? $this->findOrderLandById($purchaseOrderId, $purchaseOrderLandId)
            : null;
    }

    public function deleteOrderLandByOrderId(
        int $purchaseOrderId,
        int $purchaseOrderLandId,
        int $deletedBy
    ): bool {
        if (
            !$this->isOrderLandEditable($purchaseOrderId)
            || $this->findOrderLandById($purchaseOrderId, $purchaseOrderLandId) === null
        ) {
            return false;
        }

        $this->db->where('purchase_order_id', $purchaseOrderId);
        $this->db->where('purchase_order_land_id', $purchaseOrderLandId);
        $this->db->where('deleted_by', 0);
        $this->db->where('deleted_at', null, 'IS');
        $this->db->update('eudr_purchasing_order_lands', [
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => $deletedBy,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $deletedBy,
        ]);

        return $this->db->getLastErrno() === 0;
    }

    public function orderItemBelongsToOrder(int $purchaseOrderId, int $purchaseOrderItemId): bool
    {
        $this->db->where('purchase_order_id', $purchaseOrderId);
        $this->db->where('purchase_order_item_id', $purchaseOrderItemId);
        $this->db->where('deleted_by', 0);
        $this->db->where('deleted_at', null, 'IS');

        return (int)$this->db->getValue('eudr_purchasing_order_items', 'COUNT(*)') > 0;
    }

    private function isOrderLandEditable(int $purchaseOrderId): bool
    {
        $order = $this->findOrderOfId($purchaseOrderId);
        return $order !== null && in_array($order->getStatus(), ['draft', 'sent_to_seller', 'seller_confirmed'], true);
    }

    private function normalizeOrderLand(array $row): array
    {
        return [
            'purchase_order_land_id' => (int)$row['purchase_order_land_id'],
            'purchase_order_id' => (int)$row['purchase_order_id'],
            'purchase_order_item_id' => (int)$row['purchase_order_item_id'],
            'plot_id' => (int)$row['plot_id'],
            'seller_source_type' => (string)$row['seller_source_type'],
            'farmer_user_id' => isset($row['farmer_user_id']) ? (int)$row['farmer_user_id'] : null,
            'vendor_id' => isset($row['vendor_id']) ? (int)$row['vendor_id'] : null,
            'land_code' => $row['land_code'] ?? null,
            'land_name' => $row['land_name'] ?? null,
            'farmer_name' => $row['farmer_name'] ?? null,
            'land_area' => isset($row['land_area']) ? (float)$row['land_area'] : null,
            'harvest_weight_kg' => (float)($row['harvest_weight_kg'] ?? 0),
            'purchased_weight_kg' => (float)($row['purchased_weight_kg'] ?? 0),
            'notes' => $row['notes'] ?? null,
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }
}
