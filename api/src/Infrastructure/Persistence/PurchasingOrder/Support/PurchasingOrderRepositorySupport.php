<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PurchasingOrder\Support;

trait PurchasingOrderRepositorySupport
{
    private function scopeWhere(
        string $scope,
        int $authUserId,
        int $companyId,
        ?int $companyIdParam = null,
        string $alias = 'po'
    ): void {
        $prefix = $alias !== '' ? $alias . '.' : '';
        $this->db->where($prefix . 'deleted_by', 0);

        if ($scope === 'self') {
            $this->db->where($prefix . 'created_by', $authUserId);
            return;
        }

        if ($scope === 'own') {
            $this->db->where($prefix . 'company_id', $companyId);
            return;
        }

        if ($scope === 'all' && !empty($companyIdParam)) {
            $this->db->where($prefix . 'company_id', (int)$companyIdParam);
        }
    }

    private function viewScopeWhere(
        string $scope,
        int $authUserId,
        int $companyId,
        ?int $companyIdParam = null,
        string $alias = 'po'
    ): void {
        $prefix = $alias !== '' ? $alias . '.' : '';
        $this->db->where($prefix . 'deleted_by', 0);

        if ($scope === 'self') {
            $this->db->where(
                '(' . $prefix . 'created_by = ? OR ' . $prefix . 'seller_user_id = ?)',
                [$authUserId, $authUserId]
            );
            return;
        }

        if ($scope === 'own') {
            $conditions = [
                $prefix . 'company_id = ?',
                $prefix . 'seller_user_id = ?',
            ];
            $bindings = [$companyId, $authUserId];
            if ($companyId > 0) {
                $conditions[] = $prefix . 'seller_company_id = ?';
                $bindings[] = $companyId;
            }
            $this->db->where('(' . implode(' OR ', $conditions) . ')', $bindings);
            return;
        }

        if ($scope === 'all' && !empty($companyIdParam)) {
            $this->db->where($prefix . 'company_id', (int)$companyIdParam);
        }
    }

    /** @return array<int,array<string,mixed>> */
    private function getItems(int $purchase_order_id): array
    {
        $this->db->where('poi.purchase_order_id', $purchase_order_id);
        $this->db->where('poi.deleted_by', 0);
        $this->db->orderBy('poi.purchase_order_item_id', 'ASC');
        $rows = $this->db->get('eudr_purchasing_order_items poi', null, 'poi.*');

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
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

        return $items;
    }
}
