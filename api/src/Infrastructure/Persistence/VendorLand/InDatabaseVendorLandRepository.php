<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\VendorLand;

use App\Domain\VendorLand\VendorLandRepository;

class InDatabaseVendorLandRepository implements VendorLandRepository
{
    /**
     * @var \MysqliDb
     */
    private \MysqliDb $db;

    /**
     * @param \MysqliDb $db
     */
    public function __construct(\MysqliDb $db)
    {
        $this->db = $db;
    }

    /**
     * @return string
     */
    private function select(): string
    {
        return 'vl.*, v.vendor_name, v.vendor_type, l.plot_code, l.plot_name, l.land_area, l.province_id, l.address, l.coordinates, l.status AS land_status';
    }

    /**
     * @param array $record
     * @return array
     */
    private function normalizeRecord(array $record): array
    {
        $coordinates = json_decode((string)($record['coordinates'] ?? ''), true);
        $record['coordinates'] = is_array($coordinates) ? $coordinates : [];

        return $record;
    }

    /**
     * @param int $vendorId
     * @param string $status
     * @param string $search
     * @return void
     */
    private function applyListFilters(int $vendorId, string $status, string $search): void
    {
        $this->db->where('vl.vendor_id', $vendorId);
        $this->db->where('vl.deleted_by', 0);
        $this->db->where('vl.deleted_at', null, 'IS');

        if ($status !== 'all') {
            $this->db->where('vl.status', $status);
        }

        if ($search !== '') {
            $likeSearch = "%{$search}%";
            $this->db->where(
                '(l.plot_code LIKE ? OR l.plot_name LIKE ? OR l.address LIKE ?)',
                [$likeSearch, $likeSearch, $likeSearch]
            );
        }
    }

    /**
     * @param int $vendorId
     * @param array $params
     * @return array
     */
    public function findAll(int $vendorId, array $params = []): array
    {
        $page = (int)($params['page'] ?? 1);
        $pageLimit = (int)($params['page_limit'] ?? 10);
        $status = (string)($params['status'] ?? 'all');
        $search = trim((string)($params['search'] ?? ''));

        $this->db->join('eudr_lands l', 'l.plot_id=vl.plot_id', 'LEFT');
        $this->applyListFilters($vendorId, $status, $search);
        $totalRecords = (int)$this->db->getValue('eudr_vendor_lands vl', 'COUNT(*)');

        $this->db->pageLimit = $pageLimit;
        $this->db->join('eudr_vendors v', 'v.vendor_id=vl.vendor_id', 'LEFT');
        $this->db->join('eudr_lands l', 'l.plot_id=vl.plot_id', 'LEFT');
        $this->applyListFilters($vendorId, $status, $search);
        $this->db->orderBy('vl.vendor_land_id', 'DESC');
        $records = $this->db->arrayBuilder()->paginate('eudr_vendor_lands vl', $page, $this->select());

        $items = [];
        foreach ((array)$records as $record) {
            $items[] = $this->normalizeRecord($record);
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
     * @param int $vendorId
     * @param int $vendorLandId
     * @return array|null
     */
    public function findOne(int $vendorId, int $vendorLandId): ?array
    {
        $this->db->join('eudr_vendors v', 'v.vendor_id=vl.vendor_id', 'LEFT');
        $this->db->join('eudr_lands l', 'l.plot_id=vl.plot_id', 'LEFT');
        $this->db->where('vl.vendor_id', $vendorId);
        $this->db->where('vl.vendor_land_id', $vendorLandId);
        $this->db->where('vl.deleted_by', 0);
        $this->db->where('vl.deleted_at', null, 'IS');
        $record = $this->db->getOne('eudr_vendor_lands vl', $this->select());

        return !empty($record) ? $this->normalizeRecord($record) : null;
    }

    /**
     * @param int $vendorId
     * @param int $plotId
     * @return bool
     */
    public function activeRelationExists(int $vendorId, int $plotId): bool
    {
        $this->db->where('vendor_id', $vendorId);
        $this->db->where('plot_id', $plotId);
        $this->db->where('deleted_by', 0);
        $this->db->where('deleted_at', null, 'IS');
        $this->db->where('status', 'active');
        return (int)$this->db->getValue('eudr_vendor_lands', 'COUNT(*)') > 0;
    }

    /**
     * @param array $data
     * @return array|null
     */
    public function create(array $data): ?array
    {
        $id = (int)$this->db->insert('eudr_vendor_lands', $data);
        return $id > 0 ? $this->findOne((int)$data['vendor_id'], $id) : null;
    }

    /**
     * @param int $vendorId
     * @param int $vendorLandId
     * @param array $data
     * @return array|null
     */
    public function update(int $vendorId, int $vendorLandId, array $data): ?array
    {
        $this->db->where('vendor_id', $vendorId);
        $this->db->where('vendor_land_id', $vendorLandId);
        $this->db->where('deleted_by', 0);
        $this->db->where('deleted_at', null, 'IS');
        $this->db->update('eudr_vendor_lands', $data);
        return $this->findOne($vendorId, $vendorLandId);
    }

    /**
     * @param int $vendorId
     * @param int $vendorLandId
     * @param int $deletedBy
     * @return void
     */
    public function delete(int $vendorId, int $vendorLandId, int $deletedBy): void
    {
        $this->db->where('vendor_id', $vendorId);
        $this->db->where('vendor_land_id', $vendorLandId);
        $this->db->where('deleted_by', 0);
        $this->db->where('deleted_at', null, 'IS');
        $this->db->update('eudr_vendor_lands', [
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => $deletedBy,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $deletedBy,
        ]);
    }
}