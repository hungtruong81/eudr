<?php

declare(strict_types=1);

namespace App\Domain\CustomField;

use JsonSerializable;

class CustomFieldDefinition implements JsonSerializable
{
    private ?int $field_id;
    private string $field_code;
    private string $field_key;
    private string $field_label;
    private ?string $field_description;
    private string $entity_type;
    private string $field_type;
    private ?array $options;
    private bool $is_required;
    private bool $is_searchable;
    private int $sort_order;
    private string $status;
    private int $company_id;
    private int $created_by;
    private ?string $created_at;
    private ?int $updated_by;
    private ?string $updated_at;
    private int $deleted_by;
    private ?string $deleted_at;

    public function __construct(?int $field_id, array $data)
    {
        $this->field_id          = $field_id;
        $this->field_code        = (string)($data['field_code'] ?? '');
        $this->field_key         = (string)($data['field_key'] ?? '');
        $this->field_label       = (string)($data['field_label'] ?? '');
        $this->field_description = $data['field_description'] ?? null;
        $this->entity_type       = (string)($data['entity_type'] ?? '');
        $this->field_type        = (string)($data['field_type'] ?? 'text');
        $rawOptions              = $data['options'] ?? null;
        if (is_string($rawOptions) && $rawOptions !== '') {
            $decoded = json_decode($rawOptions, true);
            $this->options = is_array($decoded) ? $decoded : null;
        } else {
            $this->options = is_array($rawOptions) ? $rawOptions : null;
        }
        $this->is_required    = (bool)($data['is_required'] ?? false);
        $this->is_searchable  = (bool)($data['is_searchable'] ?? false);
        $this->sort_order     = (int)($data['sort_order'] ?? 0);
        $this->status         = (string)($data['status'] ?? 'active');
        $this->company_id     = (int)($data['company_id'] ?? 0);
        $this->created_by     = (int)($data['created_by'] ?? 0);
        $this->created_at     = $data['created_at'] ?? null;
        $this->updated_by     = isset($data['updated_by']) ? (int)$data['updated_by'] : null;
        $this->updated_at     = $data['updated_at'] ?? null;
        $this->deleted_by     = (int)($data['deleted_by'] ?? 0);
        $this->deleted_at     = $data['deleted_at'] ?? null;
    }

    public function getId(): ?int
    {
        return $this->field_id;
    }

    public function getCode(): string
    {
        return $this->field_code;
    }

    public function getKey(): string
    {
        return $this->field_key;
    }

    public function getEntityType(): string
    {
        return $this->entity_type;
    }

    public function getFieldType(): string
    {
        return $this->field_type;
    }

    public function getCompanyId(): int
    {
        return $this->company_id;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return [
            'field_id'          => $this->field_id,
            'field_code'        => $this->field_code,
            'field_key'         => $this->field_key,
            'field_label'       => $this->field_label,
            'field_description' => $this->field_description,
            'entity_type'       => $this->entity_type,
            'field_type'        => $this->field_type,
            'options'           => $this->options,
            'is_required'       => $this->is_required,
            'is_searchable'     => $this->is_searchable,
            'sort_order'        => $this->sort_order,
            'status'            => $this->status,
            'company_id'        => $this->company_id,
            'created_by'        => $this->created_by,
            'created_at'        => $this->created_at,
            'updated_by'        => $this->updated_by,
            'updated_at'        => $this->updated_at,
            'deleted_by'        => $this->deleted_by,
            'deleted_at'        => $this->deleted_at,
        ];
    }
}
