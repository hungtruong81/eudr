<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Production;

use App\Domain\Production\Production;
use App\Domain\Production\ProductionRepository;

class InDatabaseProductionRepository implements ProductionRepository
{
    /**
     * @var MysqliDb
     */
    private $db;

    /**
     * InDatabaseProductionRepository constructor.
     *
     * @param MysqliDb $db
     */
    public function __construct(\MysqliDb $db)
    {
        $this->db = $db;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll($params = []): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $search = $params['search'] ?? '';
        $plot_id = $params['plot_id'] ?? 0;
        $permission_status = $params['permission_status'] ?? '';
        $user_id = $params['user_id'] ?? 0;

        // Count total records
        $total_records = 0;
        $this->db->where("plant.deleted_by", 0);
       if(!empty($permission_status) && ($permission_status === 'own')) {
            $this->db->where("plant.created_by", $user_id);
        }
        if (!empty($search)) {
            $this->db->where("(plant.plantation_name LIKE '%".$search."%')");
        }
        if(!empty($plot_id)) {
            $this->db->where("plant.plot_id", $plot_id);
        }
        $total_records = $this->db->getValue("eudr_plants plant", "count(*)");


        // Set pagination
        $this->db->pageLimit = $page_limit;
        if($params['permission_status'] === 'own') {
            $this->db->where("plant.created_by", $user_id);
        }
        if (!empty($search)) {
            $this->db->where("(plant.plantation_name LIKE '%".$search."%')");
        }
        if(!empty($plot_id)) {
            $this->db->where("plant.plot_id", $plot_id);
        }
        $cols = "plant.*,land.plot_name,land.plot_code";

        $this->db->where("plant.deleted_by", 0);
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
                $items[] = new Production($item['plot_id'], $item);
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

}
