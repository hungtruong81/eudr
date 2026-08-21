<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Vehicle;

use App\Application\Utility\CurrentUserContext;
use App\Domain\Vehicle\Vehicle;
use App\Domain\Vehicle\VehicleNotFoundException;
use App\Domain\Vehicle\VehicleRepository;
use App\Application\Utility\Utils;

class InDatabaseVehicleRepository implements VehicleRepository
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
     * InDatabaseVehicleRepository constructor.
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
        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'v');
        if (!empty($search)) {
            $this->db->where('(v.vehicle_name LIKE ? OR v.license_plate LIKE ?)', ["%$search%", "%$search%"]);
        }
        $total_records = (int)$this->db->getValue("eudr_transportation_vehicle v", "count(*)");


        // Set pagination
        $this->db->pageLimit = $page_limit;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'v');
        if (!empty($search)) {
            $this->db->where('(v.vehicle_name LIKE ? OR v.license_plate LIKE ?)', ["%$search%", "%$search%"]);
        }

        $cols = "v.*";

        if (!empty($params['order_by'])) {
            $this->db->orderBy('v.'.$params['order_by'], $params['order_type']??'ASC');
        } else {
            $this->db->orderBy("v.vehicle_id", "DESC");
        }
        $records = $this->db->arraybuilder()->paginate("eudr_transportation_vehicle v", $page, $cols);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = new Vehicle($item['vehicle_id'], $item);
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
    public function findVehicleOfId(int $vehicle_id): ?Vehicle
    {
        $this->db->where("v.vehicle_id", $vehicle_id);
        $this->db->where("v.deleted_by", 0);
        $vehicle = $this->db->getOne("eudr_transportation_vehicle v", "v.*");
        if (empty($vehicle)) {
            return null;
        }
        return new Vehicle($vehicle['vehicle_id'], $vehicle);
    }

    /**
     * {@inheritdoc}
     */
    public function findVehicleOfLicensePlate(string $license_plate): ?Vehicle
    {
        $this->db->where("v.license_plate", $license_plate);
        $this->db->where("v.deleted_by", 0);
        $vehicle = $this->db->getOne("eudr_transportation_vehicle v", "v.*");
        if (empty($vehicle)) {
            return null;
        }
        return new Vehicle($vehicle['vehicle_id'], $vehicle);
    }

    /**
     * {@inheritdoc}
     */
    public function findVehicleOfCode(string $code): ?Vehicle
    {
        $this->db->where("v.vehicle_code", $code);
        $this->db->where("v.deleted_by", 0);
        $vehicle = $this->db->getOne("eudr_transportation_vehicle v", "v.*");
        if (empty($vehicle)) {
            return null;
        }
        return new Vehicle($vehicle['vehicle_id'], $vehicle);
    }

    /**
     * {@inheritdoc}
     */
    public function findVehicleOfIdWithPermission(int $vehicle_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Vehicle
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'v');
        $this->db->where('v.vehicle_id', $vehicle_id);

        $vehicle = $this->db->getOne('eudr_transportation_vehicle v', 'v.*');
        if (empty($vehicle)) {
            return null;
        }

        return new Vehicle($vehicle['vehicle_id'], $vehicle);
    }

    /**
     * {@inheritdoc}
     */
    public function findVehicleOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Vehicle
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'v');
        $this->db->where('v.vehicle_code', $code);

        $vehicle = $this->db->getOne('eudr_transportation_vehicle v', 'v.*');
        if (empty($vehicle)) {
            return null;
        }

        return new Vehicle($vehicle['vehicle_id'], $vehicle);
    }

    /**
     * {@inheritdoc}
     */
    public function findVehicleOfLicensePlateWithPermission(string $license_plate, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Vehicle
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'v');
        $this->db->where('v.license_plate', $license_plate);

        $vehicle = $this->db->getOne('eudr_transportation_vehicle v', 'v.*');
        if (empty($vehicle)) {
            return null;
        }

        return new Vehicle($vehicle['vehicle_id'], $vehicle);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = "vehc-".date("ymd").'-'.Utils::generateRandomString(8);
            $vehicle = $this->findVehicleOfCode($code);
            if (!$vehicle) {
                break;
            }
        }
        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function createVehicle(array $data): ?Vehicle
    {
        $this->db->insert("eudr_transportation_vehicle", $data);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }

        $vehicle_id = $this->db->getInsertId();

        return $this->findVehicleOfId($vehicle_id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateVehicle(int $vehicle_id, array $data_update): Vehicle
    {
        $this->db->where("vehicle_id", $vehicle_id);
        $this->db->update("eudr_transportation_vehicle", $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new VehicleNotFoundException("Vehicle not found with ID: $vehicle_id");
        }
        return $this->findVehicleOfId($vehicle_id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateVehicleWithPermission(int $vehicle_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): Vehicle
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'v');
        $this->db->where('v.vehicle_id', $vehicle_id);
        $this->db->update('eudr_transportation_vehicle v', $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new VehicleNotFoundException("Vehicle not found with ID: $vehicle_id");
        }

        return $this->findVehicleOfIdWithPermission($vehicle_id, $auth_user_id, $scope, $company_id, $company_id_param);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteVehicle(int $vehicle_id, int $deleted_by): void
    {
        $this->db->where("vehicle_id", $vehicle_id);
        $this->db->update('eudr_transportation_vehicle', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteVehicleWithPermission(int $vehicle_id, int $deleted_by, string $scope, ?int $company_id = null, ?int $company_id_param = null): void
    {
        $authUserId = $deleted_by;
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'v');
        $this->db->where('v.vehicle_id', $vehicle_id);
        $this->db->update('eudr_transportation_vehicle v', [
            'deleted_by' => $deleted_by,
            'deleted_at' => date('Y-m-d H:i:s', time()),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function findVehicleBrands(): array
    {
        $brands = $this->db->get("eudr_transportation_vehicle_brand",NULL, "vehicle_brand_name");
        return $brands;
    }

}
