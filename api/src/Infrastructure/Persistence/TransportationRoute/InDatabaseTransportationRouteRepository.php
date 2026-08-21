<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\TransportationRoute;

use App\Application\Utility\CurrentUserContext;
use App\Domain\TransportationRoute\TransportationRoute;
use App\Domain\TransportationRoute\TransportationRouteNotFoundException;
use App\Domain\TransportationRoute\TransportationRouteRepository;
use App\Domain\RawMaterialTank\RawMaterialTankRepository;
use App\Application\Utility\Utils;


class InDatabaseTransportationRouteRepository implements TransportationRouteRepository
{
    /**
     * @var MysqliDb
     */
    private $db;
    /**
     * @var RawMaterialTankRepository
     */
    private $rawMaterialTankRepository;
    /**
     * @var CurrentUserContext
     */
    private $currentUser;

    /**
     * InDatabaseTransportationRouteRepository constructor.
     *
     * @param MysqliDb $db
     */
    public function __construct(\MysqliDb $db, RawMaterialTankRepository $rawMaterialTankRepository, CurrentUserContext $currentUserContext)
    {
        $this->db = $db;
        $this->rawMaterialTankRepository = $rawMaterialTankRepository;
        $this->currentUser = $currentUserContext;
    }

    /**
     * Apply scope-based filtering (self/own/all).
     */
    private function scopeWhere(string $scope, int $authUserId, int $companyId, ?int $companyIdParam = null, string $alias = 't'): void
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
        $vehicle_id = $params['vehicle_id'] ?? 0;
        $destination_factory_id = $params['destination_factory_id'] ?? 0;
        $status = $params['status'] ?? 'all';
        $start_date = $params['start_date'] ?? null;
        $end_date = $params['end_date'] ?? null;
        $transport_date_from = $params['transport_date_from'] ?? null;
        $transport_date_to = $params['transport_date_to'] ?? null;
        $companyIdParam = $company_id_param ?? 0;

       // Count total records
        $total_records = 0;
        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 't');
        if (!empty($vehicle_id)) {
            $this->db->where("t.vehicle_id", $vehicle_id);
        }
        if (!empty($destination_factory_id)) {
            $this->db->where("t.destination_factory_id", $destination_factory_id);
        }
        if ($status !== 'all') {
            $this->db->where("t.status", $status);
        }
        if (!empty($start_date)) {
            $this->db->where("DATE(t.created_at)", $start_date, ">=");
        }
        if (!empty($end_date)) {
            $this->db->where("DATE(t.created_at)", $end_date, "<=");
        }
        if (!empty($transport_date_from)) {
            $this->db->where("t.transport_date", $transport_date_from, ">=");
        }
        if (!empty($transport_date_to)) {
            $this->db->where("t.transport_date", $transport_date_to, "<=");
        }
        $total_records = $this->db->getValue("eudr_transportation_routes t", "count(*)");


        // Set pagination
        $this->db->pageLimit = $page_limit;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 't');

        if (!empty($vehicle_id)) {
            $this->db->where("t.vehicle_id", $vehicle_id);
        }
        if (!empty($destination_factory_id)) {
            $this->db->where("t.destination_factory_id", $destination_factory_id);
        }
        if ($status !== 'all') {
            $this->db->where("t.status", $status);
        }
        if (!empty($start_date)) {
            $this->db->where("DATE(t.created_at)", $start_date, ">=");
        }
        if (!empty($end_date)) {
            $this->db->where("DATE(t.created_at)", $end_date, "<=");
        }
        if (!empty($transport_date_from)) {
            $this->db->where("t.transport_date", $transport_date_from, ">=");
        }
        if (!empty($transport_date_to)) {
            $this->db->where("t.transport_date", $transport_date_to, "<=");
        }

        $cols = "t.*, f.factory_name as destination_factory_name, v.vehicle_name as vehicle_name, v.license_plate as vehicle_license_plate";

        if (!empty($params['order_by'])) {
            $this->db->orderBy('t.'.$params['order_by'], $params['order_type']??'ASC');
        } else {
            $this->db->orderBy("t.transportation_route_id", "DESC");
        }
        $this->db->join("eudr_factories f", "f.factory_id = t.destination_factory_id", "LEFT");
        $this->db->join("eudr_transportation_vehicle v", "v.vehicle_id = t.vehicle_id", "LEFT");
        $records = $this->db->arrayBuilder()->paginate("eudr_transportation_routes t", $page, $cols);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = new TransportationRoute($item['transportation_route_id'], $item);
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
    public function findTransportationRouteOfId(int $transportation_route_id): ?TransportationRoute
    {
        $this->db->where("t.transportation_route_id", $transportation_route_id);
        $this->db->where("t.deleted_by", 0);
        $this->db->join("eudr_factories f", "f.factory_id = t.destination_factory_id", "LEFT");
        $this->db->join("eudr_transportation_vehicle v", "v.vehicle_id = t.vehicle_id", "LEFT");
        $route = $this->db->getOne("eudr_transportation_routes t", "t.*, f.factory_name as destination_factory_name, v.vehicle_name as vehicle_name, v.license_plate as vehicle_license_plate");
        if (empty($route)) {
            return null;
        }
        // Get linked transaction tickets
        $this->db->where("transportation_route_id", $transportation_route_id);
        $this->db->join("eudr_transaction_tickets tt", "tt.transaction_ticket_id = rtt.transaction_ticket_id", "LEFT");
        $transaction_tickets = $this->db->get("eudr_transportation_route_transaction_tickets rtt", null, 
        "tt.*"
        );
        
        $route['source_transaction_tickets'] = $transaction_tickets;

        return new TransportationRoute($route['transportation_route_id'], $route);
    }

    /**
     * {@inheritdoc}
     */
    public function findTransportationRouteOfIdWithPermission(int $transportation_route_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?TransportationRoute
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 't');
        $this->db->where('t.transportation_route_id', $transportation_route_id);

        $this->db->join("eudr_factories f", "f.factory_id = t.destination_factory_id", "LEFT");
        $this->db->join("eudr_transportation_vehicle v", "v.vehicle_id = t.vehicle_id", "LEFT");
        $route = $this->db->getOne("eudr_transportation_routes t", "t.*, f.factory_name as destination_factory_name, v.vehicle_name as vehicle_name, v.license_plate as vehicle_license_plate");
        if (empty($route)) {
            return null;
        }

        $this->db->where("transportation_route_id", $transportation_route_id);
        $this->db->join("eudr_transaction_tickets tt", "tt.transaction_ticket_id = rtt.transaction_ticket_id", "LEFT");
        $transaction_tickets = $this->db->get("eudr_transportation_route_transaction_tickets rtt", null,
        "tt.*"
        );

        $route['source_transaction_tickets'] = $transaction_tickets;

        return new TransportationRoute($route['transportation_route_id'], $route);
    }


    /**
     * {@inheritdoc}
     */
    public function findTransportationRouteOfCode(string $code): ?TransportationRoute
    {
        $this->db->where("t.transportation_route_code", $code);
        $this->db->where("t.deleted_by", 0);
        $this->db->join("eudr_factories f", "f.factory_id = t.destination_factory_id", "LEFT");
        $this->db->join("eudr_transportation_vehicle v", "v.vehicle_id = t.vehicle_id", "LEFT");
        $route = $this->db->getOne("eudr_transportation_routes t", "t.*, f.factory_name as destination_factory_name, v.vehicle_name as vehicle_name, v.license_plate as vehicle_license_plate");
        if (empty($route)) {
            return null;
        }

        // Get linked transaction tickets
        $this->db->where("transportation_route_id", $route['transportation_route_id']);
        $this->db->join("eudr_transaction_tickets tt", "tt.transaction_ticket_id = rtt.transaction_ticket_id", "LEFT");
        $transaction_tickets = $this->db->get("eudr_transportation_route_transaction_tickets rtt", null, 
        "tt.*"
        );
        $route['source_transaction_tickets'] = $transaction_tickets;

        return new TransportationRoute($route['transportation_route_id'], $route);
    }

    /**
     * {@inheritdoc}
     */
    public function findTransportationRouteOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?TransportationRoute
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 't');
        $this->db->where('t.transportation_route_code', $code);

        $this->db->join("eudr_factories f", "f.factory_id = t.destination_factory_id", "LEFT");
        $this->db->join("eudr_transportation_vehicle v", "v.vehicle_id = t.vehicle_id", "LEFT");
        $route = $this->db->getOne("eudr_transportation_routes t", "t.*, f.factory_name as destination_factory_name, v.vehicle_name as vehicle_name, v.license_plate as vehicle_license_plate");
        if (empty($route)) {
            return null;
        }

        $this->db->where("transportation_route_id", $route['transportation_route_id']);
        $this->db->join("eudr_transaction_tickets tt", "tt.transaction_ticket_id = rtt.transaction_ticket_id", "LEFT");
        $transaction_tickets = $this->db->get("eudr_transportation_route_transaction_tickets rtt", null,
        "tt.*"
        );
        $route['source_transaction_tickets'] = $transaction_tickets;

        return new TransportationRoute($route['transportation_route_id'], $route);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = "trrt-".date("ymd").'-'.Utils::generateRandomString(8);
            $route = $this->findTransportationRouteOfCode($code);
            if (!$route) {
                break;
            }
        }
        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function createTransportationRoute(array $data): ?TransportationRoute
    {
        $source_transaction_ticket_ids = $data['source_transaction_ticket_ids'] ?? [];
        
        unset($data['source_transaction_ticket_ids']);

        $this->db->insert("eudr_transportation_routes", $data);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }

        $route_id = $this->db->getInsertId();

        if (!empty($source_transaction_ticket_ids)) {
            // Delete old links just in case
            $this->db->where("transportation_route_id", $route_id);
            $this->db->delete("eudr_transportation_route_transaction_tickets");
            // Link transaction tickets
            foreach ($source_transaction_ticket_ids as $ticket_id) {
                $data_link = [
                    "transportation_route_id" => $route_id,
                    "transaction_ticket_id" => $ticket_id,
                ];
                $this->db->insert("eudr_transportation_route_transaction_tickets", $data_link);
            }
        }

        return $this->findTransportationRouteOfId($route_id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateTransportationRoute(int $route_id, array $data_update): TransportationRoute
    {

        $source_transaction_ticket_ids = $data_update['source_transaction_ticket_ids'] ?? [];
        
        unset($data_update['source_transaction_ticket_ids']);

        $this->db->where("transportation_route_id", $route_id);
        $this->db->update("eudr_transportation_routes", $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new TransportationRouteNotFoundException("Transportation Route not found with ID: $route_id");
        }

        if (!empty($source_transaction_ticket_ids)) {
            // Delete old links just in case
            $this->db->where("transportation_route_id", $route_id);
            $this->db->delete("eudr_transportation_route_transaction_tickets");
            // Link transaction tickets
            foreach ($source_transaction_ticket_ids as $ticket_id) {
                $data_link = [
                    "transportation_route_id" => $route_id,
                    "transaction_ticket_id" => $ticket_id,
                ];
                $this->db->insert("eudr_transportation_route_transaction_tickets", $data_link);
            }
        }

        return $this->findTransportationRouteOfId($route_id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateTransportationRouteWithPermission(int $transportation_route_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): TransportationRoute
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $source_transaction_ticket_ids = $data_update['source_transaction_ticket_ids'] ?? [];
        unset($data_update['source_transaction_ticket_ids']);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 't');
        $this->db->where('t.transportation_route_id', $transportation_route_id);
        $this->db->update("eudr_transportation_routes t", $data_update);

        if ($this->db->getLastErrno() !== 0 || $this->db->count === 0) {
            throw new TransportationRouteNotFoundException("Transportation Route not found with ID: $transportation_route_id");
        }

        if (!empty($source_transaction_ticket_ids)) {
            $this->db->where("transportation_route_id", $transportation_route_id);
            $this->db->delete("eudr_transportation_route_transaction_tickets");
            foreach ($source_transaction_ticket_ids as $ticket_id) {
                $data_link = [
                    "transportation_route_id" => $transportation_route_id,
                    "transaction_ticket_id" => $ticket_id,
                ];
                $this->db->insert("eudr_transportation_route_transaction_tickets", $data_link);
            }
        }

        return $this->findTransportationRouteOfId($transportation_route_id);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteTransportationRoute(int $route_id, int $deleted_by): void
    {
        $this->db->where("transportation_route_id", $route_id);
        $this->db->update('eudr_transportation_routes', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteTransportationRouteWithPermission(int $transportation_route_id, int $deleted_by, string $scope, ?int $company_id = null, ?int $company_id_param = null): void
    {
        $authUserId = $deleted_by;
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 't');
        $this->db->where('t.transportation_route_id', $transportation_route_id);
        $this->db->update('eudr_transportation_routes t', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

    /**
     * {@inheritdoc}
     */
    public function unloadTransportationRoute(int $transportation_route_id, array $data_update): ?TransportationRoute
    {
        $user_id = $data_update['user_id'] ?? 0;
        $status = $data_update['status'] ?? 'unloaded';
        $unloading_items = $data_update['unloading_items'] ?? [];

        $this->db->startTransaction();

        $this->db->where("transportation_route_id", $transportation_route_id);
        $this->db->update("eudr_transportation_routes", [
            "status" => $status,
            "updated_by" => $user_id,
            "updated_at" => date("Y-m-d H:i:s"),
        ]);

        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        foreach ($unloading_items as $item) {
            $raw_material_tank_id = $item['raw_material_tank_id'];
            $tank = $this->rawMaterialTankRepository->findRawMaterialTankOfId($raw_material_tank_id);
            $old_volume = $tank->getCurrentVolume();
            $new_weight = $item['actual_weight'];
            // Add history records
            $data_history = [
                "raw_material_tank_id" => $raw_material_tank_id,
                "entity_type" => 'transportation_route',
                "entity_id" => $transportation_route_id,
                "rubber_type" => $item['rubber_type'] ?? '',
                "action_type" => 'input',
                "weight" => $new_weight,
                "tsc" => $item['tsc'] ?? 0.0,
                "volume_before" => $old_volume,
                "volume_after" => $old_volume + $new_weight,
                'notes' => 'Nhập nguyên liệu thô từ lộ trình vận chuyển',
                "created_by" => $user_id,
                "created_at" => date("Y-m-d H:i:s"),
            ];
            $this->db->insert("eudr_tanks_raw_material_history", $data_history);
            if ($this->db->getLastErrno() !== 0) {
                $this->db->rollback();
                return null;
            }
            // Update raw material tanks
            $data_tank_update = [
                "current_volume" => $old_volume + $new_weight,
                "updated_by" => $user_id,
                "updated_at" => date("Y-m-d H:i:s"),
            ];
            $this->rawMaterialTankRepository->updateRawMaterialTank($raw_material_tank_id, $data_tank_update);
            if ($this->db->getLastErrno() !== 0) {
                $this->db->rollback();
                return null;
            }
        }
        $this->db->commit();
        return $this->findTransportationRouteOfId($transportation_route_id);
    }

    /**
     * {@inheritdoc}
     */
    public function unloadTransportationRouteWithPermission(int $transportation_route_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?TransportationRoute
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $existingRoute = $this->findTransportationRouteOfIdWithPermission($transportation_route_id, $authUserId, $scope, $companyId, $company_id_param);
        if (empty($existingRoute)) {
            return null;
        }

        $user_id = $data_update['user_id'] ?? $authUserId;
        $status = $data_update['status'] ?? 'unloaded';
        $unloading_items = $data_update['unloading_items'] ?? [];

        $this->db->startTransaction();

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, '');
        $this->db->where("transportation_route_id", $transportation_route_id);
        $this->db->update("eudr_transportation_routes", [
            "status" => $status,
            "updated_by" => $user_id,
            "updated_at" => date("Y-m-d H:i:s"),
        ]);

        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        foreach ($unloading_items as $item) {
            $raw_material_tank_id = $item['raw_material_tank_id'];
            $tank = $this->rawMaterialTankRepository->findRawMaterialTankOfId($raw_material_tank_id);
            $old_volume = $tank->getCurrentVolume();
            $new_weight = $item['actual_weight'];
            $data_history = [
                "raw_material_tank_id" => $raw_material_tank_id,
                "entity_type" => 'transportation_route',
                "entity_id" => $transportation_route_id,
                "rubber_type" => $item['rubber_type'] ?? '',
                "action_type" => 'input',
                "weight" => $new_weight,
                "tsc" => $item['tsc'] ?? 0.0,
                "volume_before" => $old_volume,
                "volume_after" => $old_volume + $new_weight,
                'notes' => 'Nhập nguyên liệu thô từ lộ trình vận chuyển',
                "created_by" => $user_id,
                "created_at" => date("Y-m-d H:i:s"),
            ];
            $this->db->insert("eudr_tanks_raw_material_history", $data_history);
            if ($this->db->getLastErrno() !== 0) {
                $this->db->rollback();
                return null;
            }
            $data_tank_update = [
                "current_volume" => $old_volume + $new_weight,
                "updated_by" => $user_id,
                "updated_at" => date("Y-m-d H:i:s"),
            ];
            $this->rawMaterialTankRepository->updateRawMaterialTank($raw_material_tank_id, $data_tank_update);
            if ($this->db->getLastErrno() !== 0) {
                $this->db->rollback();
                return null;
            }
        }
        $this->db->commit();
        return $this->findTransportationRouteOfId($transportation_route_id);
    }

    /**
     * {@inheritdoc}
     */
    public function countUnroutedPurchaseTickets(array $purchase_ticket_ids): int
    {
        $this->db->where("transaction_ticket_id", $purchase_ticket_ids, "IN");
        $count = $this->db->getValue("eudr_transportation_route_transaction_tickets", "count(*)");
        return (int)$count;
    }

}
