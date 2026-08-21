<?php

declare(strict_types=1);

namespace App\Domain\Vehicle;

use JsonSerializable;

class Vehicle implements JsonSerializable
{
    /**
     * @var int|null
    */
    private $vehicle_id;
    /**
     * @var string|null
     */
    private $vehicle_code;
    /**
     * @var string|null
     */
    private $vehicle_name;
    /**
     * @var string|null
     */
    private $brand;
    /**
     * @var string|null
     */
    private $type;
    /**
     * @var int|null
     */
    private $manufacture_year;
    /**
     * @var string|null
     */
    private $license_plate;
    /**
     * @var string|null
     */
    private $created_at;

    /**
     * @param int|null  $vehicle_id
     * @param array    $data
     */
    public function __construct(?int $vehicle_id, array $data)
    {
        $this->vehicle_id = $vehicle_id;
        $this->vehicle_code = $data['vehicle_code'] ?? '';
        $this->vehicle_name = $data['vehicle_name'] ?? '';
        $this->brand = $data['brand'] ?? '';
        $this->type = $data['type'] ?? '';
        $this->manufacture_year = $data['manufacture_year'] ?? 0;
        $this->license_plate = $data['license_plate'] ?? '';
        $this->created_at = $data['created_at'] ?? '';
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->vehicle_id;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->vehicle_code;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->vehicle_name;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'vehicle_id' => $this->vehicle_id,
            'vehicle_code' => $this->vehicle_code,
            'vehicle_name' => $this->vehicle_name,
            'brand' => $this->brand,
            'type' => $this->type,
            'manufacture_year' => $this->manufacture_year,
            'license_plate' => $this->license_plate,
            'created_at' => $this->created_at,
        ];
    }
}
