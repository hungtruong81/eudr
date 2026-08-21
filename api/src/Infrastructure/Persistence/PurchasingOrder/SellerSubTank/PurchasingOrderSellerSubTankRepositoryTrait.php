<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PurchasingOrder\SellerSubTank;

trait PurchasingOrderSellerSubTankRepositoryTrait
{
    /**
     * @param int $purchase_order_id
     * @param array $data
     * @return array<string,mixed>|null
     */
    public function createSellerSubTankByOrderId(int $purchase_order_id, array $data): ?array
    {
        $order = $this->findOrderOfId($purchase_order_id);
        if (empty($order) || $order->getStatus() !== 'sent_to_seller') {
            return null;
        }

        $this->db->insert('eudr_purchasing_order_seller_sub_tanks', $data);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }

        $insertedId = (int)$this->db->getInsertId();
        return $this->findSellerSubTankById($purchase_order_id, $insertedId);
    }

    /**
     * @param int $purchase_order_id
     * @param int $purchase_order_seller_sub_tank_id
     * @param array $data
     * @return array<string,mixed>|null
     */
    public function updateSellerSubTankByOrderId(
        int $purchase_order_id,
        int $purchase_order_seller_sub_tank_id,
        array $data
    ): ?array {
        $order = $this->findOrderOfId($purchase_order_id);
        if (empty($order) || $order->getStatus() !== 'sent_to_seller') {
            return null;
        }

        $this->db->where('purchase_order_seller_sub_tank_id', $purchase_order_seller_sub_tank_id);
        $this->db->where('purchase_order_id', $purchase_order_id);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_purchasing_order_seller_sub_tanks', $data);

        if ($this->db->getLastErrno() !== 0) {
            return null;
        }

        return $this->findSellerSubTankById($purchase_order_id, $purchase_order_seller_sub_tank_id);
    }

    /**
     * @param int $purchase_order_id
     * @param int $purchase_order_seller_sub_tank_id
     * @param int $deleted_by
     * @return bool
     */
    public function deleteSellerSubTankByOrderId(
        int $purchase_order_id,
        int $purchase_order_seller_sub_tank_id,
        int $deleted_by
    ): bool {
        $order = $this->findOrderOfId($purchase_order_id);
        if (empty($order) || $order->getStatus() !== 'sent_to_seller') {
            return false;
        }

        if (empty($this->findSellerSubTankById($purchase_order_id, $purchase_order_seller_sub_tank_id))) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->startTransaction();

        $this->db->where('purchase_order_id', $purchase_order_id);
        $this->db->where('purchase_order_seller_sub_tank_id', $purchase_order_seller_sub_tank_id);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_purchasing_order_buyer_seller_sub_tank_maps', [
            'deleted_by' => $deleted_by,
            'deleted_at' => $now,
            'updated_by' => $deleted_by,
            'updated_at' => $now,
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return false;
        }

        $this->db->where('purchase_order_seller_sub_tank_id', $purchase_order_seller_sub_tank_id);
        $this->db->where('purchase_order_id', $purchase_order_id);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_purchasing_order_seller_sub_tanks', [
            'deleted_by' => $deleted_by,
            'deleted_at' => $now,
            'updated_by' => $deleted_by,
            'updated_at' => $now,
        ]);

        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return false;
        }

        $this->db->commit();
        return true;
    }

    /**
     * @param int $purchase_order_id
     * @param array $params
     * @return array<string,mixed>
     */
    public function listSellerSubTanksByOrderId(int $purchase_order_id, array $params = []): array
    {
        $page = (int)($params['page'] ?? 1);
        $pageLimit = (int)($params['page_limit'] ?? 20);
        $status = (string)($params['status'] ?? 'all');

        $this->db->where('post.purchase_order_id', $purchase_order_id);
        $this->db->where('post.deleted_by', 0);
        if ($status !== 'all') {
            $this->db->where('post.status', $status);
        }
        $totalRecords = (int)$this->db->getValue(
            'eudr_purchasing_order_seller_sub_tanks post',
            'COUNT(*)'
        );

        $this->db->pageLimit = $pageLimit;
        $this->db->where('post.purchase_order_id', $purchase_order_id);
        $this->db->where('post.deleted_by', 0);
        if ($status !== 'all') {
            $this->db->where('post.status', $status);
        }
        $this->db->join('eudr_purchasing_sub_tanks pst', 'pst.sub_tank_id = post.sub_tank_id', 'LEFT');
        $this->db->orderBy('post.purchase_order_seller_sub_tank_id', 'DESC');
        $rows = $this->db->arraybuilder()->paginate(
            'eudr_purchasing_order_seller_sub_tanks post',
            $page,
            'post.*, pst.sub_tank_code, pst.sub_tank_name'
        );

        $records = [];
        foreach ((array)$rows as $row) {
            $records[] = [
                'purchase_order_seller_sub_tank_id' => (int)$row['purchase_order_seller_sub_tank_id'],
                'purchase_order_id' => (int)$row['purchase_order_id'],
                'sub_tank_id' => (int)$row['sub_tank_id'],
                'sub_tank_code' => $row['sub_tank_code'] ?? null,
                'sub_tank_name' => $row['sub_tank_name'] ?? null,
                'seller_company_id' => (int)($row['seller_company_id'] ?? 0),
                'declared_by' => (int)($row['declared_by'] ?? 0),
                'purchase_order_item_id' => isset($row['purchase_order_item_id'])
                    ? (int)$row['purchase_order_item_id']
                    : null,
                'filled_weight_kg' => (float)($row['filled_weight_kg'] ?? 0),
                'estimated_tsc_percent' => isset($row['estimated_tsc_percent'])
                    ? (float)$row['estimated_tsc_percent']
                    : null,
                'estimated_drc_percent' => isset($row['estimated_drc_percent'])
                    ? (float)$row['estimated_drc_percent']
                    : null,
                'sealed_at' => $row['sealed_at'] ?? null,
                'sealed_by' => isset($row['sealed_by']) ? (int)$row['sealed_by'] : 0,
                'status' => (string)($row['status'] ?? 'declared'),
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

    /** @return array<string,mixed>|null */
    private function findSellerSubTankById(
        int $purchase_order_id,
        int $purchase_order_seller_sub_tank_id
    ): ?array {
        $this->db->where('purchase_order_seller_sub_tank_id', $purchase_order_seller_sub_tank_id);
        $this->db->where('purchase_order_id', $purchase_order_id);
        $this->db->where('deleted_by', 0);
        $row = $this->db->getOne('eudr_purchasing_order_seller_sub_tanks');
        if (empty($row)) {
            return null;
        }

        return [
            'purchase_order_seller_sub_tank_id' => (int)$row['purchase_order_seller_sub_tank_id'],
            'purchase_order_id' => (int)$row['purchase_order_id'],
            'sub_tank_id' => (int)$row['sub_tank_id'],
            'seller_company_id' => (int)($row['seller_company_id'] ?? 0),
            'declared_by' => (int)($row['declared_by'] ?? 0),
            'purchase_order_item_id' => isset($row['purchase_order_item_id'])
                ? (int)$row['purchase_order_item_id']
                : null,
            'filled_weight_kg' => (float)($row['filled_weight_kg'] ?? 0),
            'estimated_tsc_percent' => isset($row['estimated_tsc_percent'])
                ? (float)$row['estimated_tsc_percent']
                : null,
            'estimated_drc_percent' => isset($row['estimated_drc_percent'])
                ? (float)$row['estimated_drc_percent']
                : null,
            'sealed_at' => $row['sealed_at'] ?? null,
            'sealed_by' => isset($row['sealed_by']) ? (int)$row['sealed_by'] : 0,
            'status' => (string)($row['status'] ?? 'declared'),
            'notes' => $row['notes'] ?? null,
        ];
    }
}
