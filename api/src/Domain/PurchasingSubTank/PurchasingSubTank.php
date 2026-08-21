<?php

declare(strict_types=1);

namespace App\Domain\PurchasingSubTank;

use JsonSerializable;

class PurchasingSubTank implements JsonSerializable
{
    /**
     * @var int|null
    */
    private $sub_tank_id;
    /**
     * @var string|null
     */
    private $sub_tank_code;
    /**
     * @var string|null
     */
    private $sub_tank_name;
    /**
     * @var int|null
     */
    private $company_id;
    /**
     * @var string|null
     */
    private $rubber_type;
    /**
     * @var float|null
     */
    private $capacity_kg;
    /**
     * @var float|null
     */
    private $current_volume_kg;
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
     * @var int|null
     */
    private $created_by;

    /**
     * @param int|null $sub_tank_id
     * @param array $data
     */
    public function __construct(?int $sub_tank_id, array $data)
    {
        $this->sub_tank_id = $sub_tank_id;
        $this->sub_tank_code = $data['sub_tank_code'] ?? '';
        $this->sub_tank_name = $data['sub_tank_name'] ?? '';
        $this->company_id = (int)($data['company_id'] ?? 0);
        $this->rubber_type = $data['rubber_type'] ?? 'latex';
        $this->capacity_kg = (float)($data['capacity_kg'] ?? 0);
        $this->current_volume_kg = (float)($data['current_volume_kg'] ?? 0);
        $this->location = $data['location'] ?? '';
        $this->status = $data['status'] ?? 'idle';
        $this->notes = $data['notes'] ?? '';
        $this->created_at = $data['created_at'] ?? '';
        $this->created_by = (int)($data['created_by'] ?? 0);
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->sub_tank_id;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->sub_tank_code;
    }

    /**
     * @return float|null
     */
    public function getCurrentVolumeKg(): ?float
    {
        return $this->current_volume_kg !== null ? (float)$this->current_volume_kg : null;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'sub_tank_id' => $this->sub_tank_id,
            'sub_tank_code' => $this->sub_tank_code,
            'sub_tank_name' => $this->sub_tank_name,
            'company_id' => $this->company_id,
            'rubber_type' => $this->rubber_type,
            'capacity_kg' => $this->capacity_kg,
            'current_volume_kg' => $this->current_volume_kg,
            'location' => $this->location,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'created_by' => $this->created_by,
        ];
    }
}
