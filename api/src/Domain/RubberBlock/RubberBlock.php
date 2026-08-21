<?php

declare(strict_types=1);

namespace App\Domain\RubberBlock;

use JsonSerializable;

class RubberBlock implements JsonSerializable
{
    /**
     * @var int|null
     */
    private $rubber_block_id;
    /**
     * @var string|null
     */
    private $rubber_block_code;
    /**
     * @var int|null
     */
    private $production_order_id;
    /**
     * @var int|null
     */
    private $product_type_id;
    /**
     * @var string|null
     */
    private $weight;
    /**
     * @var string|null
     */
    private $grade;
    /**
     * @var string|null
     */
    private $production_date;
    /**
     * @var string|null
     */
    private $status;
    /**
     * @var string|null
     */
    private $created_at;
    /**
     * @var string|null
     */
    private $updated_at;
    /**
     * @var string|null
     */
    private $product_type_name;
    /**
     * @var string|null
     */
    private $product_type_code;
    /**
     * @var string|null
     */
    private $production_order_name;
    /**
     * @var string|null
     */
    private $production_order_code;

    /**
     * @param int|null $rubber_block_id
     * @param array $data
     */
    public function __construct(?int $rubber_block_id, array $data)
    {
        $this->rubber_block_id = $rubber_block_id;
        $this->rubber_block_code = $data['rubber_block_code'] ?? '';
        $this->production_order_id = $data['production_order_id'] ?? 0;
        $this->product_type_id = $data['product_type_id'] ?? 0;
        $this->weight = $data['weight'] ?? '0.00';
        $this->grade = $data['grade'] ?? '';
        $this->production_date = $data['production_date'] ?? '';
        $this->status = $data['status'] ?? 'available';
        $this->created_at = $data['created_at'] ?? '';
        $this->updated_at = $data['updated_at'] ?? '';
        $this->product_type_name = $data['product_type_name'] ?? '';
        $this->product_type_code = $data['product_type_code'] ?? '';
        $this->production_order_name = $data['production_order_name'] ?? '';
        $this->production_order_code = $data['production_order_code'] ?? '';
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->rubber_block_id;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->rubber_block_code;
    }

    /**
     * @return int|null
     */
    public function getProductionOrderId(): ?int
    {
        return $this->production_order_id;
    }

    /**
     * @return int|null
     */
    public function getProductTypeId(): ?int
    {
        return $this->product_type_id;
    }

    /**
     * @return float
     */
    public function getWeight(): float
    {
        return (float)$this->weight;
    }

    /**
     * @return string|null
     */
    public function getGrade(): ?string
    {
        return $this->grade;
    }

    /**
     * @return string|null
     */
    public function getProductionDate(): ?string
    {
        return $this->production_date;
    }

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'rubber_block_id' => $this->rubber_block_id,
            'rubber_block_code' => $this->rubber_block_code,
            'production_order_id' => $this->production_order_id,
            'production_order_name' => $this->production_order_name,
            'production_order_code' => $this->production_order_code,
            'product_type_id' => $this->product_type_id,
            'product_type_name' => $this->product_type_name,
            'product_type_code' => $this->product_type_code,
            'weight' => $this->weight,
            'grade' => $this->grade,
            'production_date' => $this->production_date,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
