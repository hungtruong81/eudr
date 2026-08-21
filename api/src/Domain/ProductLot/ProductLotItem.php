<?php

declare(strict_types=1);

namespace App\Domain\ProductLot;

use JsonSerializable;

class ProductLotItem implements JsonSerializable
{
    /**
     * @var int|null
     */
    private $product_lot_item_id;
    /**
     * @var int|null
     */
    private $product_lot_id;
    /**
     * @var int|null
     */
    private $rubber_block_id;
    /**
     * @var string
     */
    private $weight_snapshot;
    /**
     * @var string
     */
    private $grade_snapshot;
    /**
     * @var string|null
     */
    private $created_at;
    /**
     * @var string|null
     */
    private $rubber_block_code;
    /**
     * @var int|null
     */
    private $product_type_id;
    /**
     * @var string|null
     */
    private $product_type_name;
    /**
     * @var string|null
     */
    private $product_type_code;
    /**
     * @var int|null
     */
    private $production_order_id;

    /**
     * @param int|null $product_lot_item_id
     * @param array $data
     */
    public function __construct(?int $product_lot_item_id, array $data)
    {
        $this->product_lot_item_id = $product_lot_item_id;
        $this->product_lot_id = $data['product_lot_id'] ?? 0;
        $this->rubber_block_id = $data['rubber_block_id'] ?? 0;
        $this->weight_snapshot = $data['weight_snapshot'] ?? '0.00';
        $this->grade_snapshot = $data['grade_snapshot'] ?? '';
        $this->created_at = $data['created_at'] ?? '';
        $this->rubber_block_code = $data['rubber_block_code'] ?? null;
        $this->product_type_id = $data['product_type_id'] ?? null;
        $this->product_type_name = $data['product_type_name'] ?? null;
        $this->product_type_code = $data['product_type_code'] ?? null;
        $this->production_order_id = $data['production_order_id'] ?? null;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->product_lot_item_id;
    }

    /**
     * @return int|null
     */
    public function getProductLotId(): ?int
    {
        return $this->product_lot_id;
    }

    /**
     * @return int|null
     */
    public function getRubberBlockId(): ?int
    {
        return $this->rubber_block_id;
    }

    /**
     * @return float
     */
    public function getWeightSnapshot(): float
    {
        return (float)$this->weight_snapshot;
    }

    /**
     * @return string
     */
    public function getGradeSnapshot(): string
    {
        return $this->grade_snapshot;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'product_lot_item_id' => $this->product_lot_item_id,
            'product_lot_id' => $this->product_lot_id,
            'rubber_block_id' => $this->rubber_block_id,
            'rubber_block_code' => $this->rubber_block_code,
            'product_type_id' => $this->product_type_id,
            'product_type_name' => $this->product_type_name,
            'product_type_code' => $this->product_type_code,
            'production_order_id' => $this->production_order_id,
            'weight_snapshot' => $this->weight_snapshot,
            'grade_snapshot' => $this->grade_snapshot,
            'created_at' => $this->created_at,
        ];
    }
}
