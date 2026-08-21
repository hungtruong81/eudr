<?php

declare(strict_types=1);

namespace App\Domain\Grade;

use JsonSerializable;

class Grade implements JsonSerializable
{
    /**
     * @var int|null
    */
    private $grade_id;
    /**
     * @var string|null
     */
    private $grade_code;
    /**
     * @var string|null
     */
    private $name;
    /**
     * @var string|null
     */
    private $description;
    /**
     * @var string|null
     */
    private $created_at;
    /**
     * @var string|null
     */
    private $updated_at;
    /**
     * @var float|null
     */
    private $current_domestic_price;
    /**
     * @var float|null
     */
    private $current_international_price;
    /**
     * @var string|null
     */
    private $current_price_effective_from;
    /**
     * @var string|null
     */
    private $current_price_effective_to;

    /**
     * @param int|null  $grade_id
     * @param array    $data
     */
    public function __construct(?int $grade_id, array $data)
    {
        $this->grade_id = $grade_id;
        $this->grade_code = $data['grade_code'] ?? '';
        $this->name = $data['name'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->created_at = $data['created_at'] ?? '';
        $this->updated_at = $data['updated_at'] ?? null;
        $this->current_domestic_price = isset($data['current_domestic_price']) ? (float)$data['current_domestic_price'] : null;
        $this->current_international_price = isset($data['current_international_price']) ? (float)$data['current_international_price'] : null;
        $this->current_price_effective_from = $data['current_price_effective_from'] ?? null;
        $this->current_price_effective_to = $data['current_price_effective_to'] ?? null;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->grade_id;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->grade_code;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'grade_id' => $this->grade_id,
            'grade_code' => $this->grade_code,
            'name' => $this->name,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'current_domestic_price' => $this->current_domestic_price,
            'current_international_price' => $this->current_international_price,
            'current_price_effective_from' => $this->current_price_effective_from,
            'current_price_effective_to' => $this->current_price_effective_to,
        ];
    }
}
