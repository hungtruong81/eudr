<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Plant;

use App\Application\Utility\CurrentUserContext;
use App\Domain\Plant\Plant;
use App\Domain\Plant\PlantNotFoundException;
use App\Domain\Plant\PlantRepository;
use App\Application\Utility\Utils;

class InDatabasePlantRepository implements PlantRepository
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
     * InDatabasePlantRepository constructor.
     *
     * @param MysqliDb $db
     */
    public function __construct(\MysqliDb $db, CurrentUserContext $currentUserContext)
    {
        $this->db = $db;
        $this->currentUser = $currentUserContext;
    }

    /**
     * Apply scope-based filtering (self/own/all).
     */
    private function scopeWhere(string $scope, int $authUserId, int $companyId, ?int $companyIdParam = null, string $alias = 'plant'): void
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
        $search = $params['search'] ?? '';
        $plot_id = $params['plot_id'] ?? 0;
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $companyIdParam = $company_id_param ?? null;

        // Count total records
        $total_records = 0;
        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'plant');
        if (!empty($search)) {
            $this->db->where("(plant.plantation_name LIKE '%".$search."%')");
        }
        if(!empty($plot_id)) {
            $this->db->where("plant.plot_id", $plot_id);
        }
        $total_records = $this->db->getValue("eudr_plants plant", "count(*)");


        // Set pagination
        $this->db->pageLimit = $page_limit;
        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'plant');
        if (!empty($search)) {
            $this->db->where("(plant.plantation_name LIKE '%".$search."%')");
        }
        if(!empty($plot_id)) {
            $this->db->where("plant.plot_id", $plot_id);
        }
        $cols = "plant.*,land.plot_name,land.plot_code";

        if (!empty($params['order_by'])) {
            $this->db->orderBy('plant.'.$params['order_by'], $params['order_type']??'ASC');
        } else {
            $this->db->orderBy("plant.plant_id", "DESC");
        }
        $this->db->join("eudr_lands land", "land.plot_id=plant.plot_id", "LEFT");
        $records = $this->db->arraybuilder()->paginate("eudr_plants plant", $page, $cols);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = new Plant($item['plot_id'], $item);
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
    public function findPlantOfCodeWithPermission(string $plant_code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Plant
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'plant');
        $this->db->where("plant.plant_code", $plant_code);
        $this->db->join("eudr_lands land", "land.plot_id=plant.plot_id", "LEFT");
        $plant = $this->db->getOne("eudr_plants plant", "plant.*, land.plot_name, land.plot_code");

        if (empty($plant)) {
            return null;
        }

        return new Plant($plant['plant_id'], $plant);
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalPlant(): int
    {
        $total = 0;
        $this->db->where("is_deleted", 0);
        $total = $this->db->getValue("eudr_plants","count(*)");

        return $total;
    }
    /**
     * {@inheritdoc}
     */
    public function findAllCropTypes(): array
    {
        $records = $this->db->get("eudr_plants_crop_types", null, "crop_type_name");
        return $records;
    }

    /**
     * {@inheritdoc}
     */
    public function findPlantOfCode(string $code): ?Plant
    {
        $this->db->where("plant.plant_code", $code);
        $this->db->where("plant.deleted_by", 0);
        $plant = $this->db->getOne("eudr_plants plant");
        if (empty($plant)) {
            return null;
        }
        return new Plant($plant['plant_id'], $plant);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = "plnt-".date("ymd").'-'.Utils::generateRandomString(8);
            $plant = $this->findPlantOfCode($code);
            if (!$plant) {
                break;
            }
        }
        return $code;
    }
    
    /**
     * {@inheritdoc}
     */
    public function findPlantOfId(int $plant_id): ?Plant
    {
        $this->db->where("plant.plant_id", $plant_id);
        $this->db->where("plant.deleted_by", 0);
        $this->db->join("eudr_lands land", "land.plot_id=plant.plot_id", "LEFT");
        $plant = $this->db->getOne("eudr_plants plant", "plant.*,land.plot_name,land.plot_code");
        if (empty($plant)) {
            return null;
        }
        return new Plant($plant['plant_id'], $plant);
    }

    /**
     * {@inheritdoc}
     */
    public function findPlantOfIdWithPermission(int $plant_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Plant
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'plant');
        $this->db->where('plant.plant_id', $plant_id);
        $this->db->join("eudr_lands land", "land.plot_id=plant.plot_id", "LEFT");
        $plant = $this->db->getOne("eudr_plants plant", "plant.*,land.plot_name,land.plot_code");
        if (empty($plant)) {
            return null;
        }

        return new Plant($plant['plant_id'], $plant);
    }

    /**
     * {@inheritdoc}
     */
    public function createPlant(array $data): ?Plant
    {
        $data['created_by'] = $data['created_by'] ?? 0;
        $data['created_at'] = date("Y-m-d H:i:s", time());
        $this->db->insert("eudr_plants", $data);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }
        $plant_id = $this->db->getInsertId();
        return $this->findPlantOfId($plant_id);
    }

    /**
     * {@inheritdoc}
     */
    public function updatePlant(int $plant_id, array $data_update): Plant
    {
        $this->db->where("plant_id", $plant_id);
        $this->db->update("eudr_plants", $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new PlantNotFoundException("Plant not found with ID: $plant_id");
        }
        return $this->findPlantOfId($plant_id);
    }

    /**
     * {@inheritdoc}
     */
    public function updatePlantWithPermission(int $plant_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): Plant
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'plant');
        $this->db->where('plant.plant_id', $plant_id);
        $this->db->update('eudr_plants plant', $data_update);
        if ($this->db->getLastErrno() !== 0 || $this->db->count === 0) {
            throw new PlantNotFoundException("Plant not found with ID: $plant_id");
        }

        return $this->findPlantOfIdWithPermission($plant_id, $auth_user_id, $scope, $company_id, $company_id_param);
    }

    /**
     * {@inheritdoc}
     */
    public function deletePlant(int $plant_id, int $deleted_by): void
    {
        $this->db->where("plant_id", $plant_id);
        $this->db->update('eudr_plants', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

    /**
     * {@inheritdoc}
     */
    public function deletePlantWithPermission(int $plant_id, int $deleted_by, string $scope, ?int $company_id = null, ?int $company_id_param = null): void
    {
        $authUserId = $deleted_by;
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'plant');
        $this->db->where('plant.plant_id', $plant_id);
        $this->db->update('eudr_plants plant', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

}
