<?php

declare(strict_types=1);

namespace App\Domain\TransportationRoute;

use JsonSerializable;

class TransportationRoute implements JsonSerializable
{
    /**
     * @var int|null
    */
    private $transportation_route_id;
    /**
     * @var string|null
     */
    private $transportation_route_code;
    /**
     * @var int|null
     */
    private $vehicle_id;
    /**
     * @var string|null
     */
    private $vehicle_name;
    /**
     * @var string|null
     */
    private $vehicle_license_plate;
    /**
     * @var int|null
     */
    private $driver_id;
    /**
     * @var string|null
     */
    private $driver_name;
    /**
     * @var string|null
     */
    private $transport_date;
    /**
     * @var string|null
     */
    private $pickup_time;
    /**
     * @var string|null
     */
    private $source_type;
    /**
     * @var array|null
     */
    private $source_transaction_ticket_ids;
    /**
     * @var array|null
     */
    private $source_transaction_tickets;
    /**
     * @var int|null
     */
    private $source_factory_id;
    /**
     * @var int|null
     */
    private $destination_factory_id;
    /**
     * @var string|null
     */
    private $destination_factory_name;
    /**
     * @var int|null
     */
    private $destination_raw_material_tank_id;
    /**
     * @var string|null
     */
    private $status;
    /**
     * @var string|null
     */
    private $created_at;

    /**
     * @param int|null  $transportation_route_id
     * @param array    $data
     */
    public function __construct(?int $transportation_route_id, array $data)
    {
        $this->transportation_route_id = $transportation_route_id;
        $this->transportation_route_code = $data['transportation_route_code'] ?? '';
        $this->vehicle_id = $data['vehicle_id'] ?? 0;
        $this->vehicle_name = $data['vehicle_name'] ?? '';
        $this->vehicle_license_plate = $data['vehicle_license_plate'] ?? '';
        $this->driver_id = $data['driver_id'] ?? 0;
        $this->driver_name = $data['driver_name'] ?? '';
        $this->transport_date = $data['transport_date'] ?? '';
        $this->pickup_time = $data['pickup_time'] ?? '';
        $this->source_type = $data['source_type'] ?? '';
        $this->source_transaction_ticket_ids = $data['source_transaction_ticket_ids'] ?? [];
        $this->source_transaction_tickets = $data['source_transaction_tickets'] ?? [];
        $this->source_factory_id = $data['source_factory_id'] ?? 0;
        $this->destination_factory_id = $data['destination_factory_id'] ?? 0;
        $this->destination_factory_name = $data['destination_factory_name'] ?? '';
        $this->destination_raw_material_tank_id = $data['destination_raw_material_tank_id'] ?? 0;
        $this->status = $data['status'] ?? '';
        $this->created_at = $data['created_at'] ?? '';
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->transportation_route_id;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->transportation_route_code;
    }

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {   
        return $this->status;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'transportation_route_id' => $this->transportation_route_id,
            'transportation_route_code' => $this->transportation_route_code,
            'vehicle_id' => $this->vehicle_id,
            'vehicle_name' => $this->vehicle_name,
            'vehicle_license_plate' => $this->vehicle_license_plate,
            'driver_id' => $this->driver_id,
            'driver_name' => $this->driver_name,
            'transport_date' => $this->transport_date,
            'pickup_time' => $this->pickup_time,
            'source_type' => $this->source_type,
            'source_transaction_tickets' => $this->source_transaction_tickets,
            'source_factory_id' => $this->source_factory_id,
            'destination_factory_id' => $this->destination_factory_id,
            'destination_factory_name' => $this->destination_factory_name,
            'destination_raw_material_tank_id' => $this->destination_raw_material_tank_id,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
