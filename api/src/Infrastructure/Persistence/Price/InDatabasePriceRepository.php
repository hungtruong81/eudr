<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Price;

use App\Application\Utility\CurrentUserContext;
use App\Domain\Price\Price;
use App\Domain\Price\PriceNotFoundException;
use App\Domain\Price\PriceRepository;
use App\Application\Utility\Utils;

class InDatabasePriceRepository implements PriceRepository
{
    /**
     * @var MysqliDb
     */
    private $db;

    /**
     * @var CurrentUserContext
     */
    private $currentUser;

    public function __construct(\MysqliDb $db, CurrentUserContext $currentUserContext)
    {
        $this->db = $db;
        $this->currentUser = $currentUserContext;
    }

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

    public function findAll(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array
    {
        $page = (int)($params['page'] ?? 1);
        $page_limit = (int)($params['page_limit'] ?? 10);
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $companyIdParam = $company_id_param ?? 0;

        $search = $params['search'] ?? '';
        $price_type = $params['price_type'] ?? 'all';
        $price_code = $params['price_code'] ?? '';
        $price_id = (int)($params['price_id'] ?? 0);

        $this->scopeWhere($scope, (int)$authUserId, (int)$companyId, $companyIdParam, 'p');
        if (!empty($search)) {
            $this->db->where('(p.price_name LIKE ? OR p.price_code LIKE ?)', ["%$search%", "%$search%"]);
        }
        if ($price_type !== 'all') {
            $this->db->where('p.price_type', $price_type);
        }
        if (!empty($price_code)) {
            $this->db->where('p.price_code', $price_code);
        }
        if (!empty($price_id)) {
            $this->db->where('p.price_id', $price_id);
        }
        $total_records = (int)$this->db->getValue('eudr_master_prices p', 'count(*)');

        $this->db->pageLimit = $page_limit;

        $this->scopeWhere($scope, (int)$authUserId, (int)$companyId, $companyIdParam, 'p');
        if (!empty($search)) {
            $this->db->where('(p.price_name LIKE ? OR p.price_code LIKE ?)', ["%$search%", "%$search%"]);
        }
        if ($price_type !== 'all') {
            $this->db->where('p.price_type', $price_type);
        }
        if (!empty($price_code)) {
            $this->db->where('p.price_code', $price_code);
        }
        if (!empty($price_id)) {
            $this->db->where('p.price_id', $price_id);
        }

        if (!empty($params['order_by'])) {
            $this->db->orderBy('p.' . $params['order_by'], $params['order_type'] ?? 'ASC');
        } else {
            $this->db->orderBy('p.price_id', 'DESC');
        }

        $records = $this->db->arraybuilder()->paginate('eudr_master_prices p', $page, 'p.*');

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = new Price((int)$item['price_id'], $item);
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

    public function findPriceOfId(int $price_id): ?Price
    {
        $this->db->where('p.price_id', $price_id);
        $this->db->where('p.deleted_by', 0);
        $row = $this->db->getOne('eudr_master_prices p', 'p.*');
        if (empty($row)) {
            return null;
        }

        return new Price((int)$row['price_id'], $row);
    }

    public function findPriceOfIdWithPermission(int $price_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Price
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, (int)$authUserId, (int)$companyId, $company_id_param, 'p');
        $this->db->where('p.price_id', $price_id);

        $row = $this->db->getOne('eudr_master_prices p', 'p.*');
        if (empty($row)) {
            return null;
        }

        return new Price((int)$row['price_id'], $row);
    }

    public function findPriceOfCode(string $price_code): ?Price
    {
        $this->db->where('p.price_code', $price_code);
        $this->db->where('p.deleted_by', 0);
        $row = $this->db->getOne('eudr_master_prices p', 'p.*');
        if (empty($row)) {
            return null;
        }

        return new Price((int)$row['price_id'], $row);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = "pric-".date("ymd").'-'.Utils::generateRandomString(8);
            $price = $this->findPriceOfCode($code);
            if (!$price) {
                break;
            }
        }
        return $code;
    }

    public function findPriceOfCodeWithPermission(string $price_code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Price
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, (int)$authUserId, (int)$companyId, $company_id_param, 'p');
        $this->db->where('p.price_code', $price_code);

        $row = $this->db->getOne('eudr_master_prices p', 'p.*');
        if (empty($row)) {
            return null;
        }

        return new Price((int)$row['price_id'], $row);
    }

    public function createPrice(array $data): ?Price
    {
        $this->db->insert('eudr_master_prices', $data);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }

        return $this->findPriceOfId((int)$this->db->getInsertId());
    }

    public function updatePriceWithPermission(int $price_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): Price
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, (int)$authUserId, (int)$companyId, $company_id_param, '');
        $this->db->where('price_id', $price_id);
        $this->db->update('eudr_master_prices', $data_update);

        if ($this->db->getLastErrno() !== 0) {
            throw new PriceNotFoundException('Price not found with ID: ' . $price_id);
        }

        $updated = $this->findPriceOfId($price_id);
        if (empty($updated)) {
            throw new PriceNotFoundException('Price not found with ID: ' . $price_id);
        }

        return $updated;
    }

    public function deletePriceWithPermission(int $price_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, (int)$authUserId, (int)$companyId, $company_id_param, '');
        $this->db->where('price_id', $price_id);
        $this->db->update('eudr_master_prices', [
            'deleted_by' => $deleted_by,
            'deleted_at' => date('Y-m-d H:i:s', time()),
        ]);
    }
}
