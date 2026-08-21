<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Sales\Order;

use App\Application\Utility\CurrentUserContext;
use App\Application\Utility\Utils;
use App\Domain\Sales\Order\SalesOrder;
use App\Domain\Sales\Order\SalesOrderRepository;

class InDatabaseSalesOrderRepository implements SalesOrderRepository
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
     * InDatabaseSalesOrderRepository constructor.
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
    private function scopeWhere(string $scope, int $companyId, ?int $companyIdParam = null, string $alias = 'o'): void
    {
        $prefix = $alias ? $alias . '.' : '';
        $this->db->where($prefix . 'deleted_by', 0);
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
        $customer_id = $params['customer_id'] ?? 0;
        $contract_id = $params['contract_id'] ?? 0;
        $date_from = $params['order_date_from'] ?? null;
        $date_to = $params['order_date_to'] ?? null;
        $order_source_type = $params['order_source_type'] ?? null;
        $transaction_ticket_id = $params['transaction_ticket_id'] ?? null;
        $buyer_type = $params['buyer_type'] ?? null;
        $companyIdParam = $company_id_param ?? ($params['company_id_param'] ?? null);

        $this->scopeWhere($scope, (int)$companyId, $companyIdParam, 'o');
        if ($status !== 'all') {
            $this->db->where('o.status', $status);
        }
        if (!empty($search)) {
            $this->db->where('(o.sale_order_code LIKE ? OR o.notes LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($customer_id)) {
            $this->db->where('o.customer_id', (int)$customer_id);
        }
        if (!empty($contract_id)) {
            $this->db->where('o.contract_id', (int)$contract_id);
        }
        if (!empty($date_from)) {
            $this->db->where('o.order_date', $date_from, '>=');
        }
        if (!empty($date_to)) {
            $this->db->where('o.order_date', $date_to, '<=');
        }
        if (!empty($order_source_type)) {
            $this->db->where('o.order_source_type', $order_source_type);
        }
        if (!empty($buyer_type)) {
            if ($buyer_type === 'customer') {
                $this->db->where('o.buyer_company_id', 0);
            } elseif ($buyer_type === 'trader') {
                $this->db->where('o.buyer_company_id', 0, '>');
            }
        }
        if (!empty($transaction_ticket_id)) {
            $this->db->join('eudr_sales_order_items oi_filter', 'oi_filter.sale_order_id = o.sale_order_id', 'INNER');
            $this->db->where('oi_filter.transaction_ticket_id', (int)$transaction_ticket_id);
        }
        $total_records = $this->db->getValue('eudr_sales_orders o', 'count(DISTINCT o.sale_order_id)');

        $this->db->pageLimit = $page_limit;
        $this->scopeWhere($scope, (int)$companyId, $companyIdParam, 'o');
        if ($status !== 'all') {
            $this->db->where('o.status', $status);
        }
        if (!empty($search)) {
            $this->db->where('(o.sale_order_code LIKE ? OR o.notes LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($customer_id)) {
            $this->db->where('o.customer_id', (int)$customer_id);
        }
        if (!empty($contract_id)) {
            $this->db->where('o.contract_id', (int)$contract_id);
        }
        if (!empty($date_from)) {
            $this->db->where('o.order_date', $date_from, '>=');
        }
        if (!empty($date_to)) {
            $this->db->where('o.order_date', $date_to, '<=');
        }
        if (!empty($order_source_type)) {
            $this->db->where('o.order_source_type', $order_source_type);
        }
        if (!empty($buyer_type)) {
            if ($buyer_type === 'customer') {
                $this->db->where('o.buyer_company_id', 0);
            } elseif ($buyer_type === 'trader') {
                $this->db->where('o.buyer_company_id', 0, '>');
            }
        }
        if (!empty($transaction_ticket_id)) {
            $this->db->join('eudr_sales_order_items oi_filter', 'oi_filter.sale_order_id = o.sale_order_id', 'INNER');
            $this->db->where('oi_filter.transaction_ticket_id', (int)$transaction_ticket_id);
            $this->db->groupBy('o.sale_order_id');
        }
        $this->db->join('eudr_sales_customers c', 'c.customer_id = o.customer_id', 'LEFT');
        $this->db->join('eudr_companies bc', 'bc.company_id = o.buyer_company_id', 'LEFT');
        $this->db->join('eudr_users bu', 'bu.user_id = o.buyer_user_id', 'LEFT');
        $this->db->orderBy('o.sale_order_id', 'DESC');
        $rows = $this->db->arraybuilder()->paginate(
            'eudr_sales_orders o',
            $page,
            'o.*, c.customer_code, c.customer_name, c.customer_phone, c.customer_email, c.customer_company_name, c.tax_code, c.customer_type, bc.company_name AS buyer_company_name, bc.company_code AS buyer_company_code, bu.full_name AS buyer_user_name'
        );

        $items = [];
        if ($this->db->count > 0) {
            foreach ($rows as $row) {
                $row['items'] = [];
                $items[] = new SalesOrder((int)$row['sale_order_id'], $row);
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
    public function findOrderOfId(int $sale_order_id): ?SalesOrder
    {
        $this->db->where('o.deleted_by', 0);
        $this->db->where('o.sale_order_id', $sale_order_id);
        $this->db->join('eudr_sales_customers c', 'c.customer_id = o.customer_id', 'LEFT');
        $this->db->join('eudr_companies bc', 'bc.company_id = o.buyer_company_id', 'LEFT');
        $this->db->join('eudr_users bu', 'bu.user_id = o.buyer_user_id', 'LEFT');
        $row = $this->db->getOne(
            'eudr_sales_orders o',
            'o.*, c.customer_code, c.customer_name, c.customer_phone, c.customer_email, c.customer_company_name, c.tax_code, c.customer_type, bc.company_name AS buyer_company_name, bc.company_code AS buyer_company_code, bu.full_name AS buyer_user_name'
        );
        if (empty($row)) {
            return null;
        }
        $row['items'] = $this->getItems($sale_order_id);
        return new SalesOrder((int)$row['sale_order_id'], $row);
    }

    /**
     * {@inheritdoc}
     */
    public function findOrderOfIdWithPermission(int $sale_order_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?SalesOrder
    {
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, (int)$companyId, $company_id_param, 'o');
        $this->db->where('o.sale_order_id', $sale_order_id);
        $this->db->join('eudr_sales_customers c', 'c.customer_id = o.customer_id', 'LEFT');
        $this->db->join('eudr_companies bc', 'bc.company_id = o.buyer_company_id', 'LEFT');
        $this->db->join('eudr_users bu', 'bu.user_id = o.buyer_user_id', 'LEFT');
        $row = $this->db->getOne(
            'eudr_sales_orders o',
            'o.*, c.customer_code, c.customer_name, c.customer_phone, c.customer_email, c.customer_company_name, c.tax_code, c.customer_type, bc.company_name AS buyer_company_name, bc.company_code AS buyer_company_code, bu.full_name AS buyer_user_name'
        );
        if (empty($row)) {
            return null;
        }
        $row['items'] = $this->getItems((int)$row['sale_order_id']);
        return new SalesOrder((int)$row['sale_order_id'], $row);
    }

    /**
     * {@inheritdoc}
     */
    public function findOrderOfCode(string $sale_order_code): ?SalesOrder
    {
        $this->db->where('o.deleted_by', 0);
        $this->db->where('o.sale_order_code', $sale_order_code);
        $this->db->join('eudr_sales_customers c', 'c.customer_id = o.customer_id', 'LEFT');
        $this->db->join('eudr_companies bc', 'bc.company_id = o.buyer_company_id', 'LEFT');
        $this->db->join('eudr_users bu', 'bu.user_id = o.buyer_user_id', 'LEFT');
        $row = $this->db->getOne(
            'eudr_sales_orders o',
            'o.*, c.customer_code, c.customer_name, c.customer_phone, c.customer_email, c.customer_company_name, c.tax_code, c.customer_type, bc.company_name AS buyer_company_name, bc.company_code AS buyer_company_code, bu.full_name AS buyer_user_name'
        );
        if (empty($row)) {
            return null;
        }
        $row['items'] = $this->getItems((int)$row['sale_order_id']);
        return new SalesOrder((int)$row['sale_order_id'], $row);
    }

    /**
     * {@inheritdoc}
     */
    public function findOrderOfCodeWithPermission(string $sale_order_code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?SalesOrder
    {
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, (int)$companyId, $company_id_param, 'o');
        $this->db->where('o.sale_order_code', $sale_order_code);
        $this->db->join('eudr_sales_customers c', 'c.customer_id = o.customer_id', 'LEFT');
        $this->db->join('eudr_companies bc', 'bc.company_id = o.buyer_company_id', 'LEFT');
        $this->db->join('eudr_users bu', 'bu.user_id = o.buyer_user_id', 'LEFT');
        $row = $this->db->getOne(
            'eudr_sales_orders o',
            'o.*, c.customer_code, c.customer_name, c.customer_phone, c.customer_email, c.customer_company_name, c.tax_code, c.customer_type, bc.company_name AS buyer_company_name, bc.company_code AS buyer_company_code, bu.full_name AS buyer_user_name'
        );
        if (empty($row)) {
            return null;
        }
        $row['items'] = $this->getItems((int)$row['sale_order_id']);
        return new SalesOrder((int)$row['sale_order_id'], $row);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = 'sord-' . date('ymd') . '-' . Utils::generateRandomString(8);
            $exists = $this->findOrderOfCode($code);
            if (!$exists) {
                break;
            }
        }
        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function createOrder(array $data, array $items): ?SalesOrder
    {
        $companyId = isset($data['company_id']) ? (int)$data['company_id'] : ($this->currentUser->getCompanyId() ?? 0);
        $data['company_id'] = $companyId;
        if (empty($data['sale_order_code'])) {
            $data['sale_order_code'] = $this->generateCode();
        }

        $now = date('Y-m-d H:i:s');
        if (empty($data['created_at'])) {
            $data['created_at'] = $now;
        }

        $this->db->startTransaction();
        $this->db->insert('eudr_sales_orders', $data);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $orderId = (int)$this->db->getInsertId();
        $this->insertItems($orderId, $companyId, $items, $data['created_by'] ?? null, $now);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->commit();
        return $this->findOrderOfId($orderId);
    }

    /**
     * {@inheritdoc}
     */
    public function updateOrderWithPermission(int $sale_order_id, array $data, array $items, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?SalesOrder
    {
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->db->startTransaction();

        $this->scopeWhere($scope, (int)$companyId, $company_id_param, '');
        $this->db->where('sale_order_id', $sale_order_id);
        $this->db->update('eudr_sales_orders', $data);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->where('sale_order_id', $sale_order_id);
        $this->db->delete('eudr_sales_order_items');
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->insertItems($sale_order_id, $companyId, $items, $data['updated_by'] ?? null, date('Y-m-d H:i:s'));
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->commit();
        return $this->findOrderOfIdWithPermission($sale_order_id, $auth_user_id, $scope, $company_id, $company_id_param);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteOrderWithPermission(int $sale_order_id, array $data, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): bool
    {
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, (int)$companyId, $company_id_param, '');
        $this->db->where('sale_order_id', $sale_order_id);
        $this->db->where('status', 'draft');
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_sales_orders', $data);

        if ($this->db->getLastErrno() !== 0) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    private function insertItems(int $orderId, int $companyId, array $items, ?int $userId, string $timestamp): void
    {
        $rows = [];
        foreach ($items as $item) {
            $rows[] = [
                'sale_order_id' => $orderId,
                'company_id' => $companyId,
                'source_type' => (string)($item['source_type'] ?? 'finished_product'),
                'transaction_ticket_id' => isset($item['transaction_ticket_id']) ? (int)$item['transaction_ticket_id'] : null,
                'raw_material_tank_id' => isset($item['raw_material_tank_id']) ? (int)$item['raw_material_tank_id'] : null,
                'product_tank_id' => isset($item['product_tank_id']) ? (int)$item['product_tank_id'] : null,
                'product_type_id' => isset($item['product_type_id']) ? (int)$item['product_type_id'] : null,
                'product_lot_id' => isset($item['product_lot_id']) ? (int)$item['product_lot_id'] : null,
                'rubber_type' => $item['rubber_type'] ?? null,
                'quality_grade' => isset($item['quality_grade']) ? (float)$item['quality_grade'] : null,
                'uom' => (string)($item['uom'] ?? ''),
                'qty_ordered' => (float)($item['qty_ordered'] ?? 0),
                'qty_allocated' => (float)($item['qty_allocated'] ?? 0),
                'qty_shipped' => (float)($item['qty_shipped'] ?? 0),
                'price' => (float)($item['price'] ?? 0),
                'discount_rate' => (float)($item['discount_rate'] ?? 0),
                'surcharge' => (float)($item['surcharge'] ?? 0),
                'currency' => (string)($item['currency'] ?? 'VND'),
                'notes' => $item['notes'] ?? null,
                'created_at' => $timestamp,
                'created_by' => $userId,
            ];
        }

        if (!empty($rows)) {
            $this->db->insertMulti('eudr_sales_order_items', $rows);
        }
    }

    /**
     * {@inheritdoc}
     */
    private function getItems(int $orderId): array
    {
        $this->db->where('oi.sale_order_id', $orderId);
        $this->db->join('eudr_tanks_finished_product tfp', 'tfp.product_tank_id = oi.product_tank_id', 'LEFT');
        $this->db->join('eudr_production_product_types pt', 'pt.product_type_id = oi.product_type_id', 'LEFT');
        $this->db->join('eudr_tanks_raw_material rmt', 'rmt.raw_material_tank_id = oi.raw_material_tank_id', 'LEFT');
        $this->db->join('eudr_transaction_tickets tt', 'tt.transaction_ticket_id = oi.transaction_ticket_id', 'LEFT');
        $this->db->join('eudr_production_product_lots pl', 'pl.product_lot_id = oi.product_lot_id', 'LEFT');
        $rows = $this->db->get('eudr_sales_order_items oi', null, 
        'oi.*, 
        tfp.product_tank_code, tfp.product_tank_name, tfp.capacity, tfp.current_volume, 
        pt.product_type_code, pt.product_type_name, pt.product_weight, pt.product_type_category,
        rmt.raw_material_tank_code, rmt.raw_material_tank_name, rmt.capacity AS raw_material_capacity, rmt.current_volume AS raw_material_current_volume,
        tt.transaction_ticket_code, tt.transaction_ticket_type, tt.contract_code AS ticket_contract_code,
        tt.seller_name AS ticket_seller_name, tt.buyer_name AS ticket_buyer_name,
        tt.latex_weight AS ticket_latex_weight, tt.scrap_rubber_weight AS ticket_scrap_rubber_weight,
        pl.product_lot_code, pl.grade AS product_lot_grade, pl.total_blocks AS product_lot_total_blocks, pl.total_weight AS product_lot_total_weight, pl.status AS product_lot_status
        '
        );

        $items = [];
        foreach ($rows as $row) {
            $item = [
                'sale_order_item_id' => (int)$row['sale_order_item_id'],
                'sale_order_id' => (int)$row['sale_order_id'],
                'company_id' => (int)$row['company_id'],
                'source_type' => $row['source_type'] ?? 'finished_product',
                'transaction_ticket_id' => isset($row['transaction_ticket_id']) ? (int)$row['transaction_ticket_id'] : null,
                'raw_material_tank_id' => isset($row['raw_material_tank_id']) ? (int)$row['raw_material_tank_id'] : null,
                'product_tank_id' => isset($row['product_tank_id']) ? (int)$row['product_tank_id'] : null,
                'product_type_id' => isset($row['product_type_id']) ? (int)$row['product_type_id'] : null,
                'product_lot_id' => isset($row['product_lot_id']) ? (int)$row['product_lot_id'] : null,
                'rubber_type' => $row['rubber_type'] ?? null,
                'quality_grade' => isset($row['quality_grade']) ? (float)$row['quality_grade'] : null,
                // Finished product tank info
                'product_tank_code' => $row['product_tank_code'] ?? null,
                'product_tank_name' => $row['product_tank_name'] ?? null,
                'product_type_code' => $row['product_type_code'] ?? null,
                'product_type_name' => $row['product_type_name'] ?? null,
                'product_weight' => isset($row['product_weight']) ? (float)$row['product_weight'] : null,
                'product_type_category' => $row['product_type_category'] ?? null,
                // Raw material tank info
                'raw_material_tank_code' => $row['raw_material_tank_code'] ?? null,
                'raw_material_tank_name' => $row['raw_material_tank_name'] ?? null,
                // Transaction ticket info
                'transaction_ticket_code' => $row['transaction_ticket_code'] ?? null,
                'ticket_contract_code' => $row['ticket_contract_code'] ?? null,
                'ticket_seller_name' => $row['ticket_seller_name'] ?? null,
                'ticket_buyer_name' => $row['ticket_buyer_name'] ?? null,
                // Product lot info
                'product_lot_code' => $row['product_lot_code'] ?? null,
                'product_lot_grade' => $row['product_lot_grade'] ?? null,
                'product_lot_total_blocks' => isset($row['product_lot_total_blocks']) ? (int)$row['product_lot_total_blocks'] : null,
                'product_lot_total_weight' => isset($row['product_lot_total_weight']) ? (float)$row['product_lot_total_weight'] : null,
                'product_lot_status' => $row['product_lot_status'] ?? null,
                // Quantities
                'uom' => $row['uom'],
                'qty_ordered' => (float)$row['qty_ordered'],
                'qty_allocated' => (float)($row['qty_allocated'] ?? 0),
                'qty_shipped' => (float)($row['qty_shipped'] ?? 0),
                'price' => (float)$row['price'],
                'discount_rate' => (float)($row['discount_rate'] ?? 0),
                'surcharge' => (float)($row['surcharge'] ?? 0),
                'currency' => $row['currency'],
                'notes' => $row['notes'],
            ];
            $items[] = $item;
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function findOrdersByTransactionTicket(int $transaction_ticket_id, array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array
    {
        $params['transaction_ticket_id'] = $transaction_ticket_id;
        return $this->findAll($params, $auth_user_id, $scope, $company_id, $company_id_param);
    }

    /**
     * {@inheritdoc}
     */
    public function findPurchaseOrders(array $params = [], ?int $auth_user_id = null, ?int $buyer_company_id = null): array
    {
        $buyerCompanyId = $buyer_company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $search = $params['search'] ?? '';
        $status = $params['status'] ?? 'all';
        $date_from = $params['order_date_from'] ?? null;
        $date_to = $params['order_date_to'] ?? null;
        $order_source_type = $params['order_source_type'] ?? null;
        $seller_company_id = $params['seller_company_id'] ?? null;

        // Count total
        $this->db->where('o.deleted_by', 0);
        $this->db->where('o.buyer_company_id', (int)$buyerCompanyId);
        if ($status !== 'all') {
            $this->db->where('o.status', $status);
        }
        if (!empty($search)) {
            $this->db->where('(o.sale_order_code LIKE ? OR o.notes LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($date_from)) {
            $this->db->where('o.order_date', $date_from, '>=');
        }
        if (!empty($date_to)) {
            $this->db->where('o.order_date', $date_to, '<=');
        }
        if (!empty($order_source_type)) {
            $this->db->where('o.order_source_type', $order_source_type);
        }
        if (!empty($seller_company_id)) {
            $this->db->where('o.company_id', (int)$seller_company_id);
        }
        $total_records = $this->db->getValue('eudr_sales_orders o', 'count(*)');

        // Paginated results
        $this->db->pageLimit = $page_limit;
        $this->db->where('o.deleted_by', 0);
        $this->db->where('o.buyer_company_id', (int)$buyerCompanyId);
        if ($status !== 'all') {
            $this->db->where('o.status', $status);
        }
        if (!empty($search)) {
            $this->db->where('(o.sale_order_code LIKE ? OR o.notes LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($date_from)) {
            $this->db->where('o.order_date', $date_from, '>=');
        }
        if (!empty($date_to)) {
            $this->db->where('o.order_date', $date_to, '<=');
        }
        if (!empty($order_source_type)) {
            $this->db->where('o.order_source_type', $order_source_type);
        }
        if (!empty($seller_company_id)) {
            $this->db->where('o.company_id', (int)$seller_company_id);
        }
        $this->db->join('eudr_sales_customers c', 'c.customer_id = o.customer_id', 'LEFT');
        $this->db->join('eudr_companies sc', 'sc.company_id = o.company_id', 'LEFT');
        $this->db->join('eudr_users bu', 'bu.user_id = o.buyer_user_id', 'LEFT');
        $this->db->orderBy('o.sale_order_id', 'DESC');
        $rows = $this->db->arraybuilder()->paginate(
            'eudr_sales_orders o',
            $page,
            'o.*, c.customer_code, c.customer_name, c.customer_phone, c.customer_email, c.customer_company_name, c.tax_code, c.customer_type, sc.company_name AS seller_company_name, sc.company_code AS seller_company_code, bu.full_name AS buyer_user_name'
        );

        $items = [];
        if ($this->db->count > 0) {
            foreach ($rows as $row) {
                $row['items'] = [];
                $row['buyer_company_name'] = null;
                $row['buyer_company_code'] = null;
                $items[] = new SalesOrder((int)$row['sale_order_id'], $row);
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
}
