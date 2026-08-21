<?php

declare(strict_types=1);

namespace App\Domain\VehicleTank;

use JsonSerializable;

class VehicleTank implements JsonSerializable
{
    private ?int $vehicle_tank_id;
    private int $vehicle_id;
    private string $vehicle_tank_code;
    private string $vehicle_tank_name;
    private string $tank_type;
    private float $capacity_kg;
    private float $current_weight_kg;
    private ?string $compartment_no;
    private string $status;
    private ?string $notes;
    private ?string $vehicle_code;
    private ?string $license_plate;
    private string $created_at;
    private int $created_by;

    public function __construct(?int $vehicle_tank_id, array $data)
    {
        $this->vehicle_tank_id = $vehicle_tank_id;
        $this->vehicle_id = (int)($data['vehicle_id'] ?? 0);
        $this->vehicle_tank_code = (string)($data['vehicle_tank_code'] ?? '');
        $this->vehicle_tank_name = (string)($data['vehicle_tank_name'] ?? '');
        $this->tank_type = (string)($data['tank_type'] ?? 'latex');
        $this->capacity_kg = (float)($data['capacity_kg'] ?? 0);
        $this->current_weight_kg = (float)($data['current_weight_kg'] ?? 0);
        $this->compartment_no = isset($data['compartment_no']) ? (string)$data['compartment_no'] : null;
        $this->status = (string)($data['status'] ?? 'idle');
        $this->notes = isset($data['notes']) ? (string)$data['notes'] : null;
        $this->vehicle_code = isset($data['vehicle_code']) ? (string)$data['vehicle_code'] : null;
        $this->license_plate = isset($data['license_plate']) ? (string)$data['license_plate'] : null;
        $this->created_at = (string)($data['created_at'] ?? '');
        $this->created_by = (int)($data['created_by'] ?? 0);
    }

    public function getId(): ?int
    {
        return $this->vehicle_tank_id;
    }

    public function getVehicleId(): int
    {
        return $this->vehicle_id;
    }

    public function getCode(): string
    {
        return $this->vehicle_tank_code;
    }

    public function getCapacityKg(): float
    {
        return $this->capacity_kg;
    }

    public function getCurrentWeightKg(): float
    {
        return $this->current_weight_kg;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isUsable(): bool
    {
        return !in_array($this->status, ['maintenance', 'inactive'], true);
    }

    public function jsonSerialize(): array
    {
        return [
            'vehicle_tank_id' => $this->vehicle_tank_id,
            'vehicle_id' => $this->vehicle_id,
            'vehicle_code' => $this->vehicle_code,
            'license_plate' => $this->license_plate,
            'vehicle_tank_code' => $this->vehicle_tank_code,
            'vehicle_tank_name' => $this->vehicle_tank_name,
            'tank_type' => $this->tank_type,
            'capacity_kg' => $this->capacity_kg,
            'current_weight_kg' => $this->current_weight_kg,
            'available_capacity_kg' => max(0, $this->capacity_kg - $this->current_weight_kg),
            'compartment_no' => $this->compartment_no,
            'status' => $this->status,
            'is_usable' => $this->isUsable(),
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'created_by' => $this->created_by,
        ];
    }
}
