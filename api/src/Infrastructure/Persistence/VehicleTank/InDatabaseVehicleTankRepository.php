<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\VehicleTank;

use App\Domain\VehicleTank\VehicleTank;
use App\Domain\VehicleTank\VehicleTankException;
use App\Domain\VehicleTank\VehicleTankRepository;
use App\Application\Utility\Utils;

class InDatabaseVehicleTankRepository implements VehicleTankRepository
{
    /**
     * @var \MysqliDb
     */
    private \MysqliDb $db;

    public function __construct(\MysqliDb $db)
    {
        $this->db = $db;
    }

    /**
     * @param int $auth_user_id
     * @param string $scope
     * @param int $company_id
     * @param int|null $company_id_param
     * @param string $tank_alias
     * @param string $vehicle_alias
     */
    private function applyScope(
        int $auth_user_id,
        string $scope,
        int $company_id,
        ?int $company_id_param = null,
        string $tank_alias = 't',
        string $vehicle_alias = 'v'
    ): void {
        $this->db->where($tank_alias . '.deleted_by', 0);
        $this->db->where($vehicle_alias . '.deleted_by', 0);

        if ($scope === 'self') {
            $this->db->where($tank_alias . '.created_by', $auth_user_id);
            $this->db->where($vehicle_alias . '.company_id', $company_id);
        } elseif ($scope === 'own') {
            $this->db->where($vehicle_alias . '.company_id', $company_id);
        } elseif ($scope === 'all' && !empty($company_id_param)) {
            $this->db->where($vehicle_alias . '.company_id', $company_id_param);
        }
    }

    private function joinVehicle(): void
    {
        $this->db->join('eudr_transportation_vehicle v', 'v.vehicle_id = t.vehicle_id', 'INNER');
    }

    private function columns(): string
    {
        return 't.*, v.vehicle_code, v.license_plate';
    }

    /**
     * @param array $params
     * @param int $auth_user_id
     * @param string $scope
     * @param int $company_id
     * @param int|null $company_id_param
     * @return array
     */
    public function findAll(
        array $params,
        int $auth_user_id,
        string $scope,
        int $company_id,
        ?int $company_id_param = null
    ): array {
        $page = (int)($params['page'] ?? 1);
        $page_limit = (int)($params['page_limit'] ?? 10);
        $search = (string)($params['search'] ?? '');
        $status = (string)($params['status'] ?? 'all');
        $vehicle_id = (int)($params['vehicle_id'] ?? 0);

        $this->joinVehicle();
        $this->applyScope($auth_user_id, $scope, $company_id, $company_id_param);

        if ($search !== '') {
            $this->db->where(
                '(t.vehicle_tank_code LIKE ? OR t.vehicle_tank_name LIKE ? OR v.license_plate LIKE ?)',
                ["%$search%", "%$search%", "%$search%"]
            );
        }

        if ($status !== 'all') {
            $this->db->where('t.status', $status);
        }

        if ($vehicle_id > 0) {
            $this->db->where('t.vehicle_id', $vehicle_id);
        }

        $total_records = (int)$this->db->getValue('eudr_vehicle_tanks t', 'COUNT(*)');

        $this->db->pageLimit = $page_limit;
        $this->joinVehicle();
        $this->applyScope($auth_user_id, $scope, $company_id, $company_id_param);

        if ($search !== '') {
            $this->db->where(
                '(t.vehicle_tank_code LIKE ? OR t.vehicle_tank_name LIKE ? OR v.license_plate LIKE ?)',
                ["%$search%", "%$search%", "%$search%"]
            );
        }

        if ($status !== 'all') {
            $this->db->where('t.status', $status);
        }

        if ($vehicle_id > 0) {
            $this->db->where('t.vehicle_id', $vehicle_id);
        }

        $this->db->orderBy('t.vehicle_tank_id', 'DESC');
        $records = $this->db
            ->arrayBuilder()
            ->paginate('eudr_vehicle_tanks t', $page, $this->columns());

        return [
            'current_page' => $page,
            'total_pages' => $this->db->totalPages,
            'total_records' => $total_records,
            'page_limit' => $this->db->pageLimit,
            'records' => array_map(
                static fn(array $row): VehicleTank => new VehicleTank(
                    (int)$row['vehicle_tank_id'],
                    $row
                ),
                $records ?? []
            ),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = "vetk-" . date("ymd") . '-' . Utils::generateRandomString(8);
            $tank = $this->findByCode($code);
            if (!$tank) {
                break;
            }
        }
        return $code;
    }

    /**
     * @param string $code
     * @return VehicleTank|null
     */
    public function findByCode(string $code): ?VehicleTank
    {
        $this->joinVehicle();
        $this->db->where('t.vehicle_tank_code', $code);
        $this->db->where('t.deleted_by', 0);
        $this->db->where('v.deleted_by', 0);
        $row = $this->db->getOne('eudr_vehicle_tanks t', $this->columns());

        return empty($row) ? null : new VehicleTank((int)$row['vehicle_tank_id'], $row);
    }

    /**
     * @param string $code
     * @param int $auth_user_id
     * @param string $scope
     * @param int $company_id
     * @param int|null $company_id_param
     * @return VehicleTank|null
     */
    public function findByCodeWithPermission(
        string $code,
        int $auth_user_id,
        string $scope,
        int $company_id,
        ?int $company_id_param = null
    ): ?VehicleTank {
        $this->joinVehicle();
        $this->applyScope($auth_user_id, $scope, $company_id, $company_id_param);
        $this->db->where('t.vehicle_tank_code', $code);
        $row = $this->db->getOne('eudr_vehicle_tanks t', $this->columns());

        return empty($row) ? null : new VehicleTank((int)$row['vehicle_tank_id'], $row);
    }

    /**
     * @param array $data
     * @return VehicleTank|null
     */
    public function create(array $data): ?VehicleTank
    {
        $capacity = (float)($data['capacity_kg'] ?? 0);
        $currentWeight = (float)($data['current_weight_kg'] ?? 0);
        if ($capacity <= 0 || $currentWeight < 0 || $currentWeight > $capacity) {
            throw new VehicleTankException('Khối lượng bồn không hợp lệ hoặc vượt quá sức chứa');
        }

        $id = $this->db->insert('eudr_vehicle_tanks', $data);
        if ($id === false || $this->db->getLastErrno() !== 0) {
            return null;
        }

        $this->db->where('t.vehicle_tank_id', (int)$id);
        $this->joinVehicle();
        $row = $this->db->getOne('eudr_vehicle_tanks t', $this->columns());

        return empty($row) ? null : new VehicleTank((int)$id, $row);
    }

    /**
     * @param int $vehicle_tank_id
     * @param array $data
     * @param int $auth_user_id
     * @param string $scope
     * @param int $company_id
     * @param int|null $company_id_param
     * @return VehicleTank|null
     */
    public function updateWithPermission(
        int $vehicle_tank_id,
        array $data,
        int $auth_user_id,
        string $scope,
        int $company_id,
        ?int $company_id_param = null
    ): ?VehicleTank {
        $this->joinVehicle();
        $this->applyScope($auth_user_id, $scope, $company_id, $company_id_param);
        $this->db->where('t.vehicle_tank_id', $vehicle_tank_id);
        $current = $this->db->getOne('eudr_vehicle_tanks t', 't.current_weight_kg');
        if (empty($current)) {
            return null;
        }

        $activeTransport = $this->db->rawQuery(
            "SELECT t.purchase_transport_id
             FROM eudr_purchasing_transports t
             INNER JOIN eudr_purchasing_transport_sub_tanks l
                 ON l.purchase_transport_id = t.purchase_transport_id
             WHERE l.vehicle_tank_id = ?
               AND t.deleted_by = 0
               AND t.status IN ('planned', 'loading', 'in_transit', 'arrived')
             LIMIT 1",
            [$vehicle_tank_id]
        );
        if (!empty($activeTransport)) {
            throw new VehicleTankException('Không thể cập nhật bồn đang được sử dụng bởi chuyến vận chuyển');
        }

        if (
            isset($data['capacity_kg'])
            && (float)$data['capacity_kg'] < (float)$current['current_weight_kg']
        ) {
            throw new VehicleTankException('Capacity không được nhỏ hơn khối lượng hiện tại trong bồn');
        }

        $this->db->where('vehicle_tank_id', $vehicle_tank_id);
        $this->db->where('deleted_by', 0);
        if (!$this->db->update('eudr_vehicle_tanks', $data) || $this->db->getLastErrno() !== 0) {
            return null;
        }

        $this->db->where('t.vehicle_tank_id', $vehicle_tank_id);
        $this->joinVehicle();
        $row = $this->db->getOne('eudr_vehicle_tanks t', $this->columns());

        return empty($row) ? null : new VehicleTank($vehicle_tank_id, $row);
    }

    /**
     * @param int $vehicle_tank_id
     * @param int $deleted_by
     * @param string $scope
     * @param int $company_id
     * @param int|null $company_id_param
     * @return bool
     */
    public function deleteWithPermission(
        int $vehicle_tank_id,
        int $deleted_by,
        string $scope,
        int $company_id,
        ?int $company_id_param = null
    ): bool {
        $this->joinVehicle();
        $this->applyScope($deleted_by, $scope, $company_id, $company_id_param);
        $this->db->where('t.vehicle_tank_id', $vehicle_tank_id);
        $current = $this->db->getOne('eudr_vehicle_tanks t', 't.current_weight_kg');
        if (empty($current)) {
            return false;
        }

        if ((float)$current['current_weight_kg'] > 0) {
            throw new VehicleTankException('Không thể xóa bồn đang chứa hàng');
        }

        $this->db->where('vehicle_tank_id', $vehicle_tank_id);
        $this->db->where('deleted_by', 0);
        return $this->db->update('eudr_vehicle_tanks', [
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => $deleted_by,
        ]);
    }

    /**
     * @param int $vehicle_tank_id
     * @param float $weight_kg
     * @param int $updated_by
     * @return VehicleTank|null
     */
    public function setCurrentWeight(int $vehicle_tank_id, float $weight_kg, int $updated_by): ?VehicleTank
    {
        if ($weight_kg < 0) {
            throw new VehicleTankException('Khối lượng trong bồn không được âm');
        }

        $this->db->where('vehicle_tank_id', $vehicle_tank_id);
        $this->db->where('deleted_by', 0);
        $this->db->where('status', ['maintenance', 'inactive'], 'NOT IN');
        $this->db->where('capacity_kg', $weight_kg, '>=');
        $updated = $this->db->update('eudr_vehicle_tanks', [
            'current_weight_kg' => $weight_kg,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $updated_by,
        ]);
        if (!$updated || $this->db->count === 0) {
            throw new VehicleTankException('Bồn không khả dụng hoặc khối lượng vượt quá capacity');
        }

        $this->db->where('t.vehicle_tank_id', $vehicle_tank_id);
        $this->joinVehicle();
        $row = $this->db->getOne('eudr_vehicle_tanks t', $this->columns());

        return empty($row) ? null : new VehicleTank($vehicle_tank_id, $row);
    }
}
