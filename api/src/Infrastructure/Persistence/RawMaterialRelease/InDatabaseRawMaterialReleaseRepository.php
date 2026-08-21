<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\RawMaterialRelease;

use App\Domain\RawMaterialRelease\RawMaterialRelease;
use App\Domain\RawMaterialRelease\RawMaterialReleaseRepository;
use App\Application\Utility\CurrentUserContext;
use App\Application\Utility\Utils;

class InDatabaseRawMaterialReleaseRepository implements RawMaterialReleaseRepository
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
     * InDatabaseRawMaterialReleaseRepository constructor.
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
     * Apply scope-based filtering (self/own/all) using company_id.
     */
    private function scopeWhere(string $scope, int $authUserId, int $companyId, ?int $companyIdParam = null, string $alias = 'rmr'): void
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
     * Enrich a raw material release record with its tanks/items and hydrate the entity.
     */
    private function hydrateRawMaterialRelease(array $rawMaterialRelease): RawMaterialRelease
    {
        $releaseId = (int)$rawMaterialRelease['material_release_id'];

        $this->db->where('rmri.material_release_id', $releaseId);
        $this->db->join('eudr_tanks_raw_material rmt', 'rmt.raw_material_tank_id = rmri.raw_tank_id', 'LEFT');
        $material_release_items = $this->db->get(
            'eudr_tanks_raw_material_release_items rmri',
            null,
            'rmri.material_release_item_id,
            rmt.raw_material_tank_id,
            rmt.raw_material_tank_code,
            rmt.raw_material_tank_name,
            rmt.capacity,
            rmt.current_volume,
            rmri.rubber_type,
            rmri.weight_requested,
            rmri.notes'
        );

        $rawMaterialRelease['raw_material_tanks'] = $material_release_items ?? [];

        return new RawMaterialRelease($releaseId, $rawMaterialRelease);
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
        $status = $params['status'] ?? 'all';
        $created_date_from = $params['created_date_from'] ?? null;
        $created_date_to = $params['created_date_to'] ?? null;
        $companyIdParam = $company_id_param ?? 0;

       // Count total records
        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'rmr');
        if (!empty($search)) {
            $this->db->where('(rmr.material_release_name LIKE ? OR rmr.material_release_code LIKE ?)', ["%$search%", "%$search%"]);
        }
        if ($status !== 'all') {
            $this->db->where("rmr.status", $status);
        }
        if (!empty($created_date_from)) {
            $this->db->where("DATE(rmr.created_at)", $created_date_from, ">=");
        }
        if (!empty($created_date_to)) {
            $this->db->where("DATE(rmr.created_at)", $created_date_to, "<=");
        }
        $total_records = (int)$this->db->getValue("eudr_tanks_raw_material_releases rmr", "count(*)");


        // Set pagination
        $this->db->pageLimit = $page_limit;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'rmr');
        if (!empty($search)) {
            $this->db->where('(rmr.material_release_name LIKE ? OR rmr.material_release_code LIKE ?)', ["%$search%", "%$search%"]);
        }
        if ($status !== 'all') {
            $this->db->where("rmr.status", $status);
        }
        if (!empty($created_date_from)) {
            $this->db->where("DATE(rmr.created_at)", $created_date_from, ">=");
        }
        if (!empty($created_date_to)) {
            $this->db->where("DATE(rmr.created_at)", $created_date_to, "<=");
        }
        
        $cols = "rmr.*";

        if (!empty($params['order_by'])) {
            $this->db->orderBy('rmr.'.$params['order_by'], $params['order_type']??'ASC');
        } else {
            $this->db->orderBy("rmr.material_release_id", "DESC");
        }
        $records = $this->db->arraybuilder()->paginate("eudr_tanks_raw_material_releases rmr", $page, $cols);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = new RawMaterialRelease($item['material_release_id'], $item);
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
    public function findRawMaterialReleaseOfId(int $raw_material_release_id): ?RawMaterialRelease
    {
        $this->db->where("rmr.material_release_id", $raw_material_release_id);
        $this->db->where("rmr.deleted_by", 0);
        $this->db->join("eudr_production_orders ppo", "ppo.production_order_id = rmr.production_order_id", "LEFT");
        $raw_material_release = $this->db->getOne("eudr_tanks_raw_material_releases rmr", "rmr.*, ppo.production_order_name, ppo.production_order_code");
        if (empty($raw_material_release)) {
            return null;
        }

        return $this->hydrateRawMaterialRelease($raw_material_release);
    }


    /**
     * {@inheritdoc}
     */
    public function findRawMaterialReleaseOfIdWithPermission(int $raw_material_release_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?RawMaterialRelease
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $companyIdParam = $company_id_param ?? 0;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'rmr');
        $this->db->where('rmr.material_release_id', $raw_material_release_id);
        $this->db->join('eudr_production_orders ppo', 'ppo.production_order_id = rmr.production_order_id', 'LEFT');

        $raw_material_release = $this->db->getOne('eudr_tanks_raw_material_releases rmr', 'rmr.*, ppo.production_order_name, ppo.production_order_code');
        if (empty($raw_material_release)) {
            return null;
        }

        return $this->hydrateRawMaterialRelease($raw_material_release);
    }


    /**
     * {@inheritdoc}
     */
    public function findRawMaterialReleaseOfCode(string $code): ?RawMaterialRelease
    {
        $this->db->where("rmr.material_release_code", $code);
        $this->db->where("rmr.deleted_by", 0);
        $this->db->join("eudr_production_orders ppo", "ppo.production_order_id = rmr.production_order_id", "LEFT");
        $raw_material_release = $this->db->getOne("eudr_tanks_raw_material_releases rmr", "rmr.*, ppo.production_order_name, ppo.production_order_code");
        if (empty($raw_material_release)) {
            return null;
        }

        return $this->hydrateRawMaterialRelease($raw_material_release);
    }

    /**
     * {@inheritdoc}
     */
    public function findRawMaterialReleaseOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?RawMaterialRelease
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $companyIdParam = $company_id_param ?? 0;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'rmr');
        $this->db->where('rmr.material_release_code', $code);
        $this->db->join('eudr_production_orders ppo', 'ppo.production_order_id = rmr.production_order_id', 'LEFT');

        $raw_material_release = $this->db->getOne('eudr_tanks_raw_material_releases rmr', 'rmr.*, ppo.production_order_name, ppo.production_order_code');
        if (empty($raw_material_release)) {
            return null;
        }

        return $this->hydrateRawMaterialRelease($raw_material_release);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = "rmrl-".date("ymd").'-'.Utils::generateRandomString(8);
            $raw_material_release = $this->findRawMaterialReleaseOfCode($code);
            if (!$raw_material_release) {
                break;
            }
        }
        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function createRawMaterialRelease(array $data): ?RawMaterialRelease
    {
        $material_release_items = $data['raw_material_tanks'];
        unset($data['raw_material_tanks']);

        $this->db->startTransaction();
        $this->db->insert("eudr_tanks_raw_material_releases", $data);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $material_release_id = $this->db->getInsertId();

        foreach ($material_release_items as $item) {
            $data_item_insert = array(
                'material_release_id' => $material_release_id,
                'raw_tank_id' => $item['tank_id'],
                'rubber_type' => $item['rubber_type'],
                'weight_requested' => $item['weight_requested'],
                'weight_released' => $item['weight_requested'],
                'tank_volume_before' => 0,
                'tank_volume_after' => 0,
                'released_at' => date('Y-m-d H:i:s', time()),
                'created_at' => date('Y-m-d H:i:s', time()),
                'notes' => $item['notes'] ?? '',
                
            );
            $this->db->insert("eudr_tanks_raw_material_release_items", $data_item_insert);
            if ($this->db->getLastErrno() !== 0) {
                $this->db->rollback();
                return null;
            }

             // Update volume in tanks
            $this->db->where("raw_material_tank_id", $item['tank_id']);
            $existingTank = $this->db->getOne("eudr_tanks_raw_material", "current_volume");
            if (!empty($existingTank)) {
                $new_volume = floatval($existingTank['current_volume']) - floatval($item['weight_requested']);
                $this->db->where("raw_material_tank_id", $item['tank_id']);
                $this->db->update("eudr_tanks_raw_material", ["current_volume" => $new_volume]);
                if ($this->db->getLastErrno() !== 0) {
                    $this->db->rollback();
                    return null;
                }
                // Add raw material tank history
                $data_history = array(
                    'raw_material_tank_id' => $item['tank_id'],
                    'entity_type' => 'raw_material_release',
                    'entity_id' => $material_release_id,
                    'action_type' => 'output',
                    'rubber_type' => $item['rubber_type'],
                    'weight' => $item['weight_requested'],
                    'volume_before' => $existingTank['current_volume'],
                    'volume_after' => $new_volume,
                    'notes' => 'Lấy nguyên liệu thô từ phiếu xuất kho (Trước sản xuất)',
                    'created_at' => date('Y-m-d H:i:s', time()),
                    'created_by' => $data['created_by'],
                );
                $this->db->insert("eudr_tanks_raw_material_history", $data_history);
                if ($this->db->getLastErrno() !== 0) {
                    $this->db->rollback();
                    return null;
                }
            }

        }

        // Update production order status to in_production
        $this->db->where("production_order_id", $data['production_order_id']);
        $this->db->update("eudr_production_orders", [
            'status' => 'in_production',
            'updated_at' => date('Y-m-d H:i:s', time()),
            'updated_by' => $data['created_by'],
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->commit();

        return $this->findRawMaterialReleaseOfId($material_release_id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateRawMaterialRelease(int $raw_material_release_id, array $data_update): ?RawMaterialRelease
    {
        $material_release_items = $data_update['raw_material_tanks'];
        unset($data_update['raw_material_tanks']);

        $this->db->startTransaction();
        $this->db->where("material_release_id", $raw_material_release_id);
        $this->db->update("eudr_tanks_raw_material_releases", $data_update);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        // Delete existing items
        $this->db->where("material_release_id", $raw_material_release_id);
        $this->db->delete("eudr_tanks_raw_material_release_items");
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        foreach ($material_release_items as $item) {
            $data_item_insert = array(
                'material_release_id' => $raw_material_release_id,
                'raw_tank_id' => $item['tank_id'],
                'rubber_type' => $item['rubber_type'],
                'weight_requested' => $item['weight_requested'],
                'weight_released' => $item['weight_requested'],
                'tank_volume_before' => 0,
                'tank_volume_after' => 0,
                'released_at' => date('Y-m-d H:i:s', time()),
                'created_at' => date('Y-m-d H:i:s', time()),
                'notes' => $item['notes'] ?? '',
                
            );
            
            $this->db->insert("eudr_tanks_raw_material_release_items", $data_item_insert);
            if ($this->db->getLastErrno() !== 0) {
                $this->db->rollback();
                return null;
            }

            // Update volume in tanks
            $this->db->where("raw_material_tank_id", $item['tank_id']);
            $existingTank = $this->db->getOne("eudr_tanks_raw_material", "current_volume");
            if (!empty($existingTank)) {
                $new_volume = floatval($existingTank['current_volume']) - floatval($item['weight_requested']);
                $this->db->where("raw_material_tank_id", $item['tank_id']);
                $this->db->update("eudr_tanks_raw_material", ["current_volume" => $new_volume]);
                if ($this->db->getLastErrno() !== 0) {
                    $this->db->rollback();
                    return null;
                }
                // Add raw material tank history
                $data_history = array(
                    'raw_material_tank_id' => $item['tank_id'],
                    'entity_type' => 'raw_material_release',
                    'entity_id' => $raw_material_release_id,
                    'action_type' => 'output',
                    'rubber_type' => $item['rubber_type'],
                    'weight' => $item['weight_requested'],
                    'volume_before' => $existingTank['current_volume'],
                    'volume_after' => $new_volume,
                    'notes' => 'Cập nhật lấy nguyên liệu thô từ phiếu xuất kho (Trước sản xuất)',
                    'created_at' => date('Y-m-d H:i:s', time()),
                    'created_by' => $data_update['updated_by'],
                );
                $this->db->insert("eudr_tanks_raw_material_history", $data_history);
                if ($this->db->getLastErrno() !== 0) {
                    $this->db->rollback();
                    return null;
                }
            }
        }

        $this->db->commit();


        return $this->findRawMaterialReleaseOfId($raw_material_release_id);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteRawMaterialRelease(int $raw_material_release_id, int $deleted_by): void
    {
        $this->db->where("material_release_id", $raw_material_release_id);
        $this->db->update('eudr_tanks_raw_material_releases', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

}
