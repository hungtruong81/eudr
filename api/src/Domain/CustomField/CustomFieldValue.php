<?php

declare(strict_types=1);

namespace App\Domain\CustomField;

use JsonSerializable;

class CustomFieldValue implements JsonSerializable
{
    private ?int $value_id;
    private int $field_id;
    private string $field_key;
    private string $field_label;
    private string $field_type;
    private string $entity_type;
    private int $entity_id;
    private int $company_id;
    private ?string $value_text;
    private ?float $value_number;
    private ?string $value_date;
    private int $created_by;
    private ?string $created_at;
    private ?int $updated_by;
    private ?string $updated_at;

    public function __construct(?int $value_id, array $data)
    {
        $this->value_id      = $value_id;
        $this->field_id      = (int)($data['field_id'] ?? 0);
        $this->field_key     = (string)($data['field_key'] ?? '');
        $this->field_label   = (string)($data['field_label'] ?? '');
        $this->field_type    = (string)($data['field_type'] ?? 'text');
        $this->entity_type   = (string)($data['entity_type'] ?? '');
        $this->entity_id     = (int)($data['entity_id'] ?? 0);
        $this->company_id    = (int)($data['company_id'] ?? 0);
        $this->value_text    = $data['value_text'] ?? null;
        $this->value_number  = isset($data['value_number']) && $data['value_number'] !== null
            ? (float)$data['value_number'] : null;
        $this->value_date    = $data['value_date'] ?? null;
        $this->created_by    = (int)($data['created_by'] ?? 0);
        $this->created_at    = $data['created_at'] ?? null;
        $this->updated_by    = isset($data['updated_by']) ? (int)$data['updated_by'] : null;
        $this->updated_at    = $data['updated_at'] ?? null;
    }

    public function getId(): ?int
    {
        return $this->value_id;
    }

    public function getFieldId(): int
    {
        return $this->field_id;
    }

    /**
     * Returns the display value (resolved from the appropriate column).
     * @return string|float|null
     */
    public function getValue(): mixed
    {
        return match ($this->field_type) {
            'number'                => $this->value_number,
            'date', 'datetime'     => $this->value_date,
            default                => $this->value_text,
        };
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return [
            'value_id'     => $this->value_id,
            'field_id'     => $this->field_id,
            'field_key'    => $this->field_key,
            'field_label'  => $this->field_label,
            'field_type'   => $this->field_type,
            'entity_type'  => $this->entity_type,
            'entity_id'    => $this->entity_id,
            'company_id'   => $this->company_id,
            'value'        => $this->getValue(),
            'value_text'   => $this->value_text,
            'value_number' => $this->value_number,
            'value_date'   => $this->value_date,
            'created_by'   => $this->created_by,
            'created_at'   => $this->created_at,
            'updated_by'   => $this->updated_by,
            'updated_at'   => $this->updated_at,
        ];
    }
}
