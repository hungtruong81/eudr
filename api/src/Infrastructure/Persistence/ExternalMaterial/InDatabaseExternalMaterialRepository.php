<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\ExternalMaterial;

use App\Domain\ExternalMaterial\ExternalMaterial;
use App\Domain\ExternalMaterial\ExternalMaterialRepository;
use App\Application\Utility\Utils;
use App\Application\Utility\CurrentUserContext;

class InDatabaseExternalMaterialRepository implements ExternalMaterialRepository
{
    /**
     * @var \MysqliDb
     */
    private $db;

    /**
     * @var CurrentUserContext
     */
    private $currentUser;

    public function __construct(\MysqliDb $db, CurrentUserContext $currentUserContext)
    {
        $this->db = $db;
        $this->currentUser = $currentUserContext;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(array $params = []): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 20;
        $search = $params['search'] ?? '';
        $status = $params['status'] ?? 'all';
        $start_date = $params['start_date'] ?? null;
        $end_date = $params['end_date'] ?? null;
        $factory_id = $params['factory_id'] ?? 0;
        $scope = $params['scope'] ?? 'self';

        // Build common where conditions
        $this->applyFilters($status, $start_date, $end_date, $factory_id, $search, $scope);
        $total_records = $this->db->getValue("eudr_external_materials em", "count(*)");

        // Pagination
        $this->db->pageLimit = $page_limit;

        // Re-apply filters for data query
        $this->applyFilters($status, $start_date, $end_date, $factory_id, $search, $scope);

        $this->db->join("eudr_factories f", "f.factory_id=em.factory_id", "LEFT");
        $this->db->orderBy("em.created_at", "DESC");

        $cols = "em.*, f.factory_name";
        $records = $this->db->arraybuilder()->paginate("eudr_external_materials em", $page, $cols);

        $items = [];
        if ($this->db->count > 0 && !empty($records)) {
            foreach ($records as $row) {
                $row['factory_name'] = $row['factory_name'] ?? '';
                $row['lands'] = $this->findLandsByExternalMaterialId((int)$row['external_material_id']);
                $row['transport'] = $this->findTransportByExternalMaterialId((int)$row['external_material_id']);
                $items[] = new ExternalMaterial((int)$row['external_material_id'], $row);
            }
        }

        return [
            "items" => $items,
            "total_records" => $total_records,
            "total_pages" => ceil($total_records / $page_limit),
            "page" => $page,
            "page_limit" => $page_limit,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = "exte-" . date("ymd") . '-' . Utils::generateRandomString(8);
            $existing = $this->findExternalMaterialOfCode($code);
            if (!$existing) {
                break;
            }
        }
        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function findExternalMaterialOfId(int $external_material_id): ?ExternalMaterial
    {
        $this->db->where("em.external_material_id", $external_material_id);
        $this->db->where("em.deleted_by", 0);
        $this->db->join("eudr_factories f", "f.factory_id=em.factory_id", "LEFT");

        $row = $this->db->getOne("eudr_external_materials em", "em.*, f.factory_name");
        if (empty($row)) {
            return null;
        }

        $row['factory_name'] = $row['factory_name'] ?? '';
        $row['lands'] = $this->findLandsByExternalMaterialId($external_material_id);
        $row['transport'] = $this->findTransportByExternalMaterialId($external_material_id);

        return new ExternalMaterial($external_material_id, $row);
    }

    /**
     * {@inheritdoc}
     */
    public function findExternalMaterialOfCode(string $code): ?ExternalMaterial
    {
        $this->db->where("em.external_material_code", $code);
        $this->db->where("em.deleted_by", 0);
        $this->db->join("eudr_factories f", "f.factory_id=em.factory_id", "LEFT");

        $row = $this->db->getOne("eudr_external_materials em", "em.*, f.factory_name");
        if (empty($row)) {
            return null;
        }

        $id = (int)$row['external_material_id'];
        $row['factory_name'] = $row['factory_name'] ?? '';
        $row['lands'] = $this->findLandsByExternalMaterialId($id);
        $row['transport'] = $this->findTransportByExternalMaterialId($id);

        return new ExternalMaterial($id, $row);
    }

    /**
     * {@inheritdoc}
     */
    public function createExternalMaterial(array $data): ?ExternalMaterial
    {
        $lands_data = $data['lands'] ?? [];
        $transport_data = $data['transport'] ?? null;
        unset($data['lands'], $data['transport']);

        $external_material_id = (int)$this->db->insert("eudr_external_materials", $data);
        if ($external_material_id <= 0) {
            throw new \RuntimeException("Failed to create external material: " . $this->db->getLastError());
        }

        // Insert land links
        if (!empty($lands_data) && is_array($lands_data)) {
            foreach ($lands_data as $land) {
                $this->db->insert("eudr_external_material_lands", [
                    "external_material_id" => $external_material_id,
                    "plot_id" => (int)$land['plot_id'],
                    "harvest_weight" => floatval($land['harvest_weight'] ?? 0),
                    "notes" => $land['notes'] ?? '',
                    "created_at" => date("Y-m-d H:i:s"),
                ]);
            }
        }

        // Insert transport
        if (!empty($transport_data)) {
            $this->db->insert("eudr_external_material_transports", [
                "external_material_id" => $external_material_id,
                "vehicle_license_plate" => $transport_data['vehicle_license_plate'] ?? '',
                "driver_name" => $transport_data['driver_name'] ?? '',
                "driver_phone" => $transport_data['driver_phone'] ?? '',
                "transport_date" => $transport_data['transport_date'] ?? null,
                "pickup_time" => $transport_data['pickup_time'] ?? null,
                "pickup_location" => $transport_data['pickup_location'] ?? '',
                "delivery_time" => $transport_data['delivery_time'] ?? null,
                "notes" => $transport_data['notes'] ?? '',
                "created_at" => date("Y-m-d H:i:s"),
            ]);
        }

        return $this->findExternalMaterialOfId($external_material_id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateExternalMaterial(int $external_material_id, array $data): ?ExternalMaterial
    {
        $lands_data = $data['lands'] ?? null;
        $transport_data = $data['transport'] ?? null;
        unset($data['lands'], $data['transport']);

        $this->db->where("external_material_id", $external_material_id);
        $this->db->where("deleted_by", 0);
        $this->db->update("eudr_external_materials", $data);

        // Update land links
        if ($lands_data !== null && is_array($lands_data)) {
            // Delete existing links
            $this->db->where("external_material_id", $external_material_id);
            $this->db->delete("eudr_external_material_lands");

            // Insert new links
            foreach ($lands_data as $land) {
                $this->db->insert("eudr_external_material_lands", [
                    "external_material_id" => $external_material_id,
                    "plot_id" => (int)$land['plot_id'],
                    "harvest_weight" => floatval($land['harvest_weight'] ?? 0),
                    "notes" => $land['notes'] ?? '',
                    "created_at" => date("Y-m-d H:i:s"),
                ]);
            }
        }

        // Update transport
        if ($transport_data !== null) {
            // Delete existing transport
            $this->db->where("external_material_id", $external_material_id);
            $this->db->delete("eudr_external_material_transports");

            // Insert new transport
            if (!empty($transport_data)) {
                $this->db->insert("eudr_external_material_transports", [
                    "external_material_id" => $external_material_id,
                    "vehicle_license_plate" => $transport_data['vehicle_license_plate'] ?? '',
                    "driver_name" => $transport_data['driver_name'] ?? '',
                    "driver_phone" => $transport_data['driver_phone'] ?? '',
                    "transport_date" => $transport_data['transport_date'] ?? null,
                    "pickup_time" => $transport_data['pickup_time'] ?? null,
                    "pickup_location" => $transport_data['pickup_location'] ?? '',
                    "delivery_time" => $transport_data['delivery_time'] ?? null,
                    "notes" => $transport_data['notes'] ?? '',
                    "created_at" => date("Y-m-d H:i:s"),
                ]);
            }
        }

        return $this->findExternalMaterialOfId($external_material_id);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteExternalMaterial(int $external_material_id, int $deleted_by): void
    {
        $this->db->where("external_material_id", $external_material_id);
        $this->db->where("deleted_by", 0);
        $this->db->where("status", "draft");
        $this->db->update("eudr_external_materials", [
            "deleted_by" => $deleted_by,
            "deleted_at" => date("Y-m-d H:i:s"),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function confirmExternalMaterial(int $external_material_id, int $confirmed_by): ?ExternalMaterial
    {
        $this->db->where("external_material_id", $external_material_id);
        $this->db->where("deleted_by", 0);
        $this->db->where("status", "draft");
        $this->db->update("eudr_external_materials", [
            "status" => "confirmed",
            "updated_by" => $confirmed_by,
            "updated_at" => date("Y-m-d H:i:s"),
        ]);

        return $this->findExternalMaterialOfId($external_material_id);
    }

    /**
     * {@inheritdoc}
     */
    public function cancelExternalMaterial(int $external_material_id, int $cancelled_by): ?ExternalMaterial
    {
        $this->db->where("external_material_id", $external_material_id);
        $this->db->where("deleted_by", 0);
        $this->db->where("status", ["draft", "confirmed"], "IN");
        $this->db->update("eudr_external_materials", [
            "status" => "cancelled",
            "updated_by" => $cancelled_by,
            "updated_at" => date("Y-m-d H:i:s"),
        ]);

        return $this->findExternalMaterialOfId($external_material_id);
    }

    /**
     * {@inheritdoc}
     */
    public function findLandsByExternalMaterialId(int $external_material_id): array
    {
        $this->db->where("eml.external_material_id", $external_material_id);
        $this->db->join("eudr_lands l", "l.plot_id=eml.plot_id", "LEFT");
        $this->db->join("eudr_general_provinces p", "p.province_id=l.province_id", "LEFT");

        $cols = "eml.*, l.plot_code, l.plot_name, l.farmer_name, l.coordinates, l.land_area, l.address, l.register_type, p.province_id, p.province_name";
        $records = $this->db->arraybuilder()->get("eudr_external_material_lands eml", null, $cols);

        $items = [];
        if (!empty($records)) {
            foreach ($records as $row) {
                $row['coordinates'] = !empty($row['coordinates']) ? json_decode($row['coordinates'], true) : [];
                $items[] = $row;
            }
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function findTransportByExternalMaterialId(int $external_material_id): ?array
    {
        $this->db->where("external_material_id", $external_material_id);
        $row = $this->db->getOne("eudr_external_material_transports");

        if (empty($row)) {
            return null;
        }

        return $row;
    }

    /**
     * Apply common filter conditions
     */
    private function applyFilters(
        string $status,
        ?string $start_date,
        ?string $end_date,
        int $factory_id,
        string $search,
        string $scope
    ): void {
        $this->db->where("em.deleted_by", 0);

        if ($status !== 'all') {
            $this->db->where("em.status", $status);
        }
        if (!empty($start_date)) {
            $this->db->where("em.purchase_date", $start_date, ">=");
        }
        if (!empty($end_date)) {
            $this->db->where("em.purchase_date", $end_date, "<=");
        }
        if (!empty($factory_id)) {
            $this->db->where("em.factory_id", $factory_id);
        }
        if (!empty($search)) {
            $this->db->where("(em.external_material_code LIKE '%" . $this->db->escape($search) . "%' OR em.supplier_name LIKE '%" . $this->db->escape($search) . "%' OR em.supplier_phone LIKE '%" . $this->db->escape($search) . "%')");
        }

        // Scope filtering
        if ($scope === 'self') {
            $this->db->where("em.created_by", $this->currentUser->getUserId());
        } elseif ($scope === 'own') {
            $this->db->where("em.company_id", $this->currentUser->getCompanyId());
        }
        // scope 'all' => no additional filter
    }
}
