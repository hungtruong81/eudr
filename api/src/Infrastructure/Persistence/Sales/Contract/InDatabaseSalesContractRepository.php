<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Sales\Contract;

use App\Application\Utility\CurrentUserContext;
use App\Application\Utility\Utils;
use App\Domain\Sales\Contract\SalesContract;
use App\Domain\Sales\Contract\SalesContractRepository;

class InDatabaseSalesContractRepository implements SalesContractRepository
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
     * InDatabaseSalesContractRepository constructor.
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
    private function scopeWhere(string $scope, int $companyId, ?int $companyIdParam = null, string $alias = 'ct'): void
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
        $customer_id = $params['customer_id'] ?? 0;
        $date_from = $params['start_date_from'] ?? null;
        $date_to = $params['start_date_to'] ?? null;
        $companyIdParam = $company_id_param ?? ($params['company_id_param'] ?? null);

        $this->scopeWhere($scope, (int)$companyId, $companyIdParam, 'ct');
        if ($status !== 'all') {
            $this->db->where('ct.status', $status);
        }
        if (!empty($search)) {
            $this->db->where('(ct.contract_code LIKE ? OR ct.title LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($customer_id)) {
            $this->db->where('ct.customer_id', (int)$customer_id);
        }
        if (!empty($date_from)) {
            $this->db->where('ct.start_date', $date_from, '>=');
        }
        if (!empty($date_to)) {
            $this->db->where('ct.start_date', $date_to, '<=');
        }
        $total_records = $this->db->getValue('eudr_sales_contracts ct', 'count(*)');

        $this->db->pageLimit = $page_limit;
        $this->scopeWhere($scope, (int)$companyId, $companyIdParam, 'ct');
        if ($status !== 'all') {
            $this->db->where('ct.status', $status);
        }
        if (!empty($search)) {
            $this->db->where('(ct.contract_code LIKE ? OR ct.title LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($customer_id)) {
            $this->db->where('ct.customer_id', (int)$customer_id);
        }
        if (!empty($date_from)) {
            $this->db->where('ct.start_date', $date_from, '>=');
        }
        if (!empty($date_to)) {
            $this->db->where('ct.start_date', $date_to, '<=');
        }
        $this->db->orderBy('ct.contract_id', 'DESC');
        $rows = $this->db->arraybuilder()->paginate('eudr_sales_contracts ct', $page);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($rows as $row) {
                $row['items'] = [];
                $items[] = new SalesContract((int)$row['contract_id'], $row);
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
    public function findContractOfId(int $contract_id): ?SalesContract
    {
        $this->db->where('ct.contract_id', $contract_id);
        $row = $this->db->getOne('eudr_sales_contracts ct', 'ct.*');
        if (empty($row)) {
            return null;
        }
        $row['items'] = $this->getItems($contract_id);
        return new SalesContract((int)$row['contract_id'], $row);
    }

    /**
     * {@inheritdoc}
     */
    public function findContractOfIdWithPermission(int $contract_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?SalesContract
    {
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, (int)$companyId, $company_id_param, 'ct');
        $this->db->where('ct.contract_id', $contract_id);
        $row = $this->db->getOne('eudr_sales_contracts ct', 'ct.*');
        if (empty($row)) {
            return null;
        }
        $row['items'] = $this->getItems((int)$row['contract_id']);
        return new SalesContract((int)$row['contract_id'], $row);
    }

    /**
     * {@inheritdoc}
     */
    public function findContractOfCode(string $contract_code): ?SalesContract
    {
        $this->db->where('ct.contract_code', $contract_code);
        $row = $this->db->getOne('eudr_sales_contracts ct', 'ct.*');
        if (empty($row)) {
            return null;
        }
        $row['items'] = $this->getItems((int)$row['contract_id']);
        return new SalesContract((int)$row['contract_id'], $row);
    }

    /**
     * {@inheritdoc}
     */
    public function findContractOfCodeWithPermission(string $contract_code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?SalesContract
    {
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, (int)$companyId, $company_id_param, 'ct');
        $this->db->where('ct.contract_code', $contract_code);
        $row = $this->db->getOne('eudr_sales_contracts ct', 'ct.*');
        if (empty($row)) {
            return null;
        }
        $row['items'] = $this->getItems((int)$row['contract_id']);
        return new SalesContract((int)$row['contract_id'], $row);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = 'ctrt-' . date('ymd') . '-' . Utils::generateRandomString(8);
            $exists = $this->findContractOfCode($code);
            if (!$exists) {
                break;
            }
        }
        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function createContract(array $data, array $items): ?SalesContract
    {
        $companyId = isset($data['company_id']) ? (int)$data['company_id'] : ($this->currentUser->getCompanyId() ?? 0);
        $data['company_id'] = $companyId;
        if (empty($data['contract_code'])) {
            $data['contract_code'] = $this->generateCode();
        }

        $now = date('Y-m-d H:i:s');
        if (empty($data['created_at'])) {
            $data['created_at'] = $now;
        }

        $this->db->startTransaction();
        $this->db->insert('eudr_sales_contracts', $data);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $contractId = (int)$this->db->getInsertId();
        $this->insertItems($contractId, $companyId, $items, $data['created_by'] ?? null, $now);

        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->commit();
        return $this->findContractOfId($contractId);
    }

    /**
     * {@inheritdoc}
     */
    public function updateContractWithPermission(int $contract_id, array $data, array $items, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?SalesContract
    {
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->db->startTransaction();

        $this->scopeWhere($scope, (int)$companyId, $company_id_param, '');
        $this->db->where('contract_id', $contract_id);
        $this->db->update('eudr_sales_contracts', $data);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->where('contract_id', $contract_id);
        $this->db->delete('eudr_sales_contract_items');
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->insertItems($contract_id, $companyId, $items, $data['updated_by'] ?? null, date('Y-m-d H:i:s'));
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->commit();
        return $this->findContractOfIdWithPermission($contract_id, $auth_user_id, $scope, $company_id, $company_id_param);
    }

    /**
     * {@inheritdoc}
     */
    private function insertItems(int $contractId, int $companyId, array $items, ?int $userId, string $timestamp): void
    {
        $rows = [];
        foreach ($items as $item) {
            $rows[] = [
                'contract_id' => $contractId,
                'company_id' => $companyId,
                'product_id' => (int)($item['product_id'] ?? 0),
                'uom' => (string)($item['uom'] ?? ''),
                'qty_committed' => (float)($item['qty_committed'] ?? 0),
                'price' => (float)($item['price'] ?? 0),
                'currency' => (string)($item['currency'] ?? 'VND'),
                'min_qc_grade' => $item['min_qc_grade'] ?? null,
                'delivery_start' => $item['delivery_start'] ?? null,
                'delivery_end' => $item['delivery_end'] ?? null,
                'notes' => $item['notes'] ?? null,
                'created_at' => $timestamp,
                'created_by' => $userId,
            ];
        }

        if (!empty($rows)) {
            $this->db->insertMulti('eudr_sales_contract_items', $rows);
        }
    }

    /**
     * {@inheritdoc}
     */
    private function getItems(int $contractId): array
    {
        $this->db->where('ci.contract_id', $contractId);
        $rows = $this->db->get('eudr_sales_contract_items ci', null, 'ci.*');

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'contract_item_id' => (int)$row['contract_item_id'],
                'contract_id' => (int)$row['contract_id'],
                'company_id' => (int)$row['company_id'],
                'product_id' => (int)$row['product_id'],
                'uom' => $row['uom'],
                'qty_committed' => (float)$row['qty_committed'],
                'price' => (float)$row['price'],
                'currency' => $row['currency'],
                'min_qc_grade' => $row['min_qc_grade'],
                'delivery_start' => $row['delivery_start'],
                'delivery_end' => $row['delivery_end'],
                'notes' => $row['notes'],
            ];
        }

        return $items;
    }
}
