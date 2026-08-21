<?php

declare(strict_types=1);

namespace App\Domain\ProductType;

use JsonSerializable;

class ProductType implements JsonSerializable
{
    /**
     * @var int|null
    */
    private $product_type_id;
    /**
     * @var string|null
     */
    private $product_type_code;
    /**
     * @var string|null
     */
    private $product_type_name;
    /**
     * @var string|null
     */
    private $product_type_category;
    /**
     * @var float|null
     */
    private $product_weight;
    /**
     * @var string|null
     */
    private $description;

    /**
     * @param int|null  $product_type_id
     * @param array    $data
     */
    public function __construct(?int $product_type_id, array $data)
    {
        $this->product_type_id = $product_type_id;
        $this->product_type_code = $data['product_type_code'] ?? '';
        $this->product_type_name = $data['product_type_name'] ?? '';
        $this->product_type_category = $data['product_type_category'] ?? '';
        $this->product_weight = $data['product_weight'] ?? 0;
        $this->description = $data['description'] ?? '';
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->product_type_id;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->product_type_code;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->product_type_name;
    }

    /**
     * @return float|null
     */
    public function getProductWeight(): ?float
    {
        return (float)$this->product_weight;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'product_type_id' => $this->product_type_id,
            'product_type_code' => $this->product_type_code,
            'product_type_name' => $this->product_type_name,
            'product_type_category' => $this->product_type_category,
            'product_weight' => $this->product_weight,
            'description' => $this->description,
        ];
    }
}
