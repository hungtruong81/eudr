<?php

declare(strict_types=1);

namespace App\Domain\RawMaterialTank;

use JsonSerializable;

class RawMaterialTank implements JsonSerializable
{
    /**
     * @var int|null
    */
    private $raw_material_tank_id;
    /**
     * @var string|null
     */
    private $raw_material_tank_code;
    /**
     * @var string|null
     */
    private $raw_material_tank_name;
    /**
     * @var int
     */
    private $factory_id;
    /**
     * @var string|null
     */
    private $factory_name;
    /**
     * @var string|null
     */
    private $tank_type;
    /**
     * @var float|null
     */
    private $capacity;
    /**
     * @var float|null
     */
    private $current_volume;
    /**
     * @var string|null
     */
    private $location;
    /**
     * @var string|null
     */
    private $status;
    /**
     * @var string|null
     */
    private $notes;
    /**
     * @var string|null
     */
    private $created_at;

    /**
     * @param int|null  $raw_material_tank_id
     * @param array    $data
     */
    public function __construct(?int $raw_material_tank_id, array $data)
    {
        $this->raw_material_tank_id = $raw_material_tank_id;
        $this->raw_material_tank_code = $data['raw_material_tank_code'] ?? '';
        $this->raw_material_tank_name = $data['raw_material_tank_name'] ?? '';
        $this->factory_id = $data['factory_id'] ?? 0;
        $this->factory_name = $data['factory_name'] ?? '';
        $this->tank_type = $data['tank_type'] ?? '';
        $this->capacity = $data['capacity'] ?? 0;
        $this->current_volume = $data['current_volume'] ?? 0;
        $this->location = $data['location'] ?? '';
        $this->status = $data['status'] ?? '';
        $this->notes = $data['notes'] ?? '';
        $this->created_at = $data['created_at'] ?? '';
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->raw_material_tank_id;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->raw_material_tank_code;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->raw_material_tank_name;
    }

    /**
     * @return string|null
     */
    public function getTankType(): ?string
    {
        return $this->tank_type;
    }

    /**
     * @return float|null
     */
    public function getCapacity(): ?float
    {
        return (float)$this->capacity;
    }

    /**
     * @return float|null
     */
    public function getCurrentVolume(): ?float
    {
        return (float)$this->current_volume;
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
            'raw_material_tank_id' => $this->raw_material_tank_id,
            'raw_material_tank_code' => $this->raw_material_tank_code,
            'raw_material_tank_name' => $this->raw_material_tank_name,
            'factory_id' => $this->factory_id,
            'factory_name' => $this->factory_name,
            'tank_type' => $this->tank_type,
            'capacity' => $this->capacity,
            'current_volume' => $this->current_volume,
            'location' => $this->location,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
