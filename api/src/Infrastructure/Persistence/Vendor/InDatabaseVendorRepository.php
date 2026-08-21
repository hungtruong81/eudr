<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Vendor;

use App\Application\Utility\CurrentUserContext;
use App\Application\Utility\Utils;
use App\Domain\Vendor\Vendor;
use App\Domain\Vendor\VendorNotFoundException;
use App\Domain\Vendor\VendorRepository;

class InDatabaseVendorRepository implements VendorRepository
{
    /**
     * @var \MysqliDb
     */
    private \MysqliDb $db;

    /**
     * @var CurrentUserContext
     */
    private CurrentUserContext $currentUser;

    /**
     * @param \MysqliDb $db
     * @param CurrentUserContext $currentUserContext
     */
    public function __construct(\MysqliDb $db, CurrentUserContext $currentUserContext)
    {
        $this->db = $db;
        $this->currentUser = $currentUserContext;
    }

    /**
     * @param string $scope
     * @param int $authUserId
     * @param int $companyId
     * @param int|null $companyIdParam
     * @param string $alias
     * @return void
     */
    private function scopeWhere(string $scope, int $authUserId, int $companyId, ?int $companyIdParam = null, string $alias = 'v'): void
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
     * @param array $params
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return array
     */
    public function findAll(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array
    {
        $page = (int)($params['page'] ?? 1);
        $pageLimit = (int)($params['page_limit'] ?? 10);
        $search = trim((string)($params['search'] ?? ''));
        $status = (string)($params['status'] ?? 'all');
        $vendorType = (string)($params['vendor_type'] ?? 'all');
        $provinceId = (int)($params['province_id'] ?? 0);

        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, (int)$authUserId, (int)$companyId, $company_id_param, 'v');
        if ($status !== 'all') {
            $this->db->where('v.status', $status);
        }
        if ($vendorType !== 'all') {
            $this->db->where('v.vendor_type', $vendorType);
        }
        if ($provinceId > 0) {
            $this->db->where('v.province_id', $provinceId);
        }
        if ($search !== '') {
            $this->db->where('(v.vendor_name LIKE ? OR v.vendor_code LIKE ? OR v.identity_number LIKE ? OR v.tax_code LIKE ? OR v.contact_name LIKE ? OR v.contact_phone LIKE ?)', ["%$search%", "%$search%", "%$search%", "%$search%", "%$search%", "%$search%"]);
        }
        $totalRecords = (int)$this->db->getValue('eudr_vendors v', 'COUNT(*)');

        $this->db->pageLimit = $pageLimit;
        $this->scopeWhere($scope, (int)$authUserId, (int)$companyId, $company_id_param, 'v');
        if ($status !== 'all') {
            $this->db->where('v.status', $status);
        }
        if ($vendorType !== 'all') {
            $this->db->where('v.vendor_type', $vendorType);
        }
        if ($provinceId > 0) {
            $this->db->where('v.province_id', $provinceId);
        }
        if ($search !== '') {
            $this->db->where('(v.vendor_name LIKE ? OR v.vendor_code LIKE ? OR v.identity_number LIKE ? OR v.tax_code LIKE ? OR v.contact_name LIKE ? OR v.contact_phone LIKE ?)', ["%$search%", "%$search%", "%$search%", "%$search%", "%$search%", "%$search%"]);
        }

        $this->db->orderBy('v.vendor_id', 'DESC');
        $records = $this->db->arrayBuilder()->paginate('eudr_vendors v', $page, 'v.*');

        $items = [];
        foreach ((array)$records as $item) {
            $items[] = new Vendor((int)$item['vendor_id'], $item);
        }

        return [
            'current_page' => $page,
            'total_pages' => (int)$this->db->totalPages,
            'total_records' => $totalRecords,
            'page_limit' => $this->db->pageLimit,
            'records' => $items,
        ];
    }

    /**
     * @return string
     */
    public function generateCode(): string
    {
        while (true) {
            $code = 'vend-' . date('ymd') . '-' . Utils::generateRandomString(8);
            if ($this->findVendorOfCode($code) === null) {
                return $code;
            }
        }
    }

    /**
     * @param int $vendor_id
    * @return Vendor|null
    * @throws VendorNotFoundException
     */
    public function findVendorOfId(int $vendor_id): ?Vendor
    {
        $this->db->where('v.vendor_id', $vendor_id);
        $this->db->where('v.deleted_by', 0);
        $vendor = $this->db->getOne('eudr_vendors v', 'v.*');
        if (empty($vendor)) {
            return null;
        }

        return new Vendor((int)$vendor['vendor_id'], $vendor);
    }

    /**
     * @param int $vendor_id
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return Vendor|null
     */
    public function findVendorOfIdWithPermission(int $vendor_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Vendor
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, (int)$authUserId, (int)$companyId, $company_id_param, 'v');
        $this->db->where('v.vendor_id', $vendor_id);
        $vendor = $this->db->getOne('eudr_vendors v', 'v.*');
        if (empty($vendor)) {
            return null;
        }

        return new Vendor((int)$vendor['vendor_id'], $vendor);
    }

    /**
     * @param string $code
     * @return Vendor|null
     */
    public function findVendorOfCode(string $code): ?Vendor
    {
        $this->db->where('v.vendor_code', $code);
        $this->db->where('v.deleted_by', 0);
        $vendor = $this->db->getOne('eudr_vendors v', 'v.*');
        if (empty($vendor)) {
            return null;
        }

        return new Vendor((int)$vendor['vendor_id'], $vendor);
    }

    /**
     * @param string $code
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return Vendor|null
     */
    public function findVendorOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Vendor
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, (int)$authUserId, (int)$companyId, $company_id_param, 'v');
        $this->db->where('v.vendor_code', $code);
        $vendor = $this->db->getOne('eudr_vendors v', 'v.*');
        if (empty($vendor)) {
            return null;
        }

        return new Vendor((int)$vendor['vendor_id'], $vendor);
    }

    /**
     * @param string $field
     * @param string $value
     * @param int|null $exclude_vendor_id
     * @return bool
     */
    public function identifierExists(string $field, string $value, ?int $exclude_vendor_id = null): bool
    {
        if (!in_array($field, ['identity_number', 'tax_code'], true) || $value === '') {
            return false;
        }

        $this->db->where($field, $value);
        if ($exclude_vendor_id !== null) {
            $this->db->where('vendor_id', $exclude_vendor_id, '!=');
        }

        return (int)$this->db->getValue('eudr_vendors', 'COUNT(*)') > 0;
    }

    /**
     * @param array $data
     * @return Vendor|null
     */
    public function createVendor(array $data): ?Vendor
    {
        $this->db->insert('eudr_vendors', $data);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }

        return $this->findVendorOfId((int)$this->db->getInsertId());
    }

    /**
     * @param int $vendor_id
     * @param array $data_update
     * @return Vendor
     */
    public function updateVendor(int $vendor_id, array $data_update): Vendor
    {
        $this->db->where('vendor_id', $vendor_id);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_vendors', $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new VendorNotFoundException("Vendor not found with ID: $vendor_id");
        }

        $vendor = $this->findVendorOfId($vendor_id);
        if (empty($vendor)) {
            throw new VendorNotFoundException("Vendor not found with ID: $vendor_id");
        }

        return $vendor;
    }

    /**
     * @param int $vendor_id
     * @param array $data_update
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return Vendor
     */
    public function updateVendorWithPermission(int $vendor_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): Vendor
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, (int)$authUserId, (int)$companyId, $company_id_param, '');
        $this->db->where('vendor_id', $vendor_id);
        $this->db->update('eudr_vendors', $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new VendorNotFoundException("Vendor not found with ID: $vendor_id");
        }

        $vendor = $this->findVendorOfId($vendor_id);
        if (empty($vendor)) {
            throw new VendorNotFoundException("Vendor not found with ID: $vendor_id");
        }

        return $vendor;
    }

    /**
     * @param int $vendor_id
     * @param int $deleted_by
     * @return void
     */
    public function deleteVendor(int $vendor_id, int $deleted_by): void
    {
        $this->db->where('vendor_id', $vendor_id);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_vendors', [
            'deleted_by' => $deleted_by,
            'deleted_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param int $vendor_id
     * @param int $deleted_by
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return void
     */
    public function deleteVendorWithPermission(int $vendor_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, (int)$authUserId, (int)$companyId, $company_id_param, '');
        $this->db->where('vendor_id', $vendor_id);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_vendors', [
            'deleted_by' => $deleted_by,
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_by' => $deleted_by,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
