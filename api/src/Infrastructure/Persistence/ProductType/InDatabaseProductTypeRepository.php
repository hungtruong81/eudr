<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\ProductType;

use App\Application\Utility\CurrentUserContext;
use App\Domain\ProductType\ProductType;
use App\Domain\ProductType\ProductTypeNotFoundException;
use App\Domain\ProductType\ProductTypeRepository;
use App\Application\Utility\Utils;

class InDatabaseProductTypeRepository implements ProductTypeRepository
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
     * InDatabaseProductTypeRepository constructor.
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
     * Note: product type table does not contain company_id, so own behaves like self.
     */
    private function scopeWhere(string $scope, int $authUserId, ?int $companyId = null, ?int $companyIdParam = null, string $alias = 'pt'): void
    {
        $prefix = $alias ? $alias . '.' : '';
        $this->db->where($prefix . 'deleted_by', 0);

        if ($scope === 'self' || $scope === 'own') {
            $this->db->where($prefix . 'created_by', $authUserId);
        }
        // scope "all" intentionally applies no creator/company restriction because table lacks company_id
    }

    /**
     * {@inheritdoc}
     */
    public function findAll($params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $search = $params['search'] ?? '';
        $product_type_category = $params['product_type_category'] ?? 'all';
        $product_type_code = $params['product_type_code'] ?? '';
        $product_type_id = $params['product_type_id'] ?? 0;

       // Count total records
        $this->scopeWhere($scope, $authUserId, $company_id, $company_id_param, 'pt');
        if (!empty($search)) {
            $this->db->where('(pt.product_type_name LIKE ? OR pt.product_type_code LIKE ?)', ["%$search%", "%$search%"]); 
        }
        if ($product_type_category !== 'all') {
            $this->db->where("pt.product_type_category", $product_type_category);
        }
        if (!empty($product_type_code)) {
            $this->db->where("pt.product_type_code", $product_type_code);
        }
        if (!empty($product_type_id)) {
            $this->db->where("pt.product_type_id", $product_type_id);
        }
        $total_records = (int)$this->db->getValue("eudr_production_product_types pt", "count(*)");


        // Set pagination
        $this->db->pageLimit = $page_limit;

        $this->scopeWhere($scope, $authUserId, $company_id, $company_id_param, 'pt');
        if (!empty($search)) {
            $this->db->where('(pt.product_type_name LIKE ? OR pt.product_type_code LIKE ?)', ["%$search%", "%$search%"]); 
        }
        if ($product_type_category !== 'all') {
            $this->db->where("pt.product_type_category", $product_type_category);
        }
        if (!empty($product_type_code)) {
            $this->db->where("pt.product_type_code", $product_type_code);
        }
        if (!empty($product_type_id)) {
            $this->db->where("pt.product_type_id", $product_type_id);
        }
        
        $cols = "pt.*";

        if (!empty($params['order_by'])) {
            $this->db->orderBy('pt.'.$params['order_by'], $params['order_type']??'ASC');
        } else {
            $this->db->orderBy("pt.product_type_id", "DESC");
        }
        $records = $this->db->arraybuilder()->paginate("eudr_production_product_types pt", $page, $cols);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = new ProductType($item['product_type_id'], $item);
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
    public function findProductTypeOfId(int $product_type_id): ?ProductType
    {
        $this->db->where("pt.product_type_id", $product_type_id);
        $this->db->where("pt.deleted_by", 0);
        $product_type = $this->db->getOne("eudr_production_product_types pt", "pt.*");
        if (empty($product_type)) {
            return null;
        }
        return new ProductType($product_type['product_type_id'], $product_type);
    }

    /**
     * {@inheritdoc}
     */
    public function findProductTypeOfIdWithPermission(int $product_type_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductType
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);

        $this->scopeWhere($scope, $authUserId, $company_id, $company_id_param, 'pt');
        $this->db->where('pt.product_type_id', $product_type_id);

        $product_type = $this->db->getOne('eudr_production_product_types pt', 'pt.*');
        if (empty($product_type)) {
            return null;
        }

        return new ProductType($product_type['product_type_id'], $product_type);
    }


    /**
     * {@inheritdoc}
     */
    public function findProductTypeOfCode(string $code): ?ProductType
    {
        $this->db->where("pt.product_type_code", $code);
        $this->db->where("pt.deleted_by", 0);
        $product_type = $this->db->getOne("eudr_production_product_types pt", "pt.*");
        if (empty($product_type)) {
            return null;
        }
        return new ProductType($product_type['product_type_id'], $product_type);
    }

    /**
     * {@inheritdoc}
     */
    public function findProductTypeOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductType
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);

        $this->scopeWhere($scope, $authUserId, $company_id, $company_id_param, 'pt');
        $this->db->where('pt.product_type_code', $code);

        $product_type = $this->db->getOne('eudr_production_product_types pt', 'pt.*');
        if (empty($product_type)) {
            return null;
        }

        return new ProductType($product_type['product_type_id'], $product_type);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = "prtp-".date("ymd").'-'.Utils::generateRandomString(8);
            $product_type = $this->findProductTypeOfCode($code);
            if (!$product_type) {
                break;
            }
        }
        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function createProductType(array $data): ?ProductType
    {
        $this->db->insert("eudr_production_product_types", $data);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }

        $product_type_id = $this->db->getInsertId();

        return $this->findProductTypeOfId($product_type_id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateProductType(int $product_type_id, array $data_update): ProductType
    {
        $this->db->where("product_type_id", $product_type_id);
        $this->db->update("eudr_production_product_types", $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new ProductTypeNotFoundException("Product Type not found with ID: $product_type_id");
        }
        return $this->findProductTypeOfId($product_type_id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateProductTypeWithPermission(int $product_type_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ProductType
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);

        $this->scopeWhere($scope, $authUserId, $company_id, $company_id_param, '');
        $this->db->where('product_type_id', $product_type_id);
        $this->db->update('eudr_production_product_types', $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new ProductTypeNotFoundException("Product Type not found with ID: $product_type_id");
        }

        return $this->findProductTypeOfId($product_type_id);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteProductType(int $product_type_id, int $deleted_by): void
    {
        $this->db->where("product_type_id", $product_type_id);
        $this->db->update('eudr_production_product_types', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteProductTypeWithPermission(int $product_type_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);

        $this->scopeWhere($scope, $authUserId, $company_id, $company_id_param, '');
        $this->db->where('product_type_id', $product_type_id);
        $this->db->update('eudr_production_product_types', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

}
