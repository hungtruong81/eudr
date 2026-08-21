<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Factory;

use App\Application\Utility\CurrentUserContext;
use App\Domain\Factory\Factory;
use App\Domain\Factory\FactoryNotFoundException;
use App\Domain\Factory\FactoryRepository;
use App\Application\Utility\Utils;

class InDatabaseFactoryRepository implements FactoryRepository
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
     * InDatabaseFactoryRepository constructor.
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
    private function scopeWhere(string $scope, int $authUserId, int $companyId, ?int $companyIdParam = null, string $alias = 'f'): void
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
        $search = $params['search'] ?? null;
        $companyIdParam = $company_id_param ?? 0;

       // Count total records
        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'f');
        if (!empty($search)) {
            $this->db->where('(f.factory_name LIKE ? OR f.factory_code LIKE ?)', ["%$search%", "%$search%"]);
        }
        $total_records = (int)$this->db->getValue("eudr_factories f", "count(*)");


        // Set pagination
        $this->db->pageLimit = $page_limit;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'f');
        if (!empty($search)) {
            $this->db->where('(f.factory_name LIKE ? OR f.factory_code LIKE ?)', ["%$search%", "%$search%"]);
        }

        $cols = "f.*";

        if (!empty($params['order_by'])) {
            $this->db->orderBy('f.'.$params['order_by'], $params['order_type']??'ASC');
        } else {
            $this->db->orderBy("f.factory_id", "DESC");
        }
        $records = $this->db->arraybuilder()->paginate("eudr_factories f", $page, $cols);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = new Factory($item['factory_id'], $item);
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
    public function findFactoryOfId(int $factory_id): ?Factory
    {
        $this->db->where("f.factory_id", $factory_id);
        $this->db->where("f.deleted_by", 0);
        $factory = $this->db->getOne("eudr_factories f", "f.*");
        if (empty($factory)) {
            return null;
        }
        return new Factory($factory['factory_id'], $factory);
    }

    /**
     * {@inheritdoc}
     */
    public function findFactoryOfIdWithPermission(int $factory_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Factory
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'f');
        $this->db->where('f.factory_id', $factory_id);

        $factory = $this->db->getOne('eudr_factories f', 'f.*');
        if (empty($factory)) {
            return null;
        }

        return new Factory($factory['factory_id'], $factory);
    }

    /**
     * {@inheritdoc}
     */
    public function findFactoryOfCode(string $code): ?Factory
    {
        $this->db->where("f.factory_code", $code);
        $this->db->where("f.deleted_by", 0);
        $factory = $this->db->getOne("eudr_factories f", "f.*");
        if (empty($factory)) {
            return null;
        }
        return new Factory($factory['factory_id'], $factory);
    }

    /**
     * {@inheritdoc}
     */
    public function findFactoryOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Factory
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'f');
        $this->db->where('f.factory_code', $code);

        $factory = $this->db->getOne('eudr_factories f', 'f.*');
        if (empty($factory)) {
            return null;
        }

        return new Factory($factory['factory_id'], $factory);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = "fact-".date("ymd").'-'.Utils::generateRandomString(8);
            $factory = $this->findFactoryOfCode($code);
            if (!$factory) {
                break;
            }
        }
        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function createFactory(array $data): ?Factory
    {
        $this->db->insert("eudr_factories", $data);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }

        $factory_id = $this->db->getInsertId();

        return $this->findFactoryOfId($factory_id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateFactory(int $factory_id, array $data_update): Factory
    {
        $this->db->where("factory_id", $factory_id);
        $this->db->update("eudr_factories", $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new FactoryNotFoundException("Factory not found with ID: $factory_id");
        }
        return $this->findFactoryOfId($factory_id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateFactoryWithPermission(int $factory_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): Factory
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, '');
        $this->db->where('factory_id', $factory_id);
        $this->db->update('eudr_factories', $data_update);

        if ($this->db->getLastErrno() !== 0) {
            throw new FactoryNotFoundException("Factory not found with ID: $factory_id");
        }

        return $this->findFactoryOfId($factory_id);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteFactory(int $factory_id, int $deleted_by): void
    {
        $this->db->where("factory_id", $factory_id);
        $this->db->update('eudr_factories', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteFactoryWithPermission(int $factory_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, '');
        $this->db->where('factory_id', $factory_id);
        $this->db->update('eudr_factories', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

}
