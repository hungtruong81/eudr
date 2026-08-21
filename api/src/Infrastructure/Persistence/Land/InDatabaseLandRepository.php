<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Land;

use App\Domain\Land\Land;
use App\Domain\Land\LandNotFoundException;
use App\Domain\Land\LandRepository;
use App\Application\Utility\Utils;
use App\Application\Utility\CurrentUserContext;

class InDatabaseLandRepository implements LandRepository
{
    /**
     * @var \MysqliDb
     */
    private $db;
    /**
     * @var CurrentUserContext
     */
    private $currentUser;

    /**
     * InDatabaseLandRepository constructor.
     *
     * @param \MysqliDb $db
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
    private function scopeWhere(string $scope, int $authUserId, int $companyId, ?int $companyIdParam = null, string $alias = 'land'): void
    {
        $prefix = $alias ? $alias . '.' : '';
        $this->db->where($prefix . 'deleted_by', 0);

        if ($scope === 'self') {
            $this->db->where($prefix . 'farmer_user_id', $authUserId);
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
        $search = $params['search'] ?? '';
        $is_approved = $params['is_approved'] ?? -1;
        $province_id = $params['province_id'] ?? 0;
        $eudr_status = $params['eudr_status'] ?? -1;
        $farmer_user_id = $params['farmer_user_id'] ?? 0;
        $not_shared_with_user_id = $params['not_shared_with_user_id'] ?? 0;
        $owner_user_id = $params['user_id'] ?? 0;
        $is_vendor = $params['is_vendor'] ?? 0;

        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $companyIdParam = $company_id_param ?? null;
        $ownerUserId = $owner_user_id ?: $authUserId;
        
        $land_ids_shared = [];
        if (!empty($not_shared_with_user_id)) {
            $this->db->where("ls.shared_with_user_id", $not_shared_with_user_id);
            $this->db->where("ls.owner_id", $ownerUserId);
            $land_shared = $this->db->get("eudr_land_shares ls", null, "ls.plot_id");
            if (!empty($land_shared)) {
                $land_ids_shared = array_map(function ($item) {
                    return $item['plot_id'];
                }, $land_shared);
            }
        }
        
        // Count total records
        $total_records = 0;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'land');
        if (!empty($farmer_user_id)) {
            $this->db->where("land.farmer_user_id", $farmer_user_id);
        }

        if (!empty($search)) {
            $this->db->where("(land.plot_name LIKE '%".$search."%' OR land.plot_code LIKE '%".$search."%')");
        }
        if ($is_approved >= 0) {
            $this->db->where("land.is_approved", $is_approved);
        }
        if ($province_id > 0) {
            $this->db->where("land.province_id", $province_id);
        }
        if ($eudr_status >= 0) {
            $this->db->where("land.eudr_status", $eudr_status);
        }
        if (!empty($land_ids_shared)) {
            $this->db->where("land.plot_id", $land_ids_shared, "NOT IN");
        }
        if (!empty($register_type) && in_array($register_type, ['internal', 'external'])) {
            $this->db->where("land.register_type", $register_type);
        }
        if (!empty($is_vendor)) {
            $this->db->where("land.is_vendor", $is_vendor);
        }

        $total_records = $this->db->getValue("eudr_lands land", "count(*)");


        // Subquery để lấy plant mới nhất theo plot_id
        $latestPlantSubquery = "
            SELECT c1.*
            FROM eudr_plants c1
            INNER JOIN (
                SELECT plot_id, MAX(created_at) AS latest_created
                FROM eudr_plants
                GROUP BY plot_id
            ) c2 ON c1.plot_id = c2.plot_id AND c1.created_at = c2.latest_created
        ";

        // Set pagination
        $this->db->pageLimit = $page_limit;
        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'land');
        if (!empty($farmer_user_id)) {
            $this->db->where("land.farmer_user_id", $farmer_user_id);
        }
        if (!empty($search)) {
            $this->db->where("(land.plot_name LIKE '%".$search."%' OR land.plot_code LIKE '%".$search."%')");
        }
        if ($is_approved >= 0) {
            $this->db->where("land.is_approved", $is_approved);
        }
        if ($province_id > 0) {
            $this->db->where("land.province_id", $province_id);
        }
        if ($eudr_status >= 0) {
            $this->db->where("land.eudr_status", $eudr_status);
        }
        if (!empty($land_ids_shared)) {
            $this->db->where("land.plot_id", $land_ids_shared, "NOT IN");
        }
        if (!empty($register_type) && in_array($register_type, ['internal', 'external'])) {
            $this->db->where("land.register_type", $register_type);
        }
        if (!empty($is_vendor)) {
            $this->db->where("land.is_vendor", $is_vendor);
        }
        $cols = "land.*,
        province.province_name,
        user.full_name as farmer_name,
        f.file_path as land_document_detection,
        zone.zone_name as zone_name,
        ANY_VALUE(plant.crop_type) as crop_type,
        ANY_VALUE(plant.year_of_planting) as year_of_planting,
        ANY_VALUE(plant.plantation_name) as plantation_name";

        if (!empty($params['order_by'])) {
            $this->db->orderBy('land.'.$params['order_by'], $params['order_type']??'ASC');
        } else {
            $this->db->orderBy("land.plot_id", "DESC");
        }
        $this->db->join("eudr_users user", "user.user_id=land.farmer_user_id", "LEFT");
        $this->db->join("eudr_general_provinces province", "province.province_id=land.province_id", "LEFT");
        $this->db->join("eudr_general_files f", "f.file_id=land.land_document_detection", "LEFT");
        $this->db->join("eudr_general_vn2000_zones zone", "zone.zone_id=land.zone_id", "LEFT");
        $this->db->join("($latestPlantSubquery) plant", "plant.plot_id=land.plot_id", "LEFT");
        $records = $this->db->arraybuilder()->paginate("eudr_lands land", $page, $cols);

        $items = [];
        $all_file_ids = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = new Land($item['plot_id'], $item);
                $land_record_ids = json_decode($item['land_records'], true);
                if (!empty($land_record_ids) && is_array($land_record_ids)) {
                    $all_file_ids = array_merge($all_file_ids, $land_record_ids);
                }
    
            }
        }

        $return_data = [
            "current_page" => $page,
            "total_pages" => $this->db->totalPages,
            "total_records" => $total_records,
            "page_limit" => $this->db->pageLimit,
            "records" => $items,
            "all_file_ids" => array_unique($all_file_ids) ?? [],
        ];

        return $return_data;
    }

    /**
     * {@inheritdoc}
     */
    public function findLandOfId(int $plot_id): ?Land
    {
        $this->db->where("plot_id", $plot_id);
        $this->db->where("deleted_by", 0);

        $land = $this->db->getOne("eudr_lands");
        if (empty($land)) {
            return null;
        }
        return new Land($land['plot_id'], $land);
    }

    /**
     * {@inheritdoc}
     */
    public function findLandOfIdWithPermission(int $plot_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Land
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'land');
        $this->db->where("land.plot_id", $plot_id);
        $this->db->join("eudr_general_provinces province", "province.province_id=land.province_id", "LEFT");
        $this->db->join("eudr_users user", "user.user_id=land.farmer_user_id", "LEFT");
        $this->db->join("eudr_general_files f", "f.file_id=land.land_document_detection", "LEFT");
        $this->db->join("eudr_general_vn2000_zones zone", "zone.zone_id=land.zone_id", "LEFT");
        $land = $this->db->getOne("eudr_lands land", "land.*, province.province_name, user.full_name as farmer_name, f.file_path as land_document_detection, zone.zone_name as zone_name");
        if (empty($land)) {
            return null;
        }

        return new Land($land['plot_id'], $land);
    }

    /**
     * {@inheritdoc}
     */
    public function findLandOfCode(string $code): ?Land
    {
        $this->db->where("land.plot_code", $code);
        $this->db->where("land.deleted_by", 0);

        $this->db->join("eudr_general_provinces province", "province.province_id=land.province_id", "LEFT");
        $this->db->join("eudr_users user", "user.user_id=land.farmer_user_id", "LEFT");
        $this->db->join("eudr_general_files f", "f.file_id=land.land_document_detection", "LEFT");
        $this->db->join("eudr_general_vn2000_zones zone", "zone.zone_id=land.zone_id", "LEFT");
        $land = $this->db->getOne("eudr_lands land", "land.*, province.province_name, user.full_name as farmer_name, f.file_path as land_document_detection, zone.zone_name as zone_name");
        if (empty($land)) {
            return null;
        }
        return new Land($land['plot_id'], $land);
    }

    /**
     * {@inheritdoc}
     */
    public function findLandIdsOfOwner(array $plot_ids, int $user_id): array
    {
        if (empty($plot_ids)) {
            return [];
        }

        $this->db->where("land.plot_id", $plot_ids, "IN");
        //$this->db->where("land.created_by", $user_id);
        $this->db->where("land.farmer_user_id", $user_id);
        //$this->db->where("land.is_approved", 1);
        $this->db->where("land.deleted_by", 0);

        $lands = $this->db->get("eudr_lands land", null, "land.plot_id");
        if (empty($lands)) {
            return [];
        }
        $land_ids = array_map(function ($item) {
            return $item['plot_id'];
        }, $lands);

        return $land_ids;
    }

    /**
     * {@inheritdoc}
     */
    public function findLandOfCodeWithPermission(string $plot_code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Land
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'land');
        $this->db->where("land.plot_code", $plot_code);
        $this->db->join("eudr_general_provinces province", "province.province_id=land.province_id", "LEFT");
        $this->db->join("eudr_users user", "user.user_id=land.farmer_user_id", "LEFT");
        $this->db->join("eudr_general_files f", "f.file_id=land.land_document_detection", "LEFT");
        $this->db->join("eudr_general_vn2000_zones zone", "zone.zone_id=land.zone_id", "LEFT");
        $land = $this->db->getOne("eudr_lands land", "land.*, province.province_name, user.full_name as farmer_name, f.file_path as land_document_detection, zone.zone_name as zone_name");

        if (empty($land)) {
            return null;
        }

        return new Land($land['plot_id'], $land);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = "plot-".date("ymd").'-'.Utils::generateRandomString(8);
            $land = $this->findLandOfCode($code);
            if (!$land) {
                break;
            }
        }
        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function createLand(array $data_item): Land
    {
        foreach ($data_item as $key => $str) {
            if($key === 'coordinates' || $key === 'coordinate_origin_points') {
                $data_item[$key] = $str;
            } else {
                $data_item[$key] = $this->db->escape($str);
            }
            
        }

        $id = $this->db->insert('eudr_lands', $data_item);

        $this->db->where("land.plot_id", $id);
        $this->db->join("eudr_general_provinces province", "province.province_id=land.province_id", "LEFT");
        $this->db->join("eudr_users user", "user.user_id=land.farmer_user_id", "LEFT");
        $this->db->join("eudr_general_files f", "f.file_id=land.land_document_detection", "LEFT");
        $this->db->join("eudr_general_vn2000_zones zone", "zone.zone_id=land.zone_id", "LEFT");
        $land = $this->db->getOne("eudr_lands land", "land.*, province.province_name, user.full_name as farmer_name, f.file_path as land_document_detection, zone.zone_name as zone_name");
        if (empty($land)) {
            throw new LandNotFoundException();
        }
        return new Land($land['plot_id'], $land);
    }
    /**
     * {@inheritdoc}
     */
    public function updateLand(int $plot_id, array $data_item): Land
    {
        foreach ($data_item as $key => $str) {
            if($key === 'coordinates' || $key === 'coordinate_origin_points') {
                $data_item[$key] = $str;
            } else {
                $data_item[$key] = $this->db->escape($str);
            }
        }

        $this->db->where("plot_id", $plot_id);
        $updated = $this->db->update('eudr_lands', $data_item);

        if (!$updated) {
            throw new LandNotFoundException();
        }

        $this->db->where("land.plot_id", $plot_id);
        $this->db->join("eudr_general_provinces province", "province.province_id=land.province_id", "LEFT");
        $this->db->join("eudr_users user", "user.user_id=land.farmer_user_id", "LEFT");
        $this->db->join("eudr_general_files f", "f.file_id=land.land_document_detection", "LEFT");
        $this->db->join("eudr_general_vn2000_zones zone", "zone.zone_id=land.zone_id", "LEFT");
        $land = $this->db->getOne("eudr_lands land", "land.*, province.province_name, user.full_name as farmer_name, f.file_path as land_document_detection, zone.zone_name as zone_name");
        if (empty($land)) {
            throw new LandNotFoundException();
        }
        return new Land($land['plot_id'], $land);
    }

    /**
     * {@inheritdoc}
     */
    public function updateLandWithPermission(int $plot_id, array $data_item, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): Land
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        foreach ($data_item as $key => $str) {
            if($key === 'coordinates' || $key === 'coordinate_origin_points') {
                $data_item[$key] = $str;
            } else {
                $data_item[$key] = $this->db->escape($str);
            }
        }

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'land');
        $this->db->where('land.plot_id', $plot_id);
        $updated = $this->db->update('eudr_lands land', $data_item);

        if (!$updated || $this->db->count === 0) {
            throw new LandNotFoundException();
        }

        return $this->findLandOfIdWithPermission($plot_id, $auth_user_id, $scope, $company_id, $company_id_param);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteLand(int $plot_id, int $deleted_by): void
    {
        $this->db->where("plot_id", $plot_id);
        $this->db->update('eudr_lands', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteLandWithPermission(int $plot_id, int $deleted_by, string $scope, ?int $company_id = null, ?int $company_id_param = null): void
    {
        $authUserId = $deleted_by;
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'land');
        $this->db->where('land.plot_id', $plot_id);
        $this->db->update('eudr_lands land', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalLand(): int
    {
        $total = 0;
        $this->db->where("deleted_by", 0);
        $total = $this->db->getValue("eudr_lands", "count(*)");

        return $total;
    }

    /**
     * {@inheritdoc}
     */
    public function checkDuplicateCoordinates($coords_input, $tolerance = 0.000001, $plot_id = 0): bool
    {
        // Nếu không có dữ liệu thì trả về false luôn
        if (empty($coords_input)) {
            return false;
        }

        // Mảng điều kiện WHERE
        $conditions = [];
        foreach ($coords_input as $coord) {
            $lat = floatval($coord['lat']);
            $lng = floatval($coord['lng']);
            $conditions[] = "(ABS(c.lat - {$lat}) <= {$tolerance} AND ABS(c.lng - {$lng}) <= {$tolerance})";
        }

        // Ghép điều kiện
        $whereClause = implode(" OR ", $conditions);
        $whereClause = "l.deleted_by = 0 AND register_type = 'internal' AND ({$whereClause})";
        if ($plot_id > 0) {
            $whereClause .= " AND l.plot_id != {$plot_id}";
        }

        // Câu SQL
        $sql = "
            SELECT 1
            FROM eudr_lands l,
            JSON_TABLE(
                l.coordinates, '$[*]' COLUMNS(
                    lat DOUBLE PATH '$.lat',
                    lng DOUBLE PATH '$.lng'
                )
            ) AS c
            WHERE {$whereClause}
            LIMIT 1
        ";

        // Thực thi query
        $result = $this->db->rawQuery($sql);

        // Nếu có bản ghi thì trùng
        return !empty($result);
    }

    /**
     * {@inheritdoc}
     */
    public function shareLand(array $plot_ids, int $shared_with_user_id, int $owner_user_id): void 
    {
        // Xoá các bản ghi chia sẻ cũ (nếu có)
        $this->db->where("plot_id", $plot_ids, "IN");
        $this->db->where("shared_with_user_id", $shared_with_user_id);
        $this->db->where("owner_id", $owner_user_id);
        $this->db->where("deleted_by", 0);
        $this->db->update("eudr_land_shares", ["deleted_by" => $owner_user_id, "deleted_at" => date("Y-m-d H:i:s", time())]);
        // Thêm mới các bản ghi chia sẻ
        $time_now = date("Y-m-d H:i:s", time());
        foreach ($plot_ids as $plot_id) {
            $data_insert = [
                "plot_id" => $plot_id,
                "owner_id" => $owner_user_id,
                "shared_with_user_id" => $shared_with_user_id,
                "created_by" => $owner_user_id,
                "created_at" => $time_now,
                "updated_by" => 0,
                "updated_at" => NULL,
                "deleted_by" => 0,
                "deleted_at" => NULL
            ];
            $this->db->insert("eudr_land_shares", $data_insert);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMySharedLands(int $user_id, array $params): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $search = $params['search'] ?? '';
        $is_approved = $params['is_approved'] ?? -1;
        $province_id = $params['province_id'] ?? 0;
        $eudr_status = $params['eudr_status'] ?? -1;
        $farmer_user_id = $params['farmer_user_id'] ?? 0;
        $status = $params['status'] ?? 'all'; // all, active, revoked
        $owner_id = $params['owner_id'] ?? 0;

        // Count total records
        $total_records = 0;
        $this->db->where("land.deleted_by", 0);
        $this->db->where("ls.shared_with_user_id", $user_id);
        if (!empty($farmer_user_id)) {
            $this->db->where("land.farmer_user_id", $farmer_user_id);
        }
        if (!empty($search)) {
            $this->db->where("(land.plot_name LIKE '%".$search."%' OR land.plot_code LIKE '%".$search."%')");
        }
        if ($is_approved >= 0) {
            $this->db->where("land.is_approved", $is_approved);
        }
        if ($province_id > 0) {
            $this->db->where("land.province_id", $province_id);
        }
        if ($eudr_status >= 0) {
            $this->db->where("land.eudr_status", $eudr_status);
        }
        if ($status !== 'all') {
            $this->db->where("ls.status", $status);
        }
        if ($owner_id > 0) {
            $this->db->where("ls.owner_id", $owner_id);
        }
        $this->db->where("ls.deleted_by", 0);
        $this->db->join("eudr_lands land", "land.plot_id=ls.plot_id", "LEFT");
        $total_records = $this->db->getValue("eudr_land_shares ls", "count(*)");

        // Subquery để lấy plant mới nhất theo plot_id
        $latestPlantSubquery = "
            SELECT c1.*
            FROM eudr_plants c1
            INNER JOIN (
                SELECT plot_id, MAX(created_at) AS latest_created
                FROM eudr_plants
                GROUP BY plot_id
            ) c2 ON c1.plot_id = c2.plot_id AND c1.created_at = c2.latest_created
        ";

        // Set pagination
        $this->db->pageLimit = $page_limit;
        if (!empty($farmer_user_id)) {
            $this->db->where("land.farmer_user_id", $farmer_user_id);
        }
        if (!empty($search)) {
            $this->db->where("(land.plot_name LIKE '%".$search."%' OR land.plot_code LIKE '%".$search."%')");
        }
        if ($is_approved >= 0) {
            $this->db->where("land.is_approved", $is_approved);
        }
        if ($province_id > 0) {
            $this->db->where("land.province_id", $province_id);
        }
        if ($eudr_status >= 0) {
            $this->db->where("land.eudr_status", $eudr_status);
        }
        if ($status !== 'all') {
            $this->db->where("ls.status", $status);
        }
        if ($owner_id > 0) {
            $this->db->where("ls.owner_id", $owner_id);
        }

        $cols = "land.*,
        province.province_name,
        user.full_name as farmer_name,
        user.phone as phone,
        user.email as email,
        user.register_type as register_type,
        f.file_path as land_document_detection,
        zone.zone_name as zone_name,
        ANY_VALUE(plant.crop_type) as crop_type,
        ANY_VALUE(plant.year_of_planting) as year_of_planting,
        ANY_VALUE(plant.plantation_name) as plantation_name";

        $this->db->where("land.deleted_by", 0);
        $this->db->where("ls.shared_with_user_id", $user_id);
        $this->db->where("ls.deleted_by", 0);
        if (!empty($params['order_by'])) {
            $this->db->orderBy('land.'.$params['order_by'], $params['order_type']??'ASC');
        } else {
            $this->db->orderBy("land.plot_id", "DESC");
        }
        
        $this->db->join("eudr_lands land", "land.plot_id=ls.plot_id", "LEFT");
        $this->db->join("($latestPlantSubquery) plant", "plant.plot_id=land.plot_id", "LEFT");
        $this->db->join("eudr_users user", "user.user_id=land.farmer_user_id", "LEFT");
        $this->db->join("eudr_general_provinces province", "province.province_id=land.province_id", "LEFT");
        $this->db->join("eudr_general_files f", "f.file_id=land.land_document_detection", "LEFT");
        $this->db->join("eudr_general_vn2000_zones zone", "zone.zone_id=land.zone_id", "LEFT");
        $records = $this->db->arraybuilder()->paginate("eudr_land_shares ls", $page, $cols);

        $items = [];
        $all_file_ids = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = new Land($item['plot_id'], $item);
                $land_record_ids = json_decode($item['land_records'], true);
                if (!empty($land_record_ids) && is_array($land_record_ids)) {
                    $all_file_ids = array_merge($all_file_ids, $land_record_ids);
                }
            }
        }

        $return_data = [
            "current_page" => $page,
            "total_pages" => $this->db->totalPages,
            "total_records" => $total_records,
            "page_limit" => $this->db->pageLimit,
            "records" => $items,
            "all_file_ids" => array_unique($all_file_ids) ?? [],
        ];

        return $return_data;
    }

    /**
     * {@inheritdoc}
     */
    public function getListUserSharedLand(int $plot_id, int $user_id, array $params): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;

        // Count total records
        $total_records = 0;
        $this->db->where("ls.status", "active");
        $this->db->where("ls.plot_id", $plot_id);
        $this->db->where("ls.owner_id", $user_id);
        $this->db->where("ls.deleted_by", 0);
        $total_records = $this->db->getValue("eudr_land_shares ls", "count(*)");

        // Set pagination
        $this->db->pageLimit = $page_limit;
        $this->db->where("ls.status", "active");
        $this->db->where("ls.plot_id", $plot_id);
        $this->db->where("ls.owner_id", $user_id);
        $this->db->where("ls.deleted_by", 0);
        $this->db->orderBy("ls.land_share_id", "DESC");
        $cols = "u.user_id,u.user_code,u.full_name, u.phone, u.email,u.register_type";
        $this->db->join("eudr_users u", "u.user_id=ls.shared_with_user_id", "LEFT");
        $records = $this->db->arraybuilder()->paginate("eudr_land_shares ls", $page, $cols);

        $return_data = [
            "current_page" => $page,
            "total_pages" => $this->db->totalPages,
            "total_records" => $total_records,
            "page_limit" => $this->db->pageLimit,
            "records" => $records,
        ];

        return $return_data;
    }

    /**
     * {@inheritdoc}
     */
    public function revokeShareLand(int $plot_id, int $owner_user_id, int $shared_with_user_id): void
    {
        $this->db->where("plot_id", $plot_id);
        $this->db->where("shared_with_user_id", $shared_with_user_id);
        $this->db->where("owner_id", $owner_user_id);
        $this->db->where("deleted_by", 0);
        $data_shared = $this->db->getOne("eudr_land_shares");
        if (empty($data_shared)) {
            return;
        }
        if ($data_shared['status'] == 'active') {
            $data_update = [
                "status" => "revoked",
                "updated_by" => $owner_user_id,
                "updated_at" => date("Y-m-d H:i:s", time()),
                //"deleted_by" => $owner_user_id,
                //"deleted_at" => date("Y-m-d H:i:s", time())
            ];
            $this->db->where("plot_id", $plot_id);
            $this->db->where("shared_with_user_id", $shared_with_user_id);
            $this->db->where("owner_id", $owner_user_id);
            $this->db->where("deleted_by", 0);
            $this->db->update("eudr_land_shares", $data_update);
        }
        return;
    }

    /**
     * {@inheritdoc}
     */
    public function listLandOfSeller(int $seller_user_id, array $params): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $search = $params['search'] ?? '';

        // Count total records
        $total_records = 0;
        if (!empty($search)) {
            $this->db->where("(land.plot_name LIKE '%".$search."%' OR land.plot_code LIKE '%".$search."%')");
        }
        $this->db->where("land.deleted_by", 0);
        $this->db->where("land.is_approved", 1);
        $this->db->where("land.farmer_user_id", $seller_user_id);
        $total_records = $this->db->getValue("eudr_lands land", "count(*)");


        // Subquery để lấy plant mới nhất theo plot_id
        $latestPlantSubquery = "
            SELECT c1.*
            FROM eudr_plants c1
            INNER JOIN (
                SELECT plot_id, MAX(created_at) AS latest_created
                FROM eudr_plants
                GROUP BY plot_id
            ) c2 ON c1.plot_id = c2.plot_id AND c1.created_at = c2.latest_created
        ";
        // Set pagination
        $this->db->pageLimit = $page_limit;
        $cols = "land.plot_id,
        land.plot_code,
        land.plot_name,
        land.land_area,
        land.address,
        ANY_VALUE(plant.crop_type) as crop_type,
        ANY_VALUE(plant.year_of_planting) as year_of_planting,
        ANY_VALUE(plant.plantation_name) as plantation_name";
        if (!empty($search)) {
            $this->db->where("(land.plot_name LIKE '%".$search."%' OR land.plot_code LIKE '%".$search."%')");
        }
        $this->db->where("land.deleted_by", 0);
        $this->db->where("land.is_approved", 1);
        $this->db->where("land.farmer_user_id", $seller_user_id);
        $this->db->orderBy("land.plot_id", "DESC");
        $this->db->join("($latestPlantSubquery) plant", "plant.plot_id=land.plot_id", "LEFT");
        $records = $this->db->arraybuilder()->paginate("eudr_lands land", $page, $cols);

        $return_data = [
            "current_page" => $page,
            "total_pages" => $this->db->totalPages,
            "total_records" => $total_records,
            "page_limit" => $this->db->pageLimit,
            "records" => $records,
        ];

        return $return_data;
    }

    /**
     * {@inheritdoc}
     */
    public function listLandByTransactionTicket(int $transaction_ticket_id, array $params): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $search = $params['search'] ?? '';

        // Tìm tất cả phiếu gốc (mua từ nông hộ) thông qua trace ngược chuỗi giao dịch
        $original_ticket_ids = $this->traceOriginalTickets($transaction_ticket_id);

        // Loại bỏ duplicate IDs
        $original_ticket_ids = array_unique($original_ticket_ids);

        // Count total records từ tất cả các phiếu gốc
        $total_records = 0;
        $this->db->where("tll.transaction_ticket_id", $original_ticket_ids, "IN");
        $total_records = $this->db->getValue("eudr_transaction_ticket_land_links tll", "count(DISTINCT tll.plot_id)");

        // Subquery để lấy plant mới nhất theo plot_id
        $latestPlantSubquery = "
            SELECT c1.*
            FROM eudr_plants c1
            INNER JOIN (
                SELECT plot_id, MAX(created_at) AS latest_created
                FROM eudr_plants
                GROUP BY plot_id
            ) c2 ON c1.plot_id = c2.plot_id AND c1.created_at = c2.latest_created
        ";
        
        // Set pagination
        $this->db->pageLimit = $page_limit;
        $cols = "
        land.plot_id,
        land.plot_code,
        land.plot_name,
        land.land_area,
        land.address,
        ANY_VALUE(plant.crop_type) as crop_type,
        ANY_VALUE(plant.year_of_planting) as year_of_planting,
        ANY_VALUE(plant.plantation_name) as plantation_name";
        
        $this->db->where("land.deleted_by", 0);
        //$this->db->where("land.is_approved", 1);
        $this->db->where("tll.transaction_ticket_id", $original_ticket_ids, "IN");
        $this->db->groupBy("land.plot_id"); // Group để tránh duplicate lands
        //$this->db->orderBy("land.plot_id", "DESC");
        $this->db->join("eudr_lands land", "land.plot_id=tll.plot_id", "LEFT");
        $this->db->join("($latestPlantSubquery) plant", "plant.plot_id=land.plot_id", "LEFT");
        $records = $this->db->arraybuilder()->paginate("eudr_transaction_ticket_land_links tll", $page, $cols);

        $return_data = [
            "current_page" => $page,
            "total_pages" => $this->db->totalPages,
            "total_records" => $total_records,
            "page_limit" => $this->db->pageLimit,
            "records" => $records,
        ];

        return $return_data;
    }

    /**
     * {@inheritdoc}
     */
    public function getSharedLandByUser(array $params): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $user_id = $params['user_id'] ?? 0;
        $shared_with_user_id = $params['shared_with_user_id'] ?? 0;
        $status = $params['status'] ?? 'all';

        // Count total records
        $total_records = 0;
        if($status != 'all') {
            $this->db->where("ls.status", $status);
        }
        $this->db->where("ls.shared_with_user_id", $shared_with_user_id);
        $this->db->where("ls.owner_id", $user_id);
        $this->db->where("ls.deleted_by", 0);
        $total_records = $this->db->getValue("eudr_land_shares ls", "count(*)");

        // Subquery để lấy plant mới nhất theo plot_id
        $latestPlantSubquery = "
            SELECT c1.*
            FROM eudr_plants c1
            INNER JOIN (
                SELECT plot_id, MAX(created_at) AS latest_created
                FROM eudr_plants
                GROUP BY plot_id
            ) c2 ON c1.plot_id = c2.plot_id AND c1.created_at = c2.latest_created
        ";

        // Set pagination
        $this->db->pageLimit = $page_limit;
        $cols = "
        land.plot_id,
        land.plot_code,
        land.plot_name,
        land.land_area,
        land.address,
        ls.status as share_status,
        ANY_VALUE(plant.crop_type) as crop_type,
        ANY_VALUE(plant.year_of_planting) as year_of_planting,
        ANY_VALUE(plant.plantation_name) as plantation_name";

        if($status != 'all') {
            $this->db->where("ls.status", $status);
        }
        //$this->db->where("land.deleted_by", 0);
        $this->db->where("ls.shared_with_user_id", $shared_with_user_id);
        $this->db->where("ls.owner_id", $user_id);
        $this->db->where("ls.deleted_by", 0);
        $this->db->join("eudr_lands land", "land.plot_id=ls.plot_id", "LEFT");
        $this->db->join("($latestPlantSubquery) plant", "plant.plot_id=land.plot_id", "LEFT");
        $records = $this->db->arraybuilder()->paginate("eudr_land_shares ls", $page, $cols);

        $return_data = [
            "current_page" => $page,
            "total_pages" => $this->db->totalPages,
            "total_records" => $total_records,
            "page_limit" => $this->db->pageLimit,
            "records" => $records,
        ];

        return $return_data;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllSharedLand(array $params): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $user_id = $params['user_id'] ?? 0;
        $search = $params['search'] ?? '';

        // Count total records
        $total_records = 0;
        $this->db->where("ls.owner_id", $user_id);
        $this->db->where("ls.deleted_by", 0);
        $this->db->where("ls.status", "active");
        if(!empty($search)) {
            $this->db->where("(land.plot_name LIKE '%".$search."%' OR land.plot_code LIKE '%".$search."%')");
        }
        $this->db->join("eudr_lands land", "land.plot_id=ls.plot_id", "LEFT");
        $total_records = $this->db->getValue("eudr_land_shares ls", "count(DISTINCT ls.plot_id)");


        // Subquery để lấy plant mới nhất theo plot_id
        $latestPlantSubquery = "
            SELECT c1.*
            FROM eudr_plants c1
            INNER JOIN (
                SELECT plot_id, MAX(created_at) AS latest_created
                FROM eudr_plants
                GROUP BY plot_id
            ) c2 ON c1.plot_id = c2.plot_id AND c1.created_at = c2.latest_created
        ";

        // Set pagination
        $this->db->pageLimit = $page_limit;
        $cols = "
        land.plot_id,
        land.plot_code,
        land.plot_name,
        land.land_area,
        land.address,
        COUNT(DISTINCT ls.shared_with_user_id) as total_shared,
        ANY_VALUE(plant.crop_type) as crop_type,
        ANY_VALUE(plant.year_of_planting) as year_of_planting,
        ANY_VALUE(plant.plantation_name) as plantation_name";

        $this->db->where("ls.owner_id", $user_id);
        $this->db->where("ls.deleted_by", 0);
        $this->db->where("ls.status", "active");
        if(!empty($search)) {
            $this->db->where("(land.plot_name LIKE '%".$search."%' OR land.plot_code LIKE '%".$search."%')");
        }
        $this->db->join("eudr_lands land", "land.plot_id=ls.plot_id", "LEFT");
        $this->db->join("($latestPlantSubquery) plant", "plant.plot_id=land.plot_id", "LEFT");
        $this->db->groupBy("ls.plot_id");
        $records = $this->db->arraybuilder()->paginate("eudr_land_shares ls", $page, $cols);

        $return_data = [
            "current_page" => $page,
            "total_pages" => $this->db->totalPages,
            "total_records" => $total_records,
            "page_limit" => $this->db->pageLimit,
            "records" => $records,
        ];

        return $return_data;
    }

    /**
     * Trace ngược chuỗi giao dịch để tìm tất cả phiếu gốc (mua từ nông hộ)
     * 
     * @param int $transaction_ticket_id
     * @param array $visited Mảng để tránh vòng lặp vô hạn
     * @return array Mảng chứa tất cả ticket IDs gốc
     */
    private function traceOriginalTickets(int $transaction_ticket_id, array $visited = []): array
    {
        // Tránh vòng lặp vô hạn
        if (in_array($transaction_ticket_id, $visited)) {
            return [];
        }
        
        $visited[] = $transaction_ticket_id;
        $result_ids = [$transaction_ticket_id]; // Bao gồm chính ticket hiện tại

        // Tìm tất cả purchase tickets liên kết với sale ticket hiện tại
        $this->db->where("sale_ticket_id", $transaction_ticket_id);
        $linked_tickets = $this->db->get("eudr_transaction_ticket_sale_purchase_links", null, "purchase_ticket_id");
        
        if (!empty($linked_tickets)) {
            foreach ($linked_tickets as $ticket) {
                $purchase_ticket_id = $ticket['purchase_ticket_id'];
                
                // Recursive trace cho từng purchase ticket
                $sub_tickets = $this->traceOriginalTickets($purchase_ticket_id, $visited);
                $result_ids = array_merge($result_ids, $sub_tickets);
            }
        }

        return $result_ids;
    }



    /**
     * {@inheritdoc}
     */
    public function findLandSupport($params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $search = $params['search'] ?? '';
        $is_approved = $params['is_approved'] ?? -1;
        $province_id = $params['province_id'] ?? 0;
        $eudr_status = $params['eudr_status'] ?? -1;
        $farmer_user_id = $params['farmer_user_id'] ?? 0;

        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        // Count total records
        $total_records = 0;
        if (!empty($farmer_user_id)) {
            $this->db->where("land.farmer_user_id", $farmer_user_id);
        }
        if (!empty($search)) {
            $this->db->where("(land.plot_name LIKE '%".$search."%' OR land.plot_code LIKE '%".$search."%')");
        }
        if ($is_approved >= 0) {
            $this->db->where("land.is_approved", $is_approved);
        }
        if ($province_id > 0) {
            $this->db->where("land.province_id", $province_id);
        }
        if ($eudr_status >= 0) {
            $this->db->where("land.eudr_status", $eudr_status);
        }
        $this->db->where("land.deleted_by", 0);
        $this->db->where("land.created_by", $authUserId);
        $total_records = $this->db->getValue("eudr_lands land", "count(*)");


        // Subquery để lấy plant mới nhất theo plot_id
        $latestPlantSubquery = "
            SELECT c1.*
            FROM eudr_plants c1
            INNER JOIN (
                SELECT plot_id, MAX(created_at) AS latest_created
                FROM eudr_plants
                GROUP BY plot_id
            ) c2 ON c1.plot_id = c2.plot_id AND c1.created_at = c2.latest_created
        ";

        // Set pagination
        $this->db->pageLimit = $page_limit;

        if (!empty($farmer_user_id)) {
            $this->db->where("land.farmer_user_id", $farmer_user_id);
        }
        if (!empty($search)) {
            $this->db->where("(land.plot_name LIKE '%".$search."%' OR land.plot_code LIKE '%".$search."%')");
        }
        if ($is_approved >= 0) {
            $this->db->where("land.is_approved", $is_approved);
        }
        if ($province_id > 0) {
            $this->db->where("land.province_id", $province_id);
        }
        if ($eudr_status >= 0) {
            $this->db->where("land.eudr_status", $eudr_status);
        }

        $this->db->where("land.deleted_by", 0);
        $this->db->where("land.created_by", $authUserId);

        $cols = "land.*,
        province.province_name,
        user.full_name as farmer_name,
        f.file_path as land_document_detection,
        zone.zone_name as zone_name,
        ANY_VALUE(plant.crop_type) as crop_type,
        ANY_VALUE(plant.year_of_planting) as year_of_planting,
        ANY_VALUE(plant.plantation_name) as plantation_name";

        if (!empty($params['order_by'])) {
            $this->db->orderBy('land.'.$params['order_by'], $params['order_type']??'ASC');
        } else {
            $this->db->orderBy("land.plot_id", "DESC");
        }
        $this->db->join("eudr_users user", "user.user_id=land.farmer_user_id", "LEFT");
        $this->db->join("eudr_general_provinces province", "province.province_id=land.province_id", "LEFT");
        $this->db->join("eudr_general_files f", "f.file_id=land.land_document_detection", "LEFT");
        $this->db->join("eudr_general_vn2000_zones zone", "zone.zone_id=land.zone_id", "LEFT");
        $this->db->join("($latestPlantSubquery) plant", "plant.plot_id=land.plot_id", "LEFT");
        $records = $this->db->arraybuilder()->paginate("eudr_lands land", $page, $cols);

        $items = [];
        $all_file_ids = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = new Land($item['plot_id'], $item);
                $land_record_ids = json_decode($item['land_records'], true);
                if (!empty($land_record_ids) && is_array($land_record_ids)) {
                    $all_file_ids = array_merge($all_file_ids, $land_record_ids);
                }
    
            }
        }

        $return_data = [
            "current_page" => $page,
            "total_pages" => $this->db->totalPages,
            "total_records" => $total_records,
            "page_limit" => $this->db->pageLimit,
            "records" => $items,
            "all_file_ids" => array_unique($all_file_ids) ?? [],
        ];

        return $return_data;
    }



}