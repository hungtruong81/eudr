<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Driver;

use App\Domain\Driver\Driver;
use App\Domain\Driver\DriverNotFoundException;
use App\Domain\Driver\DriverRepository;
use App\Application\Utility\Utils;

class InDatabaseDriverRepository implements DriverRepository
{
    /**
     * @var MysqliDb
     */
    private $db;

    /**
     * InDatabaseDriverRepository constructor.
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
        $user_id = $params['user_id'] ?? 0;

       // Count total records
        $total_records = 0;
        $this->db->where("v.deleted_by", 0);
        $this->db->where("v.created_by", $user_id);
        $total_records = $this->db->getValue("eudr_transportation_vehicle v", "count(*)");


        // Set pagination
        $this->db->pageLimit = $page_limit;

        $this->db->where("v.created_by", $user_id);
        
        if (!empty($search)) {
            $this->db->where("(v.vehicle_name LIKE '%".$search."%')");
        }

        $cols = "v.*";

        $this->db->where("v.deleted_by", 0);
        if (!empty($params['order_by'])) {
            $this->db->orderBy('v.'.$params['order_by'], $params['order_type']??'ASC');
        } else {
            $this->db->orderBy("v.vehicle_id", "DESC");
        }
        $records = $this->db->arraybuilder()->paginate("eudr_transportation_vehicle v", $page, $cols);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = new Driver($item['vehicle_id'], $item);
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
    public function findDriverOfId(int $driver_id): ?Driver
    {
        $this->db->where("v.driver_id", $driver_id);
        $this->db->where("v.deleted_by", 0);
        $driver = $this->db->getOne("eudr_transportation_driver v", "v.*");
        if (empty($driver)) {
            return null;
        }
        return new Driver($driver['driver_id'], $driver);
    }

    /**
     * {@inheritdoc}
     */
    public function findDriverOfCode(string $code): ?Driver
    {
        $this->db->where("v.driver_code", $code);
        $this->db->where("v.deleted_by", 0);
        $driver = $this->db->getOne("eudr_transportation_driver v", "v.*");
        if (empty($driver)) {
            return null;
        }
        return new Driver($driver['driver_id'], $driver);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = "driv-".date("ymd").'-'.Utils::generateRandomString(8);
            $driver = $this->findDriverOfCode($code);
            if (!$driver) {
                break;
            }
        }
        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function createDriver(array $data): ?Driver
    {
        $this->db->insert("eudr_transportation_driver", $data);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }

        $driver_id = $this->db->getInsertId();

        return $this->findDriverOfId($driver_id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateDriver(int $driver_id, array $data_update): Driver
    {
        $this->db->where("driver_id", $driver_id);
        $this->db->update("eudr_transportation_driver", $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new DriverNotFoundException("Driver not found with ID: $driver_id");
        }
        return $this->findDriverOfId($driver_id);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDriver(int $driver_id, int $deleted_by): void
    {
        $this->db->where("driver_id", $driver_id);
        $this->db->update('eudr_transportation_driver', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

}
