<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\FinishedGoodsReceipt;

use App\Domain\FinishedGoodsReceipt\FinishedGoodsReceipt;
use App\Domain\FinishedGoodsReceipt\FinishedGoodsReceiptNotFoundException;
use App\Domain\FinishedGoodsReceipt\FinishedGoodsReceiptRepository;
use App\Application\Utility\CurrentUserContext;
use App\Application\Utility\Utils;

class InDatabaseFinishedGoodsReceiptRepository implements FinishedGoodsReceiptRepository
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
     * InDatabaseFinishedGoodsReceiptRepository constructor.
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
     * Apply scope-based filtering (self/own/all) using company_id.
     */
    private function scopeWhere(string $scope, int $authUserId, int $companyId, ?int $companyIdParam = null, string $alias = 'fgr'): void
    {
        $prefix = $alias ? $alias . '.' : '';
        $this->db->where($prefix . 'deleted_by', 0);

        if ($scope === 'self') {
            $this->db->where($prefix . 'created_by', $authUserId);
            $this->db->where($prefix . 'company_id', $companyId);
        } elseif ($scope === 'own') {
            $this->db->where($prefix . 'company_id', $companyId);
        } elseif ($scope === 'all' && !empty($companyIdParam)) {
            $this->db->where($prefix . 'company_id', $companyIdParam);
        }
    }

    /**
     * Hydrate a FinishedGoodsReceipt with joined metadata.
     */
    private function hydrateFinishedGoodsReceipt(array $data): FinishedGoodsReceipt
    {
        return new FinishedGoodsReceipt((int)$data['finished_goods_receipt_id'], $data);
    }

    /**
     * {@inheritdoc}
     */
    public function findAll($params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $search = $params['search'] ?? '';
        $production_order_id = $params['production_order_id'] ?? 0;
        $product_tank_id = $params['product_tank_id'] ?? 0;
        $product_type_category = $params['product_type_category'] ?? 'all';
        $status = $params['status'] ?? 'all';
        $created_date_from = $params['created_date_from'] ?? null;
        $created_date_to = $params['created_date_to'] ?? null;
        $companyIdParam = $company_id_param ?? 0;

        // Count total records
        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'fgr');
        if (!empty($search)) {
            $this->db->where('(fgr.finished_goods_receipt_name LIKE ? OR fgr.finished_goods_receipt_code LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($production_order_id)) {
            $this->db->where('fgr.production_order_id', $production_order_id);
        }
        if (!empty($product_tank_id)) {
            $this->db->where('fgr.product_tank_id', $product_tank_id);
        }
        if ($product_type_category !== 'all') {
            $this->db->where('fgr.product_type_category', $product_type_category);
        }
        if ($status !== 'all') {
            $this->db->where('fgr.status', $status);
        }
        if (!empty($created_date_from)) {
            $this->db->where('DATE(fgr.created_at)', $created_date_from, '>=');
        }
        if (!empty($created_date_to)) {
            $this->db->where('DATE(fgr.created_at)', $created_date_to, '<=');
        }
        $total_records = (int)$this->db->getValue('eudr_tanks_finished_goods_receipts fgr', 'count(*)');


        // Set pagination
        $this->db->pageLimit = $page_limit;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'fgr');
        if (!empty($search)) {
            $this->db->where('(fgr.finished_goods_receipt_name LIKE ? OR fgr.finished_goods_receipt_code LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($production_order_id)) {
            $this->db->where('fgr.production_order_id', $production_order_id);
        }
        if (!empty($product_tank_id)) {
            $this->db->where('fgr.product_tank_id', $product_tank_id);
        }
        if ($product_type_category !== 'all') {
            $this->db->where('fgr.product_type_category', $product_type_category);
        }
        if ($status !== 'all') {
            $this->db->where('fgr.status', $status);
        }
        if (!empty($created_date_from)) {
            $this->db->where('DATE(fgr.created_at)', $created_date_from, '>=');
        }
        if (!empty($created_date_to)) {
            $this->db->where('DATE(fgr.created_at)', $created_date_to, '<=');
        }
        
        $cols = 'fgr.*, pt.product_type_name, fpt.product_tank_name, ppo.production_order_name';

        if (!empty($params['order_by'])) {
            $this->db->orderBy('fgr.'.$params['order_by'], $params['order_type']??'ASC');
        } else {
            $this->db->orderBy('fgr.finished_goods_receipt_id', 'DESC');
        }
        $this->db->join('eudr_production_orders ppo', 'ppo.production_order_id = fgr.production_order_id', 'LEFT');
        $this->db->join('eudr_tanks_finished_product fpt', 'fpt.product_tank_id = fgr.product_tank_id', 'LEFT');
        $this->db->join('eudr_production_product_types pt', 'pt.product_type_id = fgr.product_type_id', 'LEFT');
        $records = $this->db->arraybuilder()->paginate('eudr_tanks_finished_goods_receipts fgr', $page, $cols);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = $this->hydrateFinishedGoodsReceipt($item);
            }
        }

        $return_data = [
            "current_page" => $page,
            "total_pages" => $this->db->totalPages,
            "total_records" => $total_records,
            "page_limit" => $this->db->pageLimit,
            "records" => $items,
        ];

        return $return_data;
    }

    /**
     * {@inheritdoc}
     */
    public function findFinishedGoodsReceiptOfId(int $finished_goods_receipt_id): ?FinishedGoodsReceipt
    {
        $this->db->where('fgr.finished_goods_receipt_id', $finished_goods_receipt_id);
        $this->db->where('fgr.deleted_by', 0);
        $this->db->join('eudr_production_orders ppo', 'ppo.production_order_id = fgr.production_order_id', 'LEFT');
        $this->db->join('eudr_tanks_finished_product fpt', 'fpt.product_tank_id = fgr.product_tank_id', 'LEFT');
        $this->db->join('eudr_production_product_types pt', 'pt.product_type_id = fgr.product_type_id', 'LEFT');
        $finished_goods_receipt = $this->db->getOne('eudr_tanks_finished_goods_receipts fgr', 'fgr.*, pt.product_type_name, fpt.product_tank_name, ppo.production_order_name');
        if (empty($finished_goods_receipt)) {
            return null;
        }
        return $this->hydrateFinishedGoodsReceipt($finished_goods_receipt);
    }


    /**
     * {@inheritdoc}
     */
    public function findFinishedGoodsReceiptOfIdWithPermission(int $finished_goods_receipt_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?FinishedGoodsReceipt
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $companyIdParam = $company_id_param ?? 0;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'fgr');
        $this->db->where('fgr.finished_goods_receipt_id', $finished_goods_receipt_id);
        $this->db->join('eudr_production_orders ppo', 'ppo.production_order_id = fgr.production_order_id', 'LEFT');
        $this->db->join('eudr_tanks_finished_product fpt', 'fpt.product_tank_id = fgr.product_tank_id', 'LEFT');
        $this->db->join('eudr_production_product_types pt', 'pt.product_type_id = fgr.product_type_id', 'LEFT');

        $finished_goods_receipt = $this->db->getOne('eudr_tanks_finished_goods_receipts fgr', 'fgr.*, pt.product_type_name, fpt.product_tank_name, ppo.production_order_name');
        if (empty($finished_goods_receipt)) {
            return null;
        }

        return $this->hydrateFinishedGoodsReceipt($finished_goods_receipt);
    }


    /**
     * {@inheritdoc}
     */
    public function findFinishedGoodsReceiptOfCode(string $code): ?FinishedGoodsReceipt
    {
        $this->db->where('fgr.finished_goods_receipt_code', $code);
        $this->db->where('fgr.deleted_by', 0);
        $this->db->join('eudr_production_orders ppo', 'ppo.production_order_id = fgr.production_order_id', 'LEFT');
        $this->db->join('eudr_tanks_finished_product fpt', 'fpt.product_tank_id = fgr.product_tank_id', 'LEFT');
        $this->db->join('eudr_production_product_types pt', 'pt.product_type_id = fgr.product_type_id', 'LEFT');
        $finished_goods_receipt = $this->db->getOne('eudr_tanks_finished_goods_receipts fgr', 'fgr.*, pt.product_type_name, fpt.product_tank_name, ppo.production_order_name');
        if (empty($finished_goods_receipt)) {
            return null;
        }
        return $this->hydrateFinishedGoodsReceipt($finished_goods_receipt);
    }

    /**
     * {@inheritdoc}
     */
    public function findFinishedGoodsReceiptOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?FinishedGoodsReceipt
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $companyIdParam = $company_id_param ?? 0;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'fgr');
        $this->db->where('fgr.finished_goods_receipt_code', $code);
        $this->db->join('eudr_production_orders ppo', 'ppo.production_order_id = fgr.production_order_id', 'LEFT');
        $this->db->join('eudr_tanks_finished_product fpt', 'fpt.product_tank_id = fgr.product_tank_id', 'LEFT');
        $this->db->join('eudr_production_product_types pt', 'pt.product_type_id = fgr.product_type_id', 'LEFT');

        $finished_goods_receipt = $this->db->getOne('eudr_tanks_finished_goods_receipts fgr', 'fgr.*, pt.product_type_name, fpt.product_tank_name, ppo.production_order_name');
        if (empty($finished_goods_receipt)) {
            return null;
        }

        return $this->hydrateFinishedGoodsReceipt($finished_goods_receipt);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = "fgrc-".date("ymd").'-'.Utils::generateRandomString(8);
            $finished_goods_receipt = $this->findFinishedGoodsReceiptOfCode($code);
            if (!$finished_goods_receipt) {
                break;
            }
        }
        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function createFinishedGoodsReceipt(array $data): ?FinishedGoodsReceipt
    {
        $user_id = $data['created_by'];
        $production_order_id = $data['production_order_id'];
        $product_tank_id = $data['product_tank_id'];
        $product_type_category = $data['product_type_category'];
        $product_type_id = $data['product_type_id'];
        $actual_quantity = $data['actual_quantity'] ?? 0;
        $actual_weight = $data['actual_weight'] ?? 0;
        $tank_volume_before = $data['tank_volume_before'] ?? 0;
        $tank_volume_after = $data['tank_volume_after'] ?? 0;

        $this->db->startTransaction();

        $this->db->insert("eudr_tanks_finished_goods_receipts", $data);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $finished_goods_receipt_id = $this->db->getInsertId();

        // Update product tank volume
        $this->db->where("product_tank_id", $product_tank_id);
        $this->db->update("eudr_tanks_finished_product", [
            'current_volume' => $tank_volume_after,
            'updated_at' => date('Y-m-d H:i:s', time()),
            'updated_by' => $user_id,
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        // Add product tank history
        $data_history = array(
            'product_tank_id' => $product_tank_id,
            'entity_type' => 'finished_goods_receipt',
            'entity_id' => $finished_goods_receipt_id,
            'action_type' => 'input',
            'product_type_category' => $product_type_category,
            'product_type_id' => $product_type_id,
            'quantity' => $actual_quantity,
            'weight' => $actual_weight,
            'volume_before' => $tank_volume_before,
            'volume_after' => $tank_volume_after,
            'notes' => 'Nhập kho thành phẩm từ phiếu nhập kho',
            'created_at' => date('Y-m-d H:i:s', time()),
            'created_by' => $user_id,
        );

        $this->db->insert("eudr_tanks_finished_product_history", $data_history);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        // Update raw material release status
         $this->db->where("production_order_id", $production_order_id);
        $this->db->update("eudr_tanks_raw_material_releases", [
            'status' => 'completed',
            'updated_at' => date('Y-m-d H:i:s', time()),
            'updated_by' => $user_id,
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        // Update production order status
        $this->db->where("production_order_id", $production_order_id);
        $this->db->update("eudr_production_orders", [
            'status' => 'completed',
            'updated_at' => date('Y-m-d H:i:s', time()),
            'updated_by' => $user_id,
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->commit();

        return $this->findFinishedGoodsReceiptOfId($finished_goods_receipt_id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateFinishedGoodsReceipt(int $finished_goods_receipt_id, array $data_update): FinishedGoodsReceipt
    {
        $this->db->where("finished_goods_receipt_id", $finished_goods_receipt_id);
        $this->db->update("eudr_tanks_finished_goods_receipts", $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new FinishedGoodsReceiptNotFoundException("Finished Goods Receipt not found with ID: $finished_goods_receipt_id");
        }
        return $this->findFinishedGoodsReceiptOfId($finished_goods_receipt_id);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteFinishedGoodsReceipt(int $finished_goods_receipt_id, int $deleted_by): void
    {
        $this->db->where("finished_goods_receipt_id", $finished_goods_receipt_id);
        $this->db->update('eudr_tanks_finished_goods_receipts', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

    /**
     * {@inheritdoc}
     */
    public function productionFinishedGoodsSummary($params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array
    {
        $authUserId = $auth_user_id ?? ($params['user_id'] ?? ($this->currentUser->getUserId() ?? 0));
        $companyId = $company_id ?? ($params['company_id'] ?? ($this->currentUser->getCompanyId() ?? 0));
        $scopeParam = $scope ?? ($params['scope'] ?? 'own');
        $companyIdParam = $company_id_param ?? ($params['company_id_param'] ?? 0);
        $search = $params['search'] ?? '';
        $production_order_id = $params['production_order_id'] ?? 0;
        $product_tank_id = $params['product_tank_id'] ?? 0;
        $product_type_category = $params['product_type_category'] ?? 'all';
        $product_type_id = $params['product_type_id'] ?? 0;
        $status = $params['status'] ?? 'all';
        $created_date_from = $params['created_date_from'] ?? null;
        $created_date_to = $params['created_date_to'] ?? null;
        
        $this->scopeWhere($scopeParam, (int)$authUserId, (int)$companyId, (int)$companyIdParam, 'fgr');
        if (!empty($product_tank_id)) {
            $this->db->where('fgr.product_tank_id', $product_tank_id);
        }
        if (!empty($product_type_id)) {
            $this->db->where('fgr.product_type_id', $product_type_id);
        }
        if ($product_type_category !== 'all') {
            $this->db->where('fgr.product_type_category', $product_type_category);
        }
        if (!empty($production_order_id)) {
            $this->db->where('fgr.production_order_id', $production_order_id);
        }
        if ($status !== 'all') {
            $this->db->where('fgr.status', $status);
        }
        if (!empty($created_date_from)) {
            $this->db->where('DATE(fgr.created_at)', $created_date_from, '>=');
        }
        if (!empty($created_date_to)) {
            $this->db->where('DATE(fgr.created_at)', $created_date_to, '<=');
        }

        $this->db->join('eudr_production_product_types ppt', 'ppt.product_type_id = fgr.product_type_id', 'LEFT');
        $this->db->groupBy('ppt.product_type_id');
        $this->db->groupBy('ppt.product_type_code');
        $this->db->groupBy('ppt.product_type_name');
        $this->db->groupBy('ppt.product_type_category');

        $data = $this->db->get('eudr_tanks_finished_goods_receipts fgr', null,
            'ppt.product_type_id,
            ppt.product_type_code,
            ppt.product_type_name,
            ppt.product_type_category,
            SUM(fgr.actual_quantity) AS actual_quantity,
            SUM(fgr.actual_weight) AS actual_weight,
            COUNT(DISTINCT fgr.product_tank_id) AS product_tank_count'
        );

        return $data;
    }

}
