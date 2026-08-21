<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Sales\Issue;

use App\Application\Utility\CurrentUserContext;
use App\Application\Utility\Utils;
use App\Domain\Sales\Issue\SalesIssue;
use App\Domain\Sales\Issue\SalesIssueRepository;

class InDatabaseSalesIssueRepository implements SalesIssueRepository
{
    /**
     * @var MysqliDb
     */
    private $db;

    /**
     * @var CurrentUserContext
     */
    private $currentUser;

    /**
     * InDatabaseSalesIssueRepository constructor.
     *
     * @param MysqliDb $db
     * @param CurrentUserContext $currentUserContext
     */
    public function __construct(\MysqliDb $db, CurrentUserContext $currentUserContext)
    {
        $this->db = $db;
        $this->currentUser = $currentUserContext;
    }

    /**
     * Apply scope-based filtering (self/own/all).
     */
    private function scopeWhere(string $scope, int $companyId, ?int $companyIdParam = null, string $alias = 'i'): void
    {
        $prefix = $alias ? $alias . '.' : '';
        if ($scope === 'self' || $scope === 'own') {
            $this->db->where($prefix . 'company_id', $companyId);
        } elseif ($scope === 'all' && !empty($companyIdParam)) {
            $this->db->where($prefix . 'company_id', $companyIdParam);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array
    {
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $search = $params['search'] ?? '';
        $status = $params['status'] ?? 'all';
        $orderId = $params['sale_order_id'] ?? 0;
        $date_from = $params['issue_date_from'] ?? null;
        $date_to = $params['issue_date_to'] ?? null;
        $companyIdParam = $company_id_param ?? ($params['company_id_param'] ?? null);

        $this->scopeWhere($scope, (int)$companyId, $companyIdParam, 'i');
        $this->db->where('i.deleted_at', null, 'IS');
        if ($status !== 'all') {
            $this->db->where('i.status', $status);
        }
        if (!empty($search)) {
            $this->db->where('(i.issue_code LIKE ? OR i.notes LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($orderId)) {
            $this->db->where('i.sale_order_id', (int)$orderId);
        }
        if (!empty($date_from)) {
            $this->db->where('i.issue_date', $date_from, '>=');
        }
        if (!empty($date_to)) {
            $this->db->where('i.issue_date', $date_to, '<=');
        }
        $total_records = $this->db->getValue('eudr_sales_issues i', 'count(*)');

        $this->db->pageLimit = $page_limit;
        $this->scopeWhere($scope, (int)$companyId, $companyIdParam, 'i');
        $this->db->where('i.deleted_at', null, 'IS');
        if ($status !== 'all') {
            $this->db->where('i.status', $status);
        }
        if (!empty($search)) {
            $this->db->where('(i.issue_code LIKE ? OR i.notes LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($orderId)) {
            $this->db->where('i.sale_order_id', (int)$orderId);
        }
        if (!empty($date_from)) {
            $this->db->where('i.issue_date', $date_from, '>=');
        }
        if (!empty($date_to)) {
            $this->db->where('i.issue_date', $date_to, '<=');
        }
        $this->db->orderBy('i.issue_id', 'DESC');
        $rows = $this->db->arraybuilder()->paginate('eudr_sales_issues i', $page);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($rows as $row) {
                $row['items'] = [];
                $items[] = new SalesIssue((int)$row['issue_id'], $row);
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
     * {@inheritdoc}
     */
    public function findIssueOfCode(string $issue_code): ?SalesIssue
    {
        $this->db->where('i.issue_code', $issue_code);
        $row = $this->db->getOne('eudr_sales_issues i', 'i.*');
        if (empty($row)) {
            return null;
        }
        $row['items'] = $this->getItems((int)$row['issue_id']);
        return new SalesIssue((int)$row['issue_id'], $row);
    }

    /**
     * {@inheritdoc}
     */
    public function findIssueOfCodeWithPermission(string $issue_code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?SalesIssue
    {
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, (int)$companyId, $company_id_param, 'i');
        $this->db->where('i.issue_code', $issue_code);
        $this->db->where('i.deleted_at', null, 'IS');
        $row = $this->db->getOne('eudr_sales_issues i', 'i.*');
        if (empty($row)) {
            return null;
        }
        $row['items'] = $this->getItems((int)$row['issue_id']);
        return new SalesIssue((int)$row['issue_id'], $row);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = 'issu-' . date('ymd') . '-' . Utils::generateRandomString(8);
            $exists = $this->findIssueOfCode($code);
            if (!$exists) {
                break;
            }
        }
        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function createIssue(array $data, array $items): ?SalesIssue
    {
        $companyId = isset($data['company_id']) ? (int)$data['company_id'] : ($this->currentUser->getCompanyId() ?? 0);
        $data['company_id'] = $companyId;
        if (empty($data['issue_code'])) {
            $data['issue_code'] = $this->generateCode();
        }
        if (empty($data['issue_date'])) {
            $data['issue_date'] = date('Y-m-d H:i:s');
        }
        $now = date('Y-m-d H:i:s');
        if (empty($data['created_at'])) {
            $data['created_at'] = $now;
        }

        $this->db->startTransaction();
        $this->db->insert('eudr_sales_issues', $data);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $issueId = (int)$this->db->getInsertId();
        $createdBy = $data['created_by'] ?? null;
        $this->insertItems($issueId, $companyId, $items, $createdBy, $now);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->commit();
        return $this->findIssueOfCodeWithPermission($data['issue_code'], null, 'all', $companyId, $companyId);
    }

    /**
     * {@inheritdoc}
     */
    public function updateIssueWithPermission(int $issue_id, array $data, array $items, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?SalesIssue
    {
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $now = date('Y-m-d H:i:s');

        $this->db->startTransaction();

        $this->scopeWhere($scope, (int)$companyId, $company_id_param, '');
        $this->db->where('issue_id', $issue_id);
        $this->db->where('deleted_at', null, 'IS');
        $this->db->update('eudr_sales_issues', $data);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->where('issue_id', $issue_id);
        $issueItemIds = $this->db->getValue('eudr_sales_issue_items', 'issue_item_id', null);
        if (!empty($issueItemIds)) {
            $this->db->where('issue_item_id', $issueItemIds, 'IN');
            $this->db->delete('eudr_sales_issue_allocations');
        }

        $this->db->where('issue_id', $issue_id);
        $this->db->delete('eudr_sales_issue_items');
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->insertItems($issue_id, $companyId, $items, $data['updated_by'] ?? null, $now);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->commit();

        $this->db->where('issue_id', $issue_id);
        $issueCode = (string)$this->db->getValue('eudr_sales_issues', 'issue_code');
        return $this->findIssueOfCodeWithPermission($issueCode, $auth_user_id, $scope, $companyId, $company_id_param);
    }

    /**
     * {@inheritdoc}
     */
    public function confirmIssueWithPermission(int $issue_id, array $data, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?SalesIssue
    {
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $now = date('Y-m-d H:i:s');

        $this->db->startTransaction();

        $this->scopeWhere($scope, (int)$companyId, $company_id_param, '');
        $this->db->where('issue_id', $issue_id);
        $this->db->where('deleted_at', null, 'IS');
        $this->db->where('status', 'draft');
        $updateData = array_merge($data, [
            'status' => 'issued',
            'updated_at' => $now,
        ]);
        $this->db->update('eudr_sales_issues', $updateData);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $itemRows = $this->getItems($issue_id);
        $saleOrderItemIds = array_values(array_unique(array_map(static fn($row) => (int)($row['sale_order_item_id'] ?? 0), $itemRows)));
        if (!empty($saleOrderItemIds)) {
            $this->updateOrderShippedQuantities($saleOrderItemIds, null);
            if ($this->db->getLastErrno() !== 0) {
                $this->db->rollback();
                return null;
            }
        }

        // Deduct raw material tank volumes for allocations using raw_material_tank_id
        $this->deductRawMaterialTankVolumes($issue_id);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        // Transfer product lot ownership to buyer company
        $this->transferProductLotOwnership($issue_id);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->commit();

        $this->db->where('issue_id', $issue_id);
        $issueCode = (string)$this->db->getValue('eudr_sales_issues', 'issue_code');
        return $this->findIssueOfCodeWithPermission($issueCode, $auth_user_id, $scope, $companyId, $company_id_param);
    }

    /**
     * {@inheritdoc}
     */
    public function cancelIssueWithPermission(int $issue_id, array $data, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?SalesIssue
    {
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $now = date('Y-m-d H:i:s');

        $this->db->startTransaction();
        $this->scopeWhere($scope, (int)$companyId, $company_id_param, '');
        $this->db->where('issue_id', $issue_id);
        $this->db->where('deleted_at', null, 'IS');
        $this->db->where('status', 'issued');
        $updateData = array_merge($data, [
            'status' => 'cancelled',
            'updated_at' => $now,
        ]);
        $this->db->update('eudr_sales_issues', $updateData);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $itemRows = $this->getItems($issue_id);
        $saleOrderItemIds = array_values(array_unique(array_map(static fn($row) => (int)($row['sale_order_item_id'] ?? 0), $itemRows)));
        if (!empty($saleOrderItemIds)) {
            $this->updateOrderShippedQuantities($saleOrderItemIds, null);
            if ($this->db->getLastErrno() !== 0) {
                $this->db->rollback();
                return null;
            }
        }

        // Restore raw material tank volumes when cancelling an issue.
        $this->restoreRawMaterialTankVolumes($issue_id);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        // Restore product lot ownership when cancelling an issue
        $this->restoreProductLotOwnership($issue_id);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->commit();

        $this->db->where('issue_id', $issue_id);
        $issueCode = (string)$this->db->getValue('eudr_sales_issues', 'issue_code');
        return $this->findIssueOfCodeWithPermission($issueCode, $auth_user_id, $scope, $companyId, $company_id_param);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteIssueWithPermission(int $issue_id, array $data, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): bool
    {
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, (int)$companyId, $company_id_param, '');
        $this->db->where('issue_id', $issue_id);
        $this->db->where('status', 'draft');
        $this->db->where('deleted_at', null, 'IS');
        $this->db->update('eudr_sales_issues', $data);
        if ($this->db->getLastErrno() !== 0) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    private function getItems(int $issueId): array
    {
        $this->db->where('ii.issue_id', $issueId);
        $this->db->join('eudr_sales_order_items oi', 'oi.sale_order_item_id = ii.sale_order_item_id', 'LEFT');
        $this->db->join('eudr_production_product_lots pl', 'pl.product_lot_id = ii.product_lot_id', 'LEFT');
        $this->db->join('eudr_production_product_types pt', 'pt.product_type_id = oi.product_type_id', 'LEFT');
        $this->db->join('eudr_tanks_finished_product tfp', 'tfp.product_tank_id = oi.product_tank_id', 'LEFT');
        $itemRows = $this->db->get('eudr_sales_issue_items ii', null,
            'ii.*,
            oi.product_type_id, oi.product_tank_id AS order_product_tank_id,
            oi.rubber_type, oi.quality_grade,
            pt.product_type_code, pt.product_type_name, pt.product_type_category,
            tfp.product_tank_code, tfp.product_tank_name,
            pl.product_lot_code, pl.grade AS product_lot_grade,
            pl.total_blocks AS product_lot_total_blocks, pl.total_weight AS product_lot_total_weight,
            pl.status AS product_lot_status'
        );
        if (empty($itemRows)) {
            return [];
        }

        $issueItemIds = array_values(array_unique(array_map(static fn($row) => (int)$row['issue_item_id'], $itemRows)));
        $allocationsByItem = $this->getAllocations($issueItemIds);

        $items = [];
        foreach ($itemRows as $row) {
            $issueItemId = (int)$row['issue_item_id'];
            $items[] = [
                'issue_item_id' => $issueItemId,
                'issue_id' => (int)$row['issue_id'],
                'sale_order_item_id' => (int)$row['sale_order_item_id'],
                'company_id' => (int)$row['company_id'],
                'source_type' => $row['source_type'] ?? 'finished_product',
                'product_id' => (int)$row['product_id'],
                'product_lot_id' => isset($row['product_lot_id']) ? (int)$row['product_lot_id'] : null,
                'uom' => $row['uom'],
                'qty_issued' => (float)$row['qty_issued'],
                'price' => isset($row['price']) ? (float)$row['price'] : null,
                'currency' => $row['currency'],
                'notes' => $row['notes'],
                // Product type info (from order item)
                'product_type_id' => isset($row['product_type_id']) ? (int)$row['product_type_id'] : null,
                'product_type_code' => $row['product_type_code'] ?? null,
                'product_type_name' => $row['product_type_name'] ?? null,
                'product_type_category' => $row['product_type_category'] ?? null,
                // Product tank info (from order item)
                'product_tank_code' => $row['product_tank_code'] ?? null,
                'product_tank_name' => $row['product_tank_name'] ?? null,
                // Rubber & grade info (from order item)
                'rubber_type' => $row['rubber_type'] ?? null,
                'quality_grade' => isset($row['quality_grade']) ? (float)$row['quality_grade'] : null,
                // Product lot info
                'product_lot_code' => $row['product_lot_code'] ?? null,
                'product_lot_grade' => $row['product_lot_grade'] ?? null,
                'product_lot_total_blocks' => isset($row['product_lot_total_blocks']) ? (int)$row['product_lot_total_blocks'] : null,
                'product_lot_total_weight' => isset($row['product_lot_total_weight']) ? (float)$row['product_lot_total_weight'] : null,
                'product_lot_status' => $row['product_lot_status'] ?? null,
                'allocations' => $allocationsByItem[$issueItemId] ?? [],
            ];
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    private function getAllocations(array $issueItemIds): array
    {
        if (empty($issueItemIds)) {
            return [];
        }

        $this->db->where('sia.issue_item_id', $issueItemIds, 'IN');
        $this->db->join('eudr_tanks_raw_material rmt', 'rmt.raw_material_tank_id = sia.raw_material_tank_id', 'LEFT');
        $this->db->join('eudr_production_product_lots pl', 'pl.product_lot_id = sia.lot_id', 'LEFT');
        $rows = $this->db->get('eudr_sales_issue_allocations sia', null,
            'sia.*, rmt.raw_material_tank_code, rmt.raw_material_tank_name,
            pl.product_lot_code, pl.grade AS product_lot_grade,
            pl.total_blocks AS product_lot_total_blocks, pl.total_weight AS product_lot_total_weight,
            pl.status AS product_lot_status'
        );
        $result = [];
        foreach ($rows as $row) {
            $issueItemId = (int)$row['issue_item_id'];
            if (!isset($result[$issueItemId])) {
                $result[$issueItemId] = [];
            }
            $result[$issueItemId][] = [
                'issue_allocation_id' => (int)$row['issue_allocation_id'],
                'issue_item_id' => (int)$row['issue_item_id'],
                'sale_order_item_id' => (int)$row['sale_order_item_id'],
                'product_tank_id' => isset($row['product_tank_id']) ? (int)$row['product_tank_id'] : null,
                'raw_material_tank_id' => isset($row['raw_material_tank_id']) ? (int)$row['raw_material_tank_id'] : null,
                'transaction_ticket_id' => isset($row['transaction_ticket_id']) ? (int)$row['transaction_ticket_id'] : null,
                'raw_material_tank_code' => $row['raw_material_tank_code'] ?? null,
                'raw_material_tank_name' => $row['raw_material_tank_name'] ?? null,
                'lot_id' => isset($row['lot_id']) ? (int)$row['lot_id'] : null,
                'product_lot_code' => $row['product_lot_code'] ?? null,
                'product_lot_grade' => $row['product_lot_grade'] ?? null,
                'product_lot_total_blocks' => isset($row['product_lot_total_blocks']) ? (int)$row['product_lot_total_blocks'] : null,
                'product_lot_total_weight' => isset($row['product_lot_total_weight']) ? (float)$row['product_lot_total_weight'] : null,
                'product_lot_status' => $row['product_lot_status'] ?? null,
                'qty_issued' => (float)$row['qty_issued'],
                'weight_issued' => isset($row['weight_issued']) ? (float)$row['weight_issued'] : null,
                'notes' => $row['notes'],
            ];
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    private function insertItems(int $issueId, int $companyId, array $items, ?int $userId, string $timestamp): void
    {
        foreach ($items as $item) {
            $row = [
                'issue_id' => $issueId,
                'sale_order_item_id' => (int)($item['sale_order_item_id'] ?? 0),
                'company_id' => $companyId,
                'source_type' => (string)($item['source_type'] ?? 'finished_product'),
                'product_id' => (int)($item['product_id'] ?? 0),
                'product_lot_id' => isset($item['product_lot_id']) ? (int)$item['product_lot_id'] : null,
                'uom' => (string)($item['uom'] ?? ''),
                'qty_issued' => (float)($item['qty_issued'] ?? 0),
                'price' => isset($item['price']) ? (float)$item['price'] : null,
                'currency' => $item['currency'] ?? null,
                'notes' => $item['notes'] ?? null,
                'created_at' => $timestamp,
                'created_by' => $userId,
            ];

            $this->db->insert('eudr_sales_issue_items', $row);
            if ($this->db->getLastErrno() !== 0) {
                return;
            }

            $issueItemId = (int)$this->db->getInsertId();
            $allocations = $item['allocations'] ?? [];
            if (!empty($allocations)) {
                $this->insertAllocations($issueItemId, (int)$row['sale_order_item_id'], $allocations, $timestamp, $userId);
                if ($this->db->getLastErrno() !== 0) {
                    return;
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    private function insertAllocations(int $issueItemId, int $saleOrderItemId, array $allocations, string $timestamp, ?int $userId): void
    {
        $rows = [];
        foreach ($allocations as $allocation) {
            $rows[] = [
                'issue_item_id' => $issueItemId,
                'sale_order_item_id' => $saleOrderItemId,
                'product_tank_id' => isset($allocation['product_tank_id']) ? (int)$allocation['product_tank_id'] : null,
                'raw_material_tank_id' => isset($allocation['raw_material_tank_id']) ? (int)$allocation['raw_material_tank_id'] : null,
                'transaction_ticket_id' => isset($allocation['transaction_ticket_id']) ? (int)$allocation['transaction_ticket_id'] : null,
                'lot_id' => isset($allocation['lot_id']) ? (int)$allocation['lot_id'] : null,
                'qty_issued' => (float)($allocation['qty_issued'] ?? 0),
                'weight_issued' => isset($allocation['weight_issued']) ? (float)$allocation['weight_issued'] : null,
                'notes' => $allocation['notes'] ?? null,
                'created_at' => $timestamp,
                'created_by' => $userId,
            ];
        }

        if (!empty($rows)) {
            $this->db->insertMulti('eudr_sales_issue_allocations', $rows);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIssuedTotalsForOrderItems(array $saleOrderItemIds, ?int $excludeIssueId = null): array
    {
        if (empty($saleOrderItemIds)) {
            return [];
        }

        $this->db->join('eudr_sales_issues i', 'i.issue_id = ii.issue_id', 'INNER');
        $this->db->where('i.status', 'issued');
        $this->db->where('i.deleted_at', null, 'IS');
        $this->db->where('ii.sale_order_item_id', $saleOrderItemIds, 'IN');
        if (!empty($excludeIssueId)) {
            $this->db->where('i.issue_id', $excludeIssueId, '!=');
        }
        $this->db->groupBy('ii.sale_order_item_id');
        $rows = $this->db->get('eudr_sales_issue_items ii', null, 'ii.sale_order_item_id, SUM(ii.qty_issued) as total_qty');

        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row['sale_order_item_id']] = (float)$row['total_qty'];
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    private function updateOrderShippedQuantities(array $saleOrderItemIds, ?int $excludeIssueId = null): void
    {
        $totals = $this->getIssuedTotalsForOrderItems($saleOrderItemIds, $excludeIssueId);
        foreach ($saleOrderItemIds as $itemId) {
            $qty = $totals[$itemId] ?? 0;
            $this->db->where('sale_order_item_id', $itemId);
            $this->db->update('eudr_sales_order_items', ['qty_shipped' => $qty]);
        }
    }

    /**
     * Deduct raw material tank volumes for allocations that use raw_material_tank_id.
     * Called during issue confirmation.
     *
     * @param int $issueId
     */
    private function deductRawMaterialTankVolumes(int $issueId): void
    {
        $this->db->join('eudr_sales_issue_items ii', 'ii.issue_item_id = sia.issue_item_id', 'INNER');
        $this->db->where('ii.issue_id', $issueId);
        $this->db->where('sia.raw_material_tank_id', 0, '>');
        $this->db->groupBy('sia.raw_material_tank_id');
        $rows = $this->db->get(
            'eudr_sales_issue_allocations sia',
            null,
            'sia.raw_material_tank_id, SUM(sia.qty_issued) as total_qty'
        );

        foreach ($rows as $row) {
            $tankId = (int)$row['raw_material_tank_id'];
            $totalQty = (float)$row['total_qty'];
            if ($tankId > 0 && $totalQty > 0) {
                $this->db->where('raw_material_tank_id', $tankId);
                $this->db->update('eudr_tanks_raw_material', [
                    'current_volume' => $this->db->dec($totalQty),
                ]);
            }
        }
    }

    /**
     * Restore raw material tank volumes when cancelling an issue.
     *
     * @param int $issueId
     */
    private function restoreRawMaterialTankVolumes(int $issueId): void
    {
        $this->db->join('eudr_sales_issue_items ii', 'ii.issue_item_id = sia.issue_item_id', 'INNER');
        $this->db->where('ii.issue_id', $issueId);
        $this->db->where('sia.raw_material_tank_id', 0, '>');
        $this->db->groupBy('sia.raw_material_tank_id');
        $rows = $this->db->get(
            'eudr_sales_issue_allocations sia',
            null,
            'sia.raw_material_tank_id, SUM(sia.qty_issued) as total_qty'
        );

        foreach ($rows as $row) {
            $tankId = (int)$row['raw_material_tank_id'];
            $totalQty = (float)$row['total_qty'];
            if ($tankId > 0 && $totalQty > 0) {
                $this->db->where('raw_material_tank_id', $tankId);
                $this->db->update('eudr_tanks_raw_material', [
                    'current_volume' => $this->db->inc($totalQty),
                ]);
            }
        }
    }

    /**
     * Transfer product lot ownership to buyer company when issue is confirmed.
     * Looks up product_lot_id from order items linked to this issue,
     * then sets owner_company_id to the order's buyer_company_id.
     */
    private function transferProductLotOwnership(int $issueId): void
    {
        // Get the sale_order_id from the issue
        $this->db->where('issue_id', $issueId);
        $issue = $this->db->getOne('eudr_sales_issues', 'sale_order_id');
        if (empty($issue)) {
            return;
        }
        $saleOrderId = (int)$issue['sale_order_id'];

        // Get buyer_company_id and buyer_user_id from the order
        $this->db->where('sale_order_id', $saleOrderId);
        $order = $this->db->getOne('eudr_sales_orders', 'buyer_company_id, buyer_user_id');
        $buyerCompanyId = (int)($order['buyer_company_id'] ?? 0);
        $buyerUserId = (int)($order['buyer_user_id'] ?? 0);
        if ($buyerCompanyId <= 0) {
            return; // Selling to customer, not B2B — no ownership transfer
        }

        // Get product_lot_ids from issue items directly (using issue item's own product_lot_id)
        $this->db->where('issue_id', $issueId);
        $this->db->where('product_lot_id', 0, '>');
        $this->db->groupBy('product_lot_id');
        $rows = $this->db->get('eudr_sales_issue_items', null, 'product_lot_id');

        // Fallback: also check allocations lot_id for backward compatibility
        $this->db->join('eudr_sales_issue_items ii', 'ii.issue_item_id = sia.issue_item_id', 'INNER');
        $this->db->where('ii.issue_id', $issueId);
        $this->db->where('sia.lot_id', 0, '>');
        $this->db->groupBy('sia.lot_id');
        $allocRows = $this->db->get('eudr_sales_issue_allocations sia', null, 'sia.lot_id AS product_lot_id');

        $lotIds = [];
        foreach ($rows as $row) {
            $lotIds[] = (int)$row['product_lot_id'];
        }
        foreach ($allocRows as $row) {
            $lotId = (int)$row['product_lot_id'];
            if ($lotId > 0 && !in_array($lotId, $lotIds)) {
                $lotIds[] = $lotId;
            }
        }

        if (!empty($lotIds)) {
            $this->db->where('product_lot_id', $lotIds, 'IN');
            $updateData = [
                'owner_company_id' => $buyerCompanyId,
                'status' => 'confirmed', // Reset to confirmed under new owner
            ];
            if ($buyerUserId > 0) {
                $updateData['owner_id'] = $buyerUserId;
            }
            $this->db->update('eudr_production_product_lots', $updateData);
        }
    }

    /**
     * Restore product lot ownership to seller company when issue is cancelled.
     */
    private function restoreProductLotOwnership(int $issueId): void
    {
        // Get the sale_order_id from the issue
        $this->db->where('issue_id', $issueId);
        $issue = $this->db->getOne('eudr_sales_issues', 'sale_order_id, created_by');
        if (empty($issue)) {
            return;
        }
        $saleOrderId = (int)$issue['sale_order_id'];
        $sellerUserId = (int)($issue['created_by'] ?? 0);

        // Get seller company_id from the order
        $this->db->where('sale_order_id', $saleOrderId);
        $order = $this->db->getOne('eudr_sales_orders', 'company_id, buyer_company_id, created_by');
        $sellerCompanyId = (int)($order['company_id'] ?? 0);
        $buyerCompanyId = (int)($order['buyer_company_id'] ?? 0);
        if ($buyerCompanyId <= 0 || $sellerCompanyId <= 0) {
            return;
        }

        // Use created_by from order if we don't have seller_user_id from issue
        if ($sellerUserId <= 0) {
            $sellerUserId = (int)($order['created_by'] ?? 0);
        }

        // Get product_lot_ids from issue items directly
        $this->db->where('issue_id', $issueId);
        $this->db->where('product_lot_id', 0, '>');
        $this->db->groupBy('product_lot_id');
        $rows = $this->db->get('eudr_sales_issue_items', null, 'product_lot_id');

        // Fallback: also check allocations lot_id for backward compatibility
        $this->db->join('eudr_sales_issue_items ii', 'ii.issue_item_id = sia.issue_item_id', 'INNER');
        $this->db->where('ii.issue_id', $issueId);
        $this->db->where('sia.lot_id', 0, '>');
        $this->db->groupBy('sia.lot_id');
        $allocRows = $this->db->get('eudr_sales_issue_allocations sia', null, 'sia.lot_id AS product_lot_id');

        $lotIds = [];
        foreach ($rows as $row) {
            $lotIds[] = (int)$row['product_lot_id'];
        }
        foreach ($allocRows as $row) {
            $lotId = (int)$row['product_lot_id'];
            if ($lotId > 0 && !in_array($lotId, $lotIds)) {
                $lotIds[] = $lotId;
            }
        }

        if (!empty($lotIds)) {
            $this->db->where('product_lot_id', $lotIds, 'IN');
            $updateData = [
                'owner_company_id' => $sellerCompanyId,
                'status' => 'confirmed',
            ];
            if ($sellerUserId > 0) {
                $updateData['owner_id'] = $sellerUserId;
            }
            $this->db->update('eudr_production_product_lots', $updateData);
        }
    }
}
