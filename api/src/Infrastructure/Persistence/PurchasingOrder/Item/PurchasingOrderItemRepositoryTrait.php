<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PurchasingOrder\Item;

trait PurchasingOrderItemRepositoryTrait
{
    /** @return array<int,array<string,mixed>> */
    public function listOrderItemsByOrderId(int $purchase_order_id): array
    {
        $this->db->where('purchase_order_id', $purchase_order_id);
        $this->db->where('deleted_by', 0);
        $this->db->orderBy('purchase_order_item_id', 'ASC');
        $rows = $this->db->get('eudr_purchasing_order_items');

        return array_map(
            static function (array $row): array {
                return [
                    'purchase_order_item_id' => (int)$row['purchase_order_item_id'],
                    'purchase_order_id' => (int)$row['purchase_order_id'],
                    'rubber_type' => (string)($row['rubber_type'] ?? 'latex'),
                    'quality_basis' => (string)($row['quality_basis'] ?? 'kg'),
                    'quality_value' => isset($row['quality_value']) ? (float)$row['quality_value'] : null,
                    'quantity' => (float)($row['quantity'] ?? 0),
                    'weight_kg' => (float)($row['weight_kg'] ?? 0),
                    'unit_price' => (float)($row['unit_price'] ?? 0),
                    'line_amount' => (float)($row['line_amount'] ?? 0),
                    'notes' => $row['notes'] ?? null,
                ];
            },
            $rows
        );
    }

    /**
     * @param int $purchase_order_id
     * @param array $item
     * @return array<string,mixed>|null
     */
    public function addOrderItemWithPermission(
        int $purchase_order_id,
        array $item,
        ?int $auth_user_id,
        string $scope,
        ?int $company_id = null
    ): ?array {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $order = $this->findOrderOfIdWithPermission(
            $purchase_order_id,
            (int)$authUserId,
            $scope,
            (int)$companyId
        );
        if (empty($order)) {
            return null;
        }

        $this->db->insert('eudr_purchasing_order_items', $item);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }

        $itemId = (int)$this->db->getInsertId();
        $this->recalculateTotals($purchase_order_id, (int)$authUserId);
        return $this->findOrderItem($purchase_order_id, $itemId);
    }

    /**
     * @param int $purchase_order_id
     * @param int $purchase_order_item_id
     * @param array $item
     * @return array<string,mixed>|null
     */
    public function updateOrderItemWithPermission(
        int $purchase_order_id,
        int $purchase_order_item_id,
        array $item,
        ?int $auth_user_id,
        string $scope,
        ?int $company_id = null
    ): ?array {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $order = $this->findOrderOfIdWithPermission(
            $purchase_order_id,
            (int)$authUserId,
            $scope,
            (int)$companyId
        );
        if (empty($order)) {
            return null;
        }

        $this->db->where('purchase_order_item_id', $purchase_order_item_id);
        $this->db->where('purchase_order_id', $purchase_order_id);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_purchasing_order_items', $item);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }

        $this->recalculateTotals($purchase_order_id, (int)$authUserId);
        return $this->findOrderItem($purchase_order_id, $purchase_order_item_id);
    }

    /**
     * @param int $purchase_order_id
     * @param int $purchase_order_item_id
     * @param int $deleted_by
     * @return bool
     */
    public function deleteOrderItemWithPermission(
        int $purchase_order_id,
        int $purchase_order_item_id,
        int $deleted_by,
        ?int $auth_user_id,
        string $scope,
        ?int $company_id = null
    ): bool {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $order = $this->findOrderOfIdWithPermission(
            $purchase_order_id,
            (int)$authUserId,
            $scope,
            (int)$companyId
        );
        if (empty($order)) {
            return false;
        }

        if ($order->getStatus() !== 'draft') {
            return false;
        }

        $this->db->where('purchase_order_item_id', $purchase_order_item_id);
        $this->db->where('purchase_order_id', $purchase_order_id);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_purchasing_order_items', [
            'deleted_by' => $deleted_by,
            'deleted_at' => date('Y-m-d H:i:s'),
        ]);

        if ($this->db->getLastErrno() !== 0) {
            return false;
        }

        $this->recalculateTotals($purchase_order_id, (int)$authUserId);
        return true;
    }

    /** @return array<string,mixed>|null */
    private function findOrderItem(int $purchase_order_id, int $purchase_order_item_id): ?array
    {
        $this->db->where('purchase_order_item_id', $purchase_order_item_id);
        $this->db->where('purchase_order_id', $purchase_order_id);
        $this->db->where('deleted_by', 0);
        $row = $this->db->getOne('eudr_purchasing_order_items');
        if (empty($row)) {
            return null;
        }

        return [
            'purchase_order_item_id' => (int)$row['purchase_order_item_id'],
            'purchase_order_id' => (int)$row['purchase_order_id'],
            'rubber_type' => (string)($row['rubber_type'] ?? 'latex'),
            'quality_basis' => (string)($row['quality_basis'] ?? 'kg'),
            'quality_value' => isset($row['quality_value']) ? (float)$row['quality_value'] : null,
            'quantity' => (float)($row['quantity'] ?? 0),
            'weight_kg' => (float)($row['weight_kg'] ?? 0),
            'unit_price' => (float)($row['unit_price'] ?? 0),
            'line_amount' => (float)($row['line_amount'] ?? 0),
            'notes' => $row['notes'] ?? null,
        ];
    }

    /**
     * Recalculate the total quantity, total weight, and total estimated amount for a purchase order.
     *
     * @param int $purchase_order_id
     * @param int $updated_by
     * @return void
     */
    private function recalculateTotals(int $purchase_order_id, int $updated_by): void
    {
        $this->db->where('purchase_order_id', $purchase_order_id);
        $this->db->where('deleted_by', 0);
        $sum = $this->db->getOne(
            'eudr_purchasing_order_items',
            'COALESCE(SUM(quantity),0) AS total_quantity, '
                . 'COALESCE(SUM(weight_kg),0) AS total_weight_kg, '
                . 'COALESCE(SUM(line_amount),0) AS total_estimated_amount'
        );

        $this->db->where('purchase_order_id', $purchase_order_id);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_purchasing_orders', [
            'total_quantity' => (float)($sum['total_quantity'] ?? 0),
            'total_weight_kg' => (float)($sum['total_weight_kg'] ?? 0),
            'total_estimated_amount' => (float)($sum['total_estimated_amount'] ?? 0),
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $updated_by,
        ]);
    }
}
