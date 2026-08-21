<?php

declare(strict_types=1);

namespace App\Domain\ProductTank;

use JsonSerializable;

class ProductTank implements JsonSerializable
{
    /**
     * @var int|null
    */
    private $product_tank_id;
    /**
     * @var string|null
     */
    private $product_tank_code;
    /**
     * @var string|null
     */
    private $product_tank_name;
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
    private $product_type;
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
     * @param int|null  $product_tank_id
     * @param array    $data
     */
    public function __construct(?int $product_tank_id, array $data)
    {
        $this->product_tank_id = $product_tank_id;
        $this->product_tank_code = $data['product_tank_code'] ?? '';
        $this->product_tank_name = $data['product_tank_name'] ?? '';
        $this->factory_id = $data['factory_id'] ?? 0;
        $this->factory_name = $data['factory_name'] ?? '';
        $this->product_type = $data['product_type'] ?? '';
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
        return $this->product_tank_id;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->product_tank_code;
    }

    /**
     * @return float|null
     */
    public function getCurrentVolume(): ?float
    {
        return (float)$this->current_volume;
    }

    /**
     * @return float|null
     */
    public function getCapacity(): ?float
    {
        return (float)$this->capacity;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->product_tank_name;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'product_tank_id' => $this->product_tank_id,
            'product_tank_code' => $this->product_tank_code,
            'product_tank_name' => $this->product_tank_name,
            'factory_id' => $this->factory_id,
            'factory_name' => $this->factory_name,
            'product_type' => $this->product_type,
            'capacity' => $this->capacity,
            'current_volume' => $this->current_volume,
            'location' => $this->location,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
