<?php

declare(strict_types=1);

namespace App\Domain\Warehouse;

use JsonSerializable;

class Warehouse implements JsonSerializable
{
    /**
     * @var int|null
     */
    private $warehouse_id;
    /**
     * @var string|null
     */
    private $warehouse_code;
    /**
     * @var string|null
     */
    private $warehouse_name;
    /**
     * @var int|null
     */
    private $company_id;
    /**
     * @var int|null
     */
    private $factory_id;
    /**
     * @var string|null
     */
    private $factory_name;
    /**
     * @var string|null
     */
    private $warehouse_type;
    /**
     * @var string|null
     */
    private $address;
    /**
     * @var int|null
     */
    private $manager_user_id;
    /**
     * @var int|null
     */
    private $capacity_pallet;
    /**
     * @var float|null
     */
    private $max_weight_kg;
    /**
     * @var int|null
     */
    private $current_pallet_count;
    /**
     * @var float|null
     */
    private $current_weight_kg;
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
     * @param int|null $warehouse_id
     * @param array $data
     */
    public function __construct(?int $warehouse_id, array $data)
    {
        $this->warehouse_id = $warehouse_id;
        $this->warehouse_code = $data['warehouse_code'] ?? '';
        $this->warehouse_name = $data['warehouse_name'] ?? '';
        $this->company_id = (int)($data['company_id'] ?? 0);
        $this->factory_id = (int)($data['factory_id'] ?? 0);
        $this->factory_name = $data['factory_name'] ?? '';
        $this->warehouse_type = $data['warehouse_type'] ?? '';
        $this->address = $data['address'] ?? '';
        $this->manager_user_id = (int)($data['manager_user_id'] ?? 0);
        $this->capacity_pallet = (int)($data['capacity_pallet'] ?? 0);
        $this->max_weight_kg = (float)($data['max_weight_kg'] ?? 0);
        $this->current_pallet_count = (int)($data['current_pallet_count'] ?? 0);
        $this->current_weight_kg = (float)($data['current_weight_kg'] ?? 0);
        $this->status = $data['status'] ?? '';
        $this->notes = $data['notes'] ?? '';
        $this->created_at = $data['created_at'] ?? '';
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->warehouse_id;
    }

    /**
     * @return int|null
     */
    public function getCurrentPalletCount(): ?int
    {
        return $this->current_pallet_count;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'warehouse_id' => $this->warehouse_id,
            'warehouse_code' => $this->warehouse_code,
            'warehouse_name' => $this->warehouse_name,
            'company_id' => $this->company_id,
            'factory_id' => $this->factory_id,
            'factory_name' => $this->factory_name,
            'warehouse_type' => $this->warehouse_type,
            'address' => $this->address,
            'manager_user_id' => $this->manager_user_id,
            'capacity_pallet' => $this->capacity_pallet,
            'max_weight_kg' => $this->max_weight_kg,
            'current_pallet_count' => $this->current_pallet_count,
            'current_weight_kg' => $this->current_weight_kg,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
