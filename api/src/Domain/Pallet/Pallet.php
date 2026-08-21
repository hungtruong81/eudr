<?php

declare(strict_types=1);

namespace App\Domain\Pallet;

use JsonSerializable;

class Pallet implements JsonSerializable
{
    private $pallet_id;
    private $pallet_code;
    private $warehouse_id;
    private $status;
    private $total_bales;
    private $total_weight;
    private $packed_at;
    private $shipped_at;
    private $created_at;
    private $company_id;
    private $created_by;
    private $updated_at;
    private $updated_by;
    private $items;

    public function __construct(?int $pallet_id, array $data)
    {
        $this->pallet_id = $pallet_id;
        $this->pallet_code = $data['pallet_code'] ?? '';
        $this->warehouse_id = isset($data['warehouse_id']) ? (int)$data['warehouse_id'] : 0;
        $this->status = $data['status'] ?? 'empty';
        $this->total_bales = isset($data['total_bales']) ? (int)$data['total_bales'] : 0;
        $this->total_weight = isset($data['total_weight']) ? (float)$data['total_weight'] : 0.0;
        $this->packed_at = $data['packed_at'] ?? null;
        $this->shipped_at = $data['shipped_at'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->company_id = isset($data['company_id']) ? (int)$data['company_id'] : 0;
        $this->created_by = isset($data['created_by']) ? (int)$data['created_by'] : 0;
        $this->updated_at = $data['updated_at'] ?? null;
        $this->updated_by = isset($data['updated_by']) ? (int)$data['updated_by'] : 0;
        $this->items = $data['items'] ?? [];
    }

    public function getId(): ?int
    {
        return $this->pallet_id;
    }

    public function getCode(): ?string
    {
        return $this->pallet_code;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'pallet_id' => $this->pallet_id,
            'pallet_code' => $this->pallet_code,
            'warehouse_id' => $this->warehouse_id,
            'status' => $this->status,
            'total_bales' => $this->total_bales,
            'total_weight' => $this->total_weight,
            'packed_at' => $this->packed_at,
            'shipped_at' => $this->shipped_at,
            'created_at' => $this->created_at,
            'company_id' => $this->company_id,
            'created_by' => $this->created_by,
            'updated_at' => $this->updated_at,
            'updated_by' => $this->updated_by,
            'items' => $this->items,
        ];
    }
}
