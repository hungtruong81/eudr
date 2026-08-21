<?php

declare(strict_types=1);

namespace App\Domain\Price;

use JsonSerializable;

class Price implements JsonSerializable
{
    /**
     * @var int|null
     */
    private $price_id;

    /**
     * @var array
     */
    private $data;

    public function __construct(?int $price_id, array $data)
    {
        $this->price_id = $price_id;
        $this->data = $data;
    }

    public function getId(): ?int
    {
        return $this->price_id;
    }

    public function getCode(): string
    {
        return (string)($this->data['price_code'] ?? '');
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'price_id' => $this->price_id,
            'price_code' => (string)($this->data['price_code'] ?? ''),
            'price_name' => (string)($this->data['price_name'] ?? ''),
            'price_type' => (string)($this->data['price_type'] ?? ''),
            'domestic_price' => (float)($this->data['domestic_price'] ?? 0),
            'international_price' => (float)($this->data['international_price'] ?? 0),
            'company_id' => (int)($this->data['company_id'] ?? 0),
            'created_at' => $this->data['created_at'] ?? null,
            'created_by' => (int)($this->data['created_by'] ?? 0),
            'updated_at' => $this->data['updated_at'] ?? null,
            'updated_by' => (int)($this->data['updated_by'] ?? 0),
            'deleted_at' => $this->data['deleted_at'] ?? null,
            'deleted_by' => (int)($this->data['deleted_by'] ?? 0),
        ];
    }
}
