<?php

declare(strict_types=1);

namespace App\Domain\ProductionSettlingTank;

use JsonSerializable;

class ProductionSettlingTank implements JsonSerializable
{
    /**
     * @var int|null
     */
    private $settling_tank_id;
    /**
     * @var string|null
     */
    private $settling_tank_code;
    /**
     * @var string|null
     */
    private $settling_tank_name;
    /**
     * @var int
     */
    private $factory_id;
    /**
     * @var string|null
     */
    private $factory_name;
    /**
     * @var float|null
     */
    private $capacity_kg;
    /**
     * @var string|null
     */
    private $status;
    /**
     * @var string|null
     */
    private $created_at;

    public function __construct(?int $settling_tank_id, array $data)
    {
        $this->settling_tank_id = $settling_tank_id;
        $this->settling_tank_code = $data['settling_tank_code'] ?? '';
        $this->settling_tank_name = $data['settling_tank_name'] ?? '';
        $this->factory_id = $data['factory_id'] ?? 0;
        $this->factory_name = $data['factory_name'] ?? '';
        $this->capacity_kg = $data['capacity_kg'] ?? 0;
        $this->status = $data['status'] ?? '';
        $this->created_at = $data['created_at'] ?? '';
    }

    public function getId(): ?int
    {
        return $this->settling_tank_id;
    }

    public function getCode(): ?string
    {
        return $this->settling_tank_code;
    }

    public function getName(): ?string
    {
        return $this->settling_tank_name;
    }

    public function getCapacity(): ?float
    {
        return (float)$this->capacity_kg;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'settling_tank_id' => $this->settling_tank_id,
            'settling_tank_code' => $this->settling_tank_code,
            'settling_tank_name' => $this->settling_tank_name,
            'factory_id' => $this->factory_id,
            'factory_name' => $this->factory_name,
            'capacity_kg' => $this->capacity_kg,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
