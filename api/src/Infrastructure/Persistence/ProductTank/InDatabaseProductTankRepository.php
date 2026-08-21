<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\ProductTank;

use App\Application\Utility\CurrentUserContext;
use App\Domain\ProductTank\ProductTank;
use App\Domain\ProductTank\ProductTankNotFoundException;
use App\Domain\ProductTank\ProductTankRepository;
use App\Application\Utility\Utils;

class InDatabaseProductTankRepository implements ProductTankRepository
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
     * InDatabaseProductTankRepository constructor.
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
    private function scopeWhere(string $scope, int $authUserId, int $companyId, ?int $companyIdParam = null, string $alias = 'p'): void
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
     * {@inheritdoc}
     */
    public function findAll($params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $search = $params['search'] ?? '';
        $factory_id = $params['factory_id'] ?? 0;
        $product_type = $params['product_type'] ?? 'all';
        $status = $params['status'] ?? 'all';
        $companyIdParam = $company_id_param ?? 0;
        $product_tank_code = $params['product_tank_code'] ?? '';
        $product_tank_id = $params['product_tank_id'] ?? 0;

       // Count total records
        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'p');
        if (!empty($search)) {
            $this->db->where('(p.product_tank_name LIKE ? OR p.product_tank_code LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($factory_id)) {
            $this->db->where("p.factory_id", $factory_id);
        }
        if ($product_type !== 'all') {
            $this->db->where("p.product_type", $product_type);
        }
        if ($status !== 'all') {
            $this->db->where("p.status", $status);
        }
        if (!empty($product_tank_code)) {
            $this->db->where("p.product_tank_code", $product_tank_code);
        }
        if (!empty($product_tank_id)) {
            $this->db->where("p.product_tank_id", $product_tank_id);
        }
        $this->db->join("eudr_factories f", "f.factory_id = p.factory_id", "LEFT");
        $total_records = (int)$this->db->getValue("eudr_tanks_finished_product p", "count(*)");


        // Set pagination
        $this->db->pageLimit = $page_limit;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'p');
        if (!empty($search)) {
            $this->db->where('(p.product_tank_name LIKE ? OR p.product_tank_code LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($factory_id)) {
            $this->db->where("p.factory_id", $factory_id);
        }
        if ($product_type !== 'all') {
            $this->db->where("p.product_type", $product_type);
        }
        if ($status !== 'all') {
            $this->db->where("p.status", $status);
        }
        if (!empty($product_tank_code)) {
            $this->db->where("p.product_tank_code", $product_tank_code);
        }
        if (!empty($product_tank_id)) {
            $this->db->where("p.product_tank_id", $product_tank_id);
        }

        $cols = "p.*, f.factory_name";

        if (!empty($params['order_by'])) {
            $this->db->orderBy('p.'.$params['order_by'], $params['order_type']??'ASC');
        } else {
            $this->db->orderBy("p.product_tank_id", "DESC");
        }
        $this->db->join("eudr_factories f", "f.factory_id = p.factory_id", "LEFT");
        $records = $this->db->arraybuilder()->paginate("eudr_tanks_finished_product p", $page, $cols);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = new ProductTank($item['product_tank_id'], $item);
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
    public function findProductTankOfId(int $product_tank_id): ?ProductTank
    {
        $this->db->where("t.product_tank_id", $product_tank_id);
        $this->db->where("t.deleted_by", 0);
        $this->db->join("eudr_factories f", "f.factory_id = t.factory_id", "LEFT");
        $tank = $this->db->getOne("eudr_tanks_finished_product t", "t.*, f.factory_name");
        if (empty($tank)) {
            return null;
        }
        return new ProductTank($tank['product_tank_id'], $tank);
    }

    /**
     * {@inheritdoc}
     */
    public function findProductTankOfIdWithPermission(int $product_tank_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductTank
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 't');
        $this->db->where('t.product_tank_id', $product_tank_id);
        $this->db->join('eudr_factories f', 'f.factory_id = t.factory_id', 'LEFT');

        $tank = $this->db->getOne('eudr_tanks_finished_product t', 't.*, f.factory_name');
        if (empty($tank)) {
            return null;
        }

        return new ProductTank($tank['product_tank_id'], $tank);
    }


    /**
     * {@inheritdoc}
     */
    public function findProductTankOfCode(string $code): ?ProductTank
    {
        $this->db->where("t.product_tank_code", $code);
        $this->db->where("t.deleted_by", 0);
        $this->db->join("eudr_factories f", "f.factory_id = t.factory_id", "LEFT");
        $tank = $this->db->getOne("eudr_tanks_finished_product t", "t.*, f.factory_name");
        if (empty($tank)) {
            return null;
        }
        return new ProductTank($tank['product_tank_id'], $tank);
    }

    /**
     * {@inheritdoc}
     */
    public function findProductTankOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductTank
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 't');
        $this->db->where('t.product_tank_code', $code);
        $this->db->join('eudr_factories f', 'f.factory_id = t.factory_id', 'LEFT');

        $tank = $this->db->getOne('eudr_tanks_finished_product t', 't.*, f.factory_name');
        if (empty($tank)) {
            return null;
        }

        return new ProductTank($tank['product_tank_id'], $tank);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = "ptnk-".date("ymd").'-'.Utils::generateRandomString(8);
            $tank = $this->findProductTankOfCode($code);
            if (!$tank) {
                break;
            }
        }
        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function createProductTank(array $data): ?ProductTank
    {
        $this->db->insert("eudr_tanks_finished_product", $data);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }

        $tank_id = $this->db->getInsertId();

        return $this->findProductTankOfId($tank_id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateProductTank(int $tank_id, array $data_update): ProductTank
    {
        $this->db->where("product_tank_id", $tank_id);
        $this->db->update("eudr_tanks_finished_product", $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new ProductTankNotFoundException("Product Tank not found with ID: $tank_id");
        }
        return $this->findProductTankOfId($tank_id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateProductTankWithPermission(int $product_tank_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ProductTank
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, '');
        $this->db->where('product_tank_id', $product_tank_id);
        $this->db->update('eudr_tanks_finished_product', $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new ProductTankNotFoundException("Product Tank not found with ID: $product_tank_id");
        }

        return $this->findProductTankOfId($product_tank_id);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteProductTank(int $tank_id, int $deleted_by): void
    {
        $this->db->where("product_tank_id", $tank_id);
        $this->db->update('eudr_tanks_finished_product', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteProductTankWithPermission(int $product_tank_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, '');
        $this->db->where('product_tank_id', $product_tank_id);
        $this->db->update('eudr_tanks_finished_product', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

    /**
     * {@inheritdoc}
     */
    public function getHistoryOfProductTank(int $product_tank_id, array $params, ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $action_type = $params['action_type'] ?? 'all';
        $rubber_type = $params['rubber_type'] ?? 'all';
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $companyIdParam = $company_id_param ?? 0;

        // Count total records
        $this->db->join('eudr_tanks_finished_product p', 'p.product_tank_id = fph_history.product_tank_id', 'LEFT');
        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'p');
        $this->db->where("fph_history.product_tank_id", $product_tank_id);
        if ($action_type !== 'all') {
            $this->db->where("fph_history.action_type", $action_type);
        }
        if ($rubber_type !== 'all') {
            $this->db->where("fph_history.rubber_type", $rubber_type);
        }
        $total_records = (int)$this->db->getValue("eudr_tanks_finished_product_history fph_history", "count(*)");

        // Set pagination
        $this->db->pageLimit = $page_limit;

        $this->db->join('eudr_tanks_finished_product p', 'p.product_tank_id = fph_history.product_tank_id', 'LEFT');
        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'p');
        $this->db->where("fph_history.product_tank_id", $product_tank_id);
        if ($action_type !== 'all') {
            $this->db->where("fph_history.action_type", $action_type);
        }
        if ($rubber_type !== 'all') {
            $this->db->where("fph_history.rubber_type", $rubber_type);
        }

        $cols = "fph_history.*";

        $this->db->orderBy("fph_history.created_at", "DESC");
        $records = $this->db->arrayBuilder()->paginate("eudr_tanks_finished_product_history fph_history", $page, $cols);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = $item;
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


}
