<?php

declare(strict_types=1);

namespace App\Domain\ExternalMaterial;

use JsonSerializable;

class ExternalMaterial implements JsonSerializable
{
    private $external_material_id;
    private $external_material_code;
    private $factory_id;
    private $factory_name;
    private $company_id;
    private $supplier_name;
    private $supplier_phone;
    private $supplier_address;
    private $latex_weight;
    private $latex_tsc_grade;
    private $scrap_rubber_weight;
    private $scrap_rubber_drc_grade;
    private $cup_lump_weight;
    private $total_amount;
    private $purchase_date;
    private $notes;
    private $status;
    private $created_by;
    private $created_at;
    private $updated_by;
    private $updated_at;
    private $lands;
    private $transport;

    public function __construct(?int $external_material_id, array $data)
    {
        $this->external_material_id = $external_material_id;
        $this->external_material_code = $data['external_material_code'] ?? '';
        $this->factory_id = $data['factory_id'] ?? 0;
        $this->factory_name = $data['factory_name'] ?? '';
        $this->company_id = $data['company_id'] ?? 0;
        $this->supplier_name = $data['supplier_name'] ?? '';
        $this->supplier_phone = $data['supplier_phone'] ?? '';
        $this->supplier_address = $data['supplier_address'] ?? '';
        $this->latex_weight = $data['latex_weight'] ?? 0;
        $this->latex_tsc_grade = $data['latex_tsc_grade'] ?? 0;
        $this->scrap_rubber_weight = $data['scrap_rubber_weight'] ?? 0;
        $this->scrap_rubber_drc_grade = $data['scrap_rubber_drc_grade'] ?? 0;
        $this->cup_lump_weight = $data['cup_lump_weight'] ?? 0;
        $this->total_amount = $data['total_amount'] ?? 0;
        $this->purchase_date = $data['purchase_date'] ?? '';
        $this->notes = $data['notes'] ?? '';
        $this->status = $data['status'] ?? 'draft';
        $this->created_by = $data['created_by'] ?? 0;
        $this->created_at = $data['created_at'] ?? '';
        $this->updated_by = $data['updated_by'] ?? 0;
        $this->updated_at = $data['updated_at'] ?? null;
        $this->lands = $data['lands'] ?? [];
        $this->transport = $data['transport'] ?? null;
    }

    public function getId(): ?int { return $this->external_material_id; }
    public function getCode(): ?string { return $this->external_material_code; }
    public function getStatus(): string { return $this->status; }
    public function getFactoryId(): int { return $this->factory_id; }
    public function getCompanyId(): int { return $this->company_id; }
    public function getCreatedBy(): int { return $this->created_by; }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'external_material_id' => $this->external_material_id,
            'external_material_code' => $this->external_material_code,
            'factory_id' => $this->factory_id,
            'factory_name' => $this->factory_name,
            'company_id' => $this->company_id,
            'supplier_name' => $this->supplier_name,
            'supplier_phone' => $this->supplier_phone,
            'supplier_address' => $this->supplier_address,
            'latex_weight' => $this->latex_weight,
            'latex_tsc_grade' => $this->latex_tsc_grade,
            'scrap_rubber_weight' => $this->scrap_rubber_weight,
            'scrap_rubber_drc_grade' => $this->scrap_rubber_drc_grade,
            'cup_lump_weight' => $this->cup_lump_weight,
            'total_amount' => $this->total_amount,
            'purchase_date' => $this->purchase_date,
            'notes' => $this->notes,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_by' => $this->updated_by,
            'updated_at' => $this->updated_at,
            'lands' => $this->lands,
            'transport' => $this->transport,
        ];
    }
}
