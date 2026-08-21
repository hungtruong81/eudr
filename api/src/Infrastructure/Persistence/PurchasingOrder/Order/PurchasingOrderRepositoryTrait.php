<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PurchasingOrder\Order;

use App\Application\Utility\Utils;
use App\Domain\PurchasingOrder\PurchasingOrder;

trait PurchasingOrderRepositoryTrait
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getStatusHistory(int $purchase_order_id): array
    {
        $rows = $this->db->rawQuery(
            'SELECT log.purchase_order_status_log_id, log.purchase_order_id,
                    log.from_status, log.to_status, log.actor_user_id,
                    log.actor_role, log.action_name, log.notes, log.created_at,
                    user.user_code AS actor_user_code,
                    user.full_name AS actor_name
             FROM eudr_purchasing_order_status_logs log
             LEFT JOIN eudr_users user ON user.user_id = log.actor_user_id
             WHERE log.purchase_order_id = ?
             ORDER BY log.created_at ASC, log.purchase_order_status_log_id ASC',
            [$purchase_order_id]
        );

        return array_map(static function (array $row): array {
            $row['purchase_order_status_log_id'] = (int)$row['purchase_order_status_log_id'];
            $row['purchase_order_id'] = (int)$row['purchase_order_id'];
            $row['actor_user_id'] = (int)$row['actor_user_id'];
            return $row;
        }, (array)$rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function getReconciliation(int $purchase_order_id): array
    {
        $order = $this->db->rawQueryOne(
            'SELECT purchase_order_id, purchase_order_code, seller_account_type, status
             FROM eudr_purchasing_orders
             WHERE purchase_order_id = ? AND deleted_by = 0',
            [$purchase_order_id]
        );
        if (empty($order)) {
            return [];
        }

        $sellerAccountType = (string)$order['seller_account_type'];
        $isFarmer = $sellerAccountType === 'farmer';
        $mappingTable = $isFarmer
            ? 'eudr_purchasing_order_buyer_land_maps'
            : 'eudr_purchasing_order_buyer_seller_sub_tank_maps';
        $plannedColumn = $isFarmer ? 'planned_receive_weight_kg' : 'planned_transfer_weight_kg';
        $actualColumn = $isFarmer ? 'actual_receive_weight_kg' : 'actual_transfer_weight_kg';

        $totals = $this->db->rawQueryOne(
            "SELECT COALESCE(SUM($plannedColumn), 0) AS planned_weight_kg,
                    COALESCE(SUM($actualColumn), 0) AS actual_weight_kg
             FROM $mappingTable
             WHERE purchase_order_id = ? AND deleted_by = 0",
            [$purchase_order_id]
        );
        $transport = $this->db->rawQueryOne(
            "SELECT COALESCE(SUM(CASE WHEN transport.status <> 'cancelled' THEN line.loaded_weight_kg ELSE 0 END), 0) AS loaded_weight_kg,
                COALESCE(SUM(CASE WHEN transport.status IN ('arrived', 'closed') THEN line.loaded_weight_kg ELSE 0 END), 0) AS arrived_weight_kg
             FROM eudr_purchasing_transports transport
             LEFT JOIN eudr_purchasing_transport_sub_tanks line
                    ON line.purchase_transport_id = transport.purchase_transport_id
             WHERE transport.purchase_order_id = ? AND transport.deleted_by = 0",
            [$purchase_order_id]
        );
        $intake = $this->db->rawQueryOne(
            'SELECT COALESCE(SUM(received_weight_kg), 0) AS intake_weight_kg
             FROM eudr_purchasing_sub_tank_intakes
             WHERE purchase_order_id = ? AND deleted_by = 0',
            [$purchase_order_id]
        );
        $receipt = $this->db->rawQueryOne(
            "SELECT COALESCE(SUM(CASE WHEN receipt.status = 'posted' THEN item.accepted_weight_kg ELSE 0 END), 0) AS receipt_weight_kg,
                    COALESCE(SUM(CASE WHEN receipt.status = 'posted' THEN item.rejected_weight_kg ELSE 0 END), 0) AS rejected_weight_kg
             FROM eudr_purchasing_factory_receipts receipt
             LEFT JOIN eudr_purchasing_factory_receipt_items item
                    ON item.factory_receipt_id = receipt.factory_receipt_id
             WHERE receipt.purchase_order_id = ? AND receipt.deleted_by = 0",
            [$purchase_order_id]
        );

        $planned = (float)($totals['planned_weight_kg'] ?? 0);
        $actual = (float)($totals['actual_weight_kg'] ?? 0);
        $loaded = (float)($transport['loaded_weight_kg'] ?? 0);
        $arrived = (float)($transport['arrived_weight_kg'] ?? 0);
        $intakeWeight = (float)($intake['intake_weight_kg'] ?? 0);
        $receiptWeight = (float)($receipt['receipt_weight_kg'] ?? 0);
        $rejectedWeight = (float)($receipt['rejected_weight_kg'] ?? 0);
        $variance = static fn(float $left, float $right): float => round($left - $right, 2);
        $mappingRows = $isFarmer
            ? $this->db->rawQuery(
                'SELECT map.purchase_order_buyer_land_map_id AS mapping_id,
                        map.purchase_order_item_id, map.purchase_order_buyer_sub_tank_id,
                        map.purchase_order_land_id AS source_id,
                        land.land_code AS source_code, land.land_name AS source_name,
                        tank.sub_tank_id AS buyer_sub_tank_id,
                        tank.sub_tank_code AS buyer_sub_tank_code,
                        map.planned_receive_weight_kg AS planned_weight_kg,
                        map.actual_receive_weight_kg AS actual_weight_kg,
                        map.received_at AS reconciled_at
                 FROM eudr_purchasing_order_buyer_land_maps map
                 INNER JOIN eudr_purchasing_order_buyer_sub_tanks buyer
                    ON buyer.purchase_order_buyer_sub_tank_id = map.purchase_order_buyer_sub_tank_id
                   AND buyer.deleted_by = 0
                 INNER JOIN eudr_purchasing_sub_tanks tank
                    ON tank.sub_tank_id = buyer.sub_tank_id AND tank.deleted_by = 0
                 LEFT JOIN eudr_purchasing_order_lands land
                    ON land.purchase_order_land_id = map.purchase_order_land_id AND land.deleted_by = 0
                 WHERE map.purchase_order_id = ? AND map.deleted_by = 0
                 ORDER BY map.purchase_order_buyer_land_map_id',
                [$purchase_order_id]
            )
            : $this->db->rawQuery(
                'SELECT map.purchase_order_buyer_seller_sub_tank_map_id AS mapping_id,
                        buyer.purchase_order_item_id, map.purchase_order_buyer_sub_tank_id,
                        seller.sub_tank_id AS source_id,
                        seller_tank.sub_tank_code AS source_code,
                        seller_tank.sub_tank_name AS source_name,
                        buyer.sub_tank_id AS buyer_sub_tank_id,
                        buyer_tank.sub_tank_code AS buyer_sub_tank_code,
                        map.planned_transfer_weight_kg AS planned_weight_kg,
                        map.actual_transfer_weight_kg AS actual_weight_kg,
                        map.transferred_at AS reconciled_at
                 FROM eudr_purchasing_order_buyer_seller_sub_tank_maps map
                 INNER JOIN eudr_purchasing_order_buyer_sub_tanks buyer
                    ON buyer.purchase_order_buyer_sub_tank_id = map.purchase_order_buyer_sub_tank_id
                   AND buyer.deleted_by = 0
                 INNER JOIN eudr_purchasing_order_seller_sub_tanks seller
                    ON seller.purchase_order_seller_sub_tank_id = map.purchase_order_seller_sub_tank_id
                   AND seller.deleted_by = 0
                 INNER JOIN eudr_purchasing_sub_tanks buyer_tank
                    ON buyer_tank.sub_tank_id = buyer.sub_tank_id AND buyer_tank.deleted_by = 0
                 INNER JOIN eudr_purchasing_sub_tanks seller_tank
                    ON seller_tank.sub_tank_id = seller.sub_tank_id AND seller_tank.deleted_by = 0
                 WHERE map.purchase_order_id = ? AND map.deleted_by = 0
                 ORDER BY map.purchase_order_buyer_seller_sub_tank_map_id',
                [$purchase_order_id]
            );
        $mappings = array_map(static function (array $row): array {
            $plannedWeight = (float)$row['planned_weight_kg'];
            $actualWeight = (float)$row['actual_weight_kg'];
            foreach (['mapping_id', 'purchase_order_item_id', 'purchase_order_buyer_sub_tank_id', 'source_id', 'buyer_sub_tank_id'] as $field) {
                $row[$field] = (int)$row[$field];
            }
            $row['planned_weight_kg'] = $plannedWeight;
            $row['actual_weight_kg'] = $actualWeight;
            $row['variance_kg'] = round($plannedWeight - $actualWeight, 2);
            $row['is_balanced'] = abs($plannedWeight - $actualWeight) <= 0.01;
            return $row;
        }, (array)$mappingRows);

        return [
            'purchase_order_id' => (int)$order['purchase_order_id'],
            'purchase_order_code' => $order['purchase_order_code'],
            'seller_account_type' => $sellerAccountType,
            'status' => $order['status'],
            'source_type' => $isFarmer ? 'land' : 'seller_sub_tank',
            'weights' => [
                'planned_weight_kg' => $planned,
                'actual_mapped_weight_kg' => $actual,
                'loaded_weight_kg' => $loaded,
                'arrived_weight_kg' => $arrived,
                'intake_weight_kg' => $intakeWeight,
                'factory_received_weight_kg' => $receiptWeight,
                'rejected_weight_kg' => $rejectedWeight,
            ],
            'variances' => [
                'planned_vs_actual_kg' => $variance($planned, $actual),
                'actual_vs_loaded_kg' => $variance($actual, $loaded),
                'loaded_vs_arrived_kg' => $variance($loaded, $arrived),
                'arrived_vs_intake_kg' => $variance($arrived, $intakeWeight),
                'intake_vs_factory_received_kg' => $variance($intakeWeight, $receiptWeight + $rejectedWeight),
            ],
            'mappings' => $mappings,
            'is_balanced' => abs($planned - $actual) <= 0.01
                && abs($actual - $loaded) <= 0.01
                && abs($loaded - $arrived) <= 0.01
                && abs($arrived - $intakeWeight) <= 0.01
                && abs($intakeWeight - ($receiptWeight + $rejectedWeight)) <= 0.01,
        ];
    }

    public function findAll(
        array $params = [],
        ?int $auth_user_id = null,
        string $scope = 'own',
        ?int $company_id = null,
        ?int $company_id_param = null
    ): array {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $page = (int)($params['page'] ?? 1);
        $pageLimit = (int)($params['page_limit'] ?? 10);
        $search = trim((string)($params['search'] ?? ''));
        $status = (string)($params['status'] ?? 'all');
        $dateFrom = $params['purchase_date_from'] ?? null;
        $dateTo = $params['purchase_date_to'] ?? null;
        $sellerSourceType = $params['seller_source_type'] ?? null;

        $this->viewScopeWhere($scope, (int)$authUserId, (int)$companyId, $company_id_param, 'po');
        if ($status !== 'all') {
            $this->db->where('po.status', $status);
        }
        if ($search !== '') {
            $this->db->where(
                '(po.purchase_order_code LIKE ? OR po.seller_name LIKE ? OR po.buyer_name LIKE ?)',
                ["%$search%", "%$search%", "%$search%"]
            );
        }
        if (!empty($dateFrom)) {
            $this->db->where('po.purchase_date', $dateFrom, '>=');
        }
        if (!empty($dateTo)) {
            $this->db->where('po.purchase_date', $dateTo, '<=');
        }
        if (!empty($sellerSourceType) && $sellerSourceType !== 'all') {
            $this->db->where('po.seller_source_type', $sellerSourceType);
        }
        $totalRecords = (int)$this->db->getValue('eudr_purchasing_orders po', 'COUNT(*)');

        $this->db->pageLimit = $pageLimit;
        $this->viewScopeWhere($scope, (int)$authUserId, (int)$companyId, $company_id_param, 'po');
        if ($status !== 'all') {
            $this->db->where('po.status', $status);
        }
        if ($search !== '') {
            $this->db->where(
                '(po.purchase_order_code LIKE ? OR po.seller_name LIKE ? OR po.buyer_name LIKE ?)',
                ["%$search%", "%$search%", "%$search%"]
            );
        }
        if (!empty($dateFrom)) {
            $this->db->where('po.purchase_date', $dateFrom, '>=');
        }
        if (!empty($dateTo)) {
            $this->db->where('po.purchase_date', $dateTo, '<=');
        }
        if (!empty($sellerSourceType) && $sellerSourceType !== 'all') {
            $this->db->where('po.seller_source_type', $sellerSourceType);
        }
        $this->db->orderBy('po.purchase_order_id', 'DESC');
        $rows = $this->db->arraybuilder()->paginate('eudr_purchasing_orders po', $page, 'po.*');

        $records = [];
        foreach ($rows as $row) {
            $row['items'] = [];
            $records[] = new PurchasingOrder((int)$row['purchase_order_id'], $row);
        }
        return [
            'current_page' => $page,
            'total_pages' => (int)$this->db->totalPages,
            'total_records' => $totalRecords,
            'page_limit' => $pageLimit,
            'records' => $records,
        ];
    }

    public function findOrderOfId(int $purchase_order_id): ?PurchasingOrder
    {
        $this->db->where('po.deleted_by', 0);
        $this->db->where('po.purchase_order_id', $purchase_order_id);
        return $this->hydrateOrder($this->db->getOne('eudr_purchasing_orders po', 'po.*'));
    }

    public function findOrderOfCode(string $purchase_order_code): ?PurchasingOrder
    {
        $this->db->where('po.deleted_by', 0);
        $this->db->where('po.purchase_order_code', $purchase_order_code);
        return $this->hydrateOrder($this->db->getOne('eudr_purchasing_orders po', 'po.*'));
    }

    public function findOrderOfCodeWithPermission(
        string $purchase_order_code,
        ?int $auth_user_id,
        string $scope,
        ?int $company_id = null,
        ?int $company_id_param = null
    ): ?PurchasingOrder {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $this->viewScopeWhere($scope, (int)$authUserId, (int)$companyId, $company_id_param, 'po');
        $this->db->where('po.purchase_order_code', $purchase_order_code);
        return $this->hydrateOrder($this->db->getOne('eudr_purchasing_orders po', 'po.*'));
    }

    public function findOrderOfIdWithPermission(
        int $purchase_order_id,
        ?int $auth_user_id,
        string $scope,
        ?int $company_id = null,
        ?int $company_id_param = null
    ): ?PurchasingOrder {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $this->viewScopeWhere($scope, (int)$authUserId, (int)$companyId, $company_id_param, 'po');
        $this->db->where('po.purchase_order_id', $purchase_order_id);
        return $this->hydrateOrder($this->db->getOne('eudr_purchasing_orders po', 'po.*'));
    }

    public function generateCode(): string
    {
        while (true) {
            $code = 'pord-' . date('ymd') . '-' . Utils::generateRandomString(8);
            if ($this->findOrderOfCode($code) === null) {
                return $code;
            }
        }
    }

    public function createOrder(array $data): ?PurchasingOrder
    {
        $this->db->insert('eudr_purchasing_orders', $data);
        return $this->db->getLastErrno() !== 0 ? null : $this->findOrderOfId((int)$this->db->getInsertId());
    }

    public function updateDraftOrderWithPermission(
        int $purchase_order_id,
        array $update_data,
        ?int $auth_user_id,
        string $scope,
        ?int $company_id = null
    ): ?PurchasingOrder {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $this->scopeWhere($scope, (int)$authUserId, (int)$companyId, null, 'po');
        $this->db->where('po.purchase_order_id', $purchase_order_id);
        $this->db->where('po.status', 'draft');
        $this->db->update('eudr_purchasing_orders po', $update_data);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }
        $updatedOrder = $this->findOrderOfIdWithPermission(
            $purchase_order_id,
            (int)$authUserId,
            $scope,
            (int)$companyId
        );
        return empty($updatedOrder) || ($updatedOrder->jsonSerialize()['status'] ?? '') !== 'draft'
            ? null
            : $updatedOrder;
    }

    public function deleteDraftOrderWithPermission(
        int $purchase_order_id,
        int $deleted_by,
        ?int $auth_user_id,
        string $scope,
        ?int $company_id = null
    ): bool {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $now = date('Y-m-d H:i:s');
        $this->scopeWhere($scope, (int)$authUserId, (int)$companyId, null, 'po');
        $this->db->where('po.purchase_order_id', $purchase_order_id);
        $this->db->where('po.status', 'draft');
        $this->db->update('eudr_purchasing_orders po', [
            'deleted_by' => $deleted_by,
            'deleted_at' => $now,
            'updated_by' => $deleted_by,
            'updated_at' => $now,
        ]);
        return $this->db->getLastErrno() === 0;
    }

    private function hydrateOrder(?array $row): ?PurchasingOrder
    {
        if (empty($row)) {
            return null;
        }
        $row['items'] = $this->getItems((int)$row['purchase_order_id']);
        return new PurchasingOrder((int)$row['purchase_order_id'], $row);
    }
}
