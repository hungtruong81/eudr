<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Company;

use App\Application\Utility\CurrentUserContext;
use App\Domain\Company\Company;
use App\Domain\Company\CompanyNotFoundException;
use App\Domain\Company\CompanyRepository;
use App\Application\Utility\Utils;

class InDatabaseCompanyRepository implements CompanyRepository
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
     * InDatabaseCompanyRepository constructor.
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
    private function scopeWhere(string $scope, int $authUserId, ?int $companyIdParam = null, string $alias = 'com'): void
    {
        $prefix = $alias ? $alias . '.' : '';
        $this->db->where($prefix . 'deleted_by', 0);

        if ($scope === 'self') {
            $this->db->where($prefix . 'created_by', $authUserId);
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
        $search = $params['search'] ?? '';
        $status = $params['status'] ?? 'all';
        $companyIdParam = $company_id_param ?? null;

        // Count total records
        $total_records = 0;
        $this->scopeWhere($scope, $authUserId, $companyIdParam, 'com');
        if (!empty($search)) {
            $this->db->where('(com.company_name LIKE ?)', ["%$search%"]);
        }
       
        if ($status !== 'all') {
            $this->db->where('com.status', $status);
        }
        $total_records = $this->db->getValue('eudr_companies com', 'count(*)');


        // Set pagination
        $this->db->pageLimit = $page_limit;

        $this->scopeWhere($scope, $authUserId, $companyIdParam, 'com');
        if (!empty($search)) {
            $this->db->where('(com.company_name LIKE ?)', ["%$search%"]);
        }
        if ($status !== 'all') {
            $this->db->where('com.status', $status);
        }
        
        $this->db->join('eudr_users u', 'u.company_id = com.company_id AND u.deleted_by = 0', 'LEFT');
        $this->db->groupBy('com.company_id');
        $cols = 'com.*, COUNT(DISTINCT u.user_id) AS member_count';
        if (!empty($params['order_by'])) {
            $this->db->orderBy('com.'.$params['order_by'], $params['order_type']??'ASC');
        } else {
            $this->db->orderBy("com.company_id", "DESC");
        }
        $records = $this->db->arraybuilder()->paginate("eudr_companies com", $page, $cols);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = new Company($item['company_id'], $item);
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
    public function findCompanyOfId(int $company_id): ?Company
    {
        $this->db->where("com.company_id", $company_id);
        $this->db->where("com.deleted_by", 0);
        $company = $this->db->getOne("eudr_companies com", "com.*");
        if (empty($company)) {
            return null;
        }
        return new Company($company['company_id'], $company);
    }

    /**
     * {@inheritdoc}
     */
    public function findCompanyOfIdWithPermission(int $company_id, ?int $auth_user_id, string $scope, ?int $company_id_param = null): ?Company
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);

        $this->scopeWhere($scope, $authUserId, $company_id_param, 'com');
        $this->db->where('com.company_id', $company_id);
        $company = $this->db->getOne('eudr_companies com', 'com.*');
        if (empty($company)) {
            return null;
        }

        return new Company($company['company_id'], $company);
    }


    /**
     * {@inheritdoc}
     */
    public function findCompanyOfCode(string $code): ?Company
    {
        $this->db->where("com.company_code", $code);
        $this->db->where("com.deleted_by", 0);
        $company = $this->db->getOne("eudr_companies com", "com.*");
        if (empty($company)) {
            return null;
        }
        return new Company($company['company_id'], $company);
    }

    /**
     * {@inheritdoc}
     */
    public function findCompanyOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id_param = null): ?Company
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);

        $this->scopeWhere($scope, $authUserId, $company_id_param, 'com');
        $this->db->where('com.company_code', $code);
        $company = $this->db->getOne('eudr_companies com', 'com.*');
        if (empty($company)) {
            return null;
        }

        return new Company($company['company_id'], $company);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = "comp-".date("ymd").'-'.Utils::generateRandomString(8);
            $company = $this->findCompanyOfCode($code);
            if (!$company) {
                break;
            }
        }
        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function createCompany(array $data): ?Company
    {
        $this->db->insert("eudr_companies", $data);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }

        $company_id = $this->db->getInsertId();

        return $this->findCompanyOfId($company_id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateCompany(int $company_id, array $data_update): Company
    {
        $this->db->where("company_id", $company_id);
        $this->db->update("eudr_companies", $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new CompanyNotFoundException("Company not found with ID: $company_id");
        }
        return $this->findCompanyOfId($company_id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateCompanyWithPermission(int $company_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id_param = null): Company
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);

        $this->scopeWhere($scope, $authUserId, $company_id_param, 'com');
        $this->db->where('com.company_id', $company_id);
        $this->db->update('eudr_companies com', $data_update);
        if ($this->db->getLastErrno() !== 0 || $this->db->count === 0) {
            throw new CompanyNotFoundException("Company not found with ID: $company_id");
        }

        return $this->findCompanyOfIdWithPermission($company_id, $auth_user_id, $scope, $company_id_param);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteCompany(int $company_id, int $deleted_by): void
    {
        $this->db->where("company_id", $company_id);
        $this->db->update('eudr_companies', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteCompanyWithPermission(int $company_id, int $deleted_by, string $scope, ?int $company_id_param = null): void
    {
        $authUserId = $deleted_by;

        $this->scopeWhere($scope, $authUserId, $company_id_param, 'com');
        $this->db->where('com.company_id', $company_id);
        $this->db->update('eudr_companies com', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

}
