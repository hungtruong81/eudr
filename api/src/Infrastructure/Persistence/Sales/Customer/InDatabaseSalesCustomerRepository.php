<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Sales\Customer;

use App\Application\Utility\CurrentUserContext;
use App\Application\Settings\SettingsInterface;
use App\Application\Utility\Utils;
use App\Domain\Sales\Customer\SalesCustomer;
use App\Domain\Sales\Customer\SalesCustomerRepository;

class InDatabaseSalesCustomerRepository implements SalesCustomerRepository
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
     * @var SettingsInterface
     */
    private $settings;

    /**
     * InDatabaseSalesCustomerRepository constructor.
     *
     * @param MysqliDb $db
     * @param CurrentUserContext $currentUserContext
     */
    public function __construct(\MysqliDb $db, CurrentUserContext $currentUserContext, SettingsInterface $settings)
    {
        $this->db = $db;
        $this->currentUser = $currentUserContext;
        $this->settings = $settings;
    }

    /**
     * Apply scope-based filtering (self/own/all).
     */
    private function scopeWhere(string $scope, int $companyId, ?int $companyIdParam = null, string $alias = 'c'): void
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
        $companyIdParam = $company_id_param ?? ($params['company_id_param'] ?? null);

        $this->scopeWhere($scope, (int)$companyId, $companyIdParam, 'c');
        if ($status !== 'all') {
            $this->db->where('c.status', $status);
        }
        if (!empty($search)) {
            $this->db->where('(c.customer_name LIKE ? OR c.tax_code LIKE ? OR c.customer_phone LIKE ? OR c.customer_email LIKE ?)', ["%$search%", "%$search%", "%$search%", "%$search%"]);
        }
        $total_records = $this->db->getValue('eudr_sales_customers c', 'count(*)');

        $this->db->pageLimit = $page_limit;
        $this->scopeWhere($scope, (int)$companyId, $companyIdParam, 'c');
        if ($status !== 'all') {
            $this->db->where('c.status', $status);
        }
        if (!empty($search)) {
            $this->db->where('(c.customer_name LIKE ? OR c.tax_code LIKE ? OR c.customer_phone LIKE ? OR c.customer_email LIKE ?)', ["%$search%", "%$search%", "%$search%", "%$search%"]);
        }
        $this->db->orderBy('c.customer_id', 'DESC');
        $rows = $this->db->arraybuilder()->paginate('eudr_sales_customers c', $page);

        $fileIdBucket = [];
        foreach ($rows as $row) {
            $ids = $this->decodeFileIds($row['business_license_file_ids'] ?? '');
            $fileIdBucket = array_merge($fileIdBucket, $ids);
        }
        $fileMap = $this->getFilePathsByIds($fileIdBucket);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($rows as $row) {
                $row = $this->enrichFiles($row, $fileMap);
                $items[] = new SalesCustomer((int)$row['customer_id'], $row);
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
    public function findCustomerOfId(int $customer_id): ?SalesCustomer
    {
        $this->db->where('c.deleted_by', 0);
        $this->db->where('c.customer_id', $customer_id);
        $row = $this->db->getOne('eudr_sales_customers c', 'c.*');
        if (empty($row)) {
            return null;
        }
        $row = $this->enrichFiles($row);
        return new SalesCustomer((int)$row['customer_id'], $row);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findCustomerOfIdWithPermission(int $customer_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?SalesCustomer
    {
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, (int)$companyId, $company_id_param, 'c');
        $this->db->where('c.customer_id', $customer_id);
        $row = $this->db->getOne('eudr_sales_customers c', 'c.*');
        if (empty($row)) {
            return null;
        }
        $row = $this->enrichFiles($row);
        return new SalesCustomer((int)$row['customer_id'], $row);
    }

    /**
     * {@inheritdoc}
     */
    public function findCustomerOfCode(string $customer_code): ?SalesCustomer
    {
        $this->db->where('c.deleted_by', 0);
        $this->db->where('c.customer_code', $customer_code);
        $row = $this->db->getOne('eudr_sales_customers c', 'c.*');
        if (empty($row)) {
            return null;
        }
        $row = $this->enrichFiles($row);
        return new SalesCustomer((int)$row['customer_id'], $row);
    }

    /**
     * {@inheritdoc}
     */
    public function findCustomerOfCodeWithPermission(string $customer_code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?SalesCustomer
    {
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, (int)$companyId, $company_id_param, 'c');
        $this->db->where('c.customer_code', $customer_code);
        $row = $this->db->getOne('eudr_sales_customers c', 'c.*');
        if (empty($row)) {
            return null;
        }
        $row = $this->enrichFiles($row);
        return new SalesCustomer((int)$row['customer_id'], $row);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = 'cutr-' . date('ymd') . '-' . Utils::generateRandomString(8);
            $exists = $this->findCustomerOfCode($code);
            if (!$exists) {
                break;
            }
        }
        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function createCustomer(array $data): ?SalesCustomer
    {

        $this->db->insert('eudr_sales_customers', $data);
        if ($this->db->getLastErrno() !== 0) {
            throw new \RuntimeException("Failed to create sales customer");
        }
        $id = (int)$this->db->getInsertId();
        return $this->findCustomerOfId($id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateCustomerWithPermission(int $customer_id, array $data, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): SalesCustomer
    {
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, (int)$companyId, $company_id_param, '');
        $this->db->where('customer_id', $customer_id);
        $this->db->update('eudr_sales_customers', $data);

        if ($this->db->getLastErrno() !== 0) {
            throw new \RuntimeException("Failed to update sales customer");
        }

        return $this->findCustomerOfIdWithPermission($customer_id, $auth_user_id, $scope, $company_id, $company_id_param);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteCustomerWithPermission(int $customer_id, array $data, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): bool
    {
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, (int)$companyId, $company_id_param, '');
        $this->db->where('customer_id', $customer_id);
        $this->db->update('eudr_sales_customers', $data);

        if ($this->db->getLastErrno() !== 0) {
            return false;
        }

        return true;
    }

    
    /**
     * Decode stored JSON file id list to int array.
     */
    private function decodeFileIds($raw): array
    {
        if (empty($raw)) {
            return [];
        }
        if (is_array($raw)) {
            return array_values(array_map('intval', $raw));
        }
        $decoded = json_decode((string)$raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        return array_values(array_map('intval', $decoded));
    }

    /**
     * Map file ids to paths.
     * @param int[] $fileIds
     * @return array<int,string>
     */
    private function getFilePathsByIds(array $fileIds): array
    {
        $fileIds = array_values(array_unique(array_filter(array_map('intval', $fileIds))));
        if (empty($fileIds)) {
            return [];
        }
        $this->db->where('file_id', $fileIds, 'IN');
        $this->db->where('is_deleted', 0);
        $rows = $this->db->get('eudr_general_files', null, ['file_id', 'file_path']);
        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['file_id']] = $row['file_path'];
        }
        return $map;
    }

    /**
     * Enrich customer row with decoded ids and file URLs.
     * @param array<int|string,mixed> $row
     * @param array<int,string> $fileMap
     * @return array<string,mixed>
     */
    private function enrichFiles(array $row, array $fileMap = []): array
    {
        $ids = $this->decodeFileIds($row['business_license_file_ids'] ?? '');
        $row['business_license_file_ids'] = $ids;
        if (empty($fileMap)) {
            $fileMap = $this->getFilePathsByIds($ids);
        }
        $urls = [];
        foreach ($ids as $id) {
            if (!empty($fileMap[$id])) {
                $urls[] = $this->buildFileUrl($fileMap[$id]);
            }
        }
        $row['business_license_file_urls'] = $urls;
        return $row;
    }

    /**
     * Build absolute CDN URL from stored file path.
     */
    private function buildFileUrl(string $path): string
    {
        if (empty($path)) {
            return '';
        }
        $base = rtrim((string)$this->settings->get('url_cdn'), '/');
        $trimmed = ltrim($path, '/');
        return $base . '/' . $trimmed;
    }
}
