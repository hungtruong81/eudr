<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Pallet;

use App\Application\Utility\CurrentUserContext;
use App\Application\Utility\Utils;
use App\Domain\Pallet\Pallet;
use App\Domain\Pallet\PalletNotFoundException;
use App\Domain\Pallet\PalletRepository;

class InDatabasePalletRepository implements PalletRepository
{
    private $db;
    private $currentUser;

    public function __construct(\MysqliDb $db, CurrentUserContext $currentUserContext)
    {
        $this->db = $db;
        $this->currentUser = $currentUserContext;
    }

    private function scopeWhere(string $scope, int $authUserId, int $companyId, ?int $companyIdParam = null, string $alias = 'p'): void
    {
        $prefix = $alias !== '' ? $alias . '.' : '';

        if ($scope === 'self') {
            $this->db->where($prefix . 'created_by', $authUserId);
            $this->db->where($prefix . 'company_id', $companyId);
        } elseif ($scope === 'own') {
            $this->db->where($prefix . 'company_id', $companyId);
        } elseif ($scope === 'all' && !empty($companyIdParam)) {
            $this->db->where($prefix . 'company_id', $companyIdParam);
        }
    }

    private function hydrate(array $row): Pallet
    {
        return new Pallet((int)$row['pallet_id'], $row);
    }

    public function findAll(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $search = $params['search'] ?? '';
        $status = $params['status'] ?? 'all';
        $warehouse_id = $params['warehouse_id'] ?? 0;

        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $companyIdParam = $company_id_param ?? 0;

        $this->scopeWhere($scope, (int)$authUserId, (int)$companyId, (int)$companyIdParam, 'p');
        if ($search !== '') {
            $this->db->where('(p.pallet_code LIKE ?)', ["%$search%"]);
        }
        if ($status !== 'all') {
            $this->db->where('p.status', $status);
        }
        if (!empty($warehouse_id)) {
            $this->db->where('p.warehouse_id', (int)$warehouse_id);
        }
        $total_records = (int)$this->db->getValue('eudr_production_pallets p', 'count(*)');

        $this->db->pageLimit = $page_limit;

        $this->scopeWhere($scope, (int)$authUserId, (int)$companyId, (int)$companyIdParam, 'p');
        if ($search !== '') {
            $this->db->where('(p.pallet_code LIKE ?)', ["%$search%"]);
        }
        if ($status !== 'all') {
            $this->db->where('p.status', $status);
        }
        if (!empty($warehouse_id)) {
            $this->db->where('p.warehouse_id', (int)$warehouse_id);
        }

        if (!empty($params['order_by'])) {
            $this->db->orderBy('p.' . $params['order_by'], $params['order_type'] ?? 'ASC');
        } else {
            $this->db->orderBy('p.pallet_id', 'DESC');
        }

        $records = $this->db->arraybuilder()->paginate('eudr_production_pallets p', $page, 'p.*');

        $items = [];
        if (!empty($records)) {
            foreach ($records as $row) {
                $items[] = $this->hydrate($row);
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

    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = 'pall-' . date('ymd') . '-' . Utils::generateRandomString(8);
            if (!$this->findPalletOfCode($code)) {
                break;
            }
        }
        return $code;
    }

    public function findPalletOfId(int $pallet_id): ?Pallet
    {
        $this->db->where('pallet_id', $pallet_id);
        $row = $this->db->getOne('eudr_production_pallets');
        if (empty($row)) {
            return null;
        }
        return $this->hydrate($row);
    }

    public function findPalletOfIdWithPermission(int $pallet_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Pallet
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, (int)$authUserId, (int)$companyId, $company_id_param, 'p');
        $this->db->where('p.pallet_id', $pallet_id);
        $row = $this->db->getOne('eudr_production_pallets p', 'p.*');
        if (empty($row)) {
            return null;
        }
        return $this->hydrate($row);
    }

    public function findPalletOfCode(string $code): ?Pallet
    {
        $this->db->where('pallet_code', $code);
        $row = $this->db->getOne('eudr_production_pallets');
        if (empty($row)) {
            return null;
        }
        return $this->hydrate($row);
    }

    public function findPalletOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Pallet
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, (int)$authUserId, (int)$companyId, $company_id_param, 'p');
        $this->db->where('p.pallet_code', $code);
        $row = $this->db->getOne('eudr_production_pallets p', 'p.*');
        if (empty($row)) {
            return null;
        }
        return $this->hydrate($row);
    }

    public function createPallet(array $data): ?Pallet
    {
        $this->db->insert('eudr_production_pallets', $data);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }
        return $this->findPalletOfId((int)$this->db->getInsertId());
    }

    public function updatePalletWithPermission(int $pallet_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): Pallet
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, (int)$authUserId, (int)$companyId, $company_id_param, '');
        $this->db->where('pallet_id', $pallet_id);
        $this->db->update('eudr_production_pallets', $data_update);

        if ($this->db->getLastErrno() !== 0) {
            throw new PalletNotFoundException('Pallet not found with ID: ' . $pallet_id);
        }

        $updated = $this->findPalletOfId($pallet_id);
        if (empty($updated)) {
            throw new PalletNotFoundException('Pallet not found with ID: ' . $pallet_id);
        }

        return $updated;
    }

    public function deletePalletWithPermission(int $pallet_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, (int)$authUserId, (int)$companyId, $company_id_param, 'p');
        $this->db->where('p.pallet_id', $pallet_id);
        $allowed = $this->db->getOne('eudr_production_pallets p', 'p.pallet_id');
        if (empty($allowed)) {
            throw new PalletNotFoundException('Pallet not found with ID: ' . $pallet_id);
        }

        $this->db->where('pallet_id', $pallet_id);
        $this->db->delete('eudr_production_pallet_items');

        $this->db->where('pallet_id', $pallet_id);
        $this->db->delete('eudr_production_pallets');
    }

    public function listPalletItems(int $pallet_id): array
    {
        $this->db->where('pi.pallet_id', $pallet_id);
        $this->db->join('eudr_production_rubber_blocks rb', 'rb.rubber_block_id = pi.rubber_block_id', 'LEFT');
        $this->db->orderBy('pi.pallet_item_id', 'DESC');
        $rows = $this->db->arraybuilder()->get(
            'eudr_production_pallet_items pi',
            null,
            'pi.pallet_item_id, pi.pallet_id, pi.rubber_block_id, pi.added_at, rb.rubber_block_code, rb.weight, rb.grade, rb.status'
        );

        return $rows ?? [];
    }

    public function addPalletItems(int $pallet_id, array $rubber_block_ids): array
    {
        $inserted = [];
        $rubber_block_ids = array_values(array_unique(array_map('intval', $rubber_block_ids)));

        foreach ($rubber_block_ids as $rubber_block_id) {
            $this->db->where('pallet_id', $pallet_id);
            $this->db->where('rubber_block_id', $rubber_block_id);
            $exists = $this->db->getOne('eudr_production_pallet_items', 'pallet_item_id');
            if (!empty($exists)) {
                continue;
            }

            $this->db->insert('eudr_production_pallet_items', [
                'pallet_id' => $pallet_id,
                'rubber_block_id' => $rubber_block_id,
                'added_at' => date('Y-m-d H:i:s'),
            ]);

            if ($this->db->getLastErrno() === 0) {
                $inserted[] = [
                    'pallet_item_id' => (int)$this->db->getInsertId(),
                    'pallet_id' => $pallet_id,
                    'rubber_block_id' => $rubber_block_id,
                ];
            }
        }

        $this->recalculatePalletTotals($pallet_id);

        return $inserted;
    }

    public function removePalletItem(int $pallet_id, int $pallet_item_id): void
    {
        $this->db->where('pallet_id', $pallet_id);
        $this->db->where('pallet_item_id', $pallet_item_id);
        $this->db->delete('eudr_production_pallet_items');

        $this->recalculatePalletTotals($pallet_id);
    }

    public function recalculatePalletTotals(int $pallet_id): ?Pallet
    {
        $this->db->where('pi.pallet_id', $pallet_id);
        $this->db->join('eudr_production_rubber_blocks rb', 'rb.rubber_block_id = pi.rubber_block_id', 'LEFT');
        $row = $this->db->getOne('eudr_production_pallet_items pi', 'COUNT(pi.pallet_item_id) AS total_bales, COALESCE(SUM(rb.weight),0) AS total_weight');

        $data = [
            'total_bales' => (int)($row['total_bales'] ?? 0),
            'total_weight' => (float)($row['total_weight'] ?? 0),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->where('pallet_id', $pallet_id);
        $this->db->update('eudr_production_pallets', $data);

        return $this->findPalletOfId($pallet_id);
    }

    public function updateStatusWithPermission(int $pallet_id, string $status, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Pallet
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $updateData = array_merge($data_update, [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->scopeWhere($scope, (int)$authUserId, (int)$companyId, $company_id_param, '');
        $this->db->where('pallet_id', $pallet_id);
        $this->db->update('eudr_production_pallets', $updateData);

        if ($this->db->getLastErrno() !== 0) {
            throw new PalletNotFoundException('Pallet not found with ID: ' . $pallet_id);
        }

        return $this->findPalletOfId($pallet_id);
    }
}
