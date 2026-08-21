<?php

declare(strict_types=1);

namespace App\Domain\FinishedGoodsReceipt;

use JsonSerializable;

class FinishedGoodsReceipt implements JsonSerializable
{
    /**
     * @var int|null
    */
    private $finished_goods_receipt_id;
    /**
     * @var string|null
     */
    private $finished_goods_receipt_code;
    /**
     * @var string|null
     */
    private $finished_goods_receipt_name;
    /**
     * @var string|null
     */
    private $product_type_category;
    /**
     * @var int|null
     */
    private $production_order_id;
    /**
     * @var string|null
     */
    private $production_order_name;
    /**
     * @var int|null
     */
    private $product_type_id;
    /**
     * @var string|null
     */
    private $product_type_name;
    /**
     * @var int|null
     */
    private $product_tank_id;
    /**
     * @var string|null
     */
    private $product_tank_name;
    /**
     * @var int|null
     */
    private $actual_quantity;
    /**
     * @var string|null
     */
    private $actual_weight;
    /**
     * @var string|null
     */
    private $tank_volume_before;
    /**
     * @var string|null
     */
    private $tank_volume_after;
    /**
     * @var string|null
     */
    private $status;
    /**
     * @var string|null
     */
    private $created_at;

    /**
     * @param int|null  $finished_goods_receipt_id
     * @param array    $data
     */
    public function __construct(?int $finished_goods_receipt_id, array $data)
    {
        $this->finished_goods_receipt_id = $finished_goods_receipt_id;
        $this->finished_goods_receipt_code = $data['finished_goods_receipt_code'] ?? '';
        $this->finished_goods_receipt_name = $data['finished_goods_receipt_name'] ?? '';
        $this->product_type_category = $data['product_type_category'] ?? '';
        $this->production_order_id = $data['production_order_id'] ?? 0;
        $this->production_order_name = $data['production_order_name'] ?? '';
        $this->product_type_id = $data['product_type_id'] ?? 0;
        $this->product_type_name = $data['product_type_name'] ?? '';
        $this->product_tank_id = $data['product_tank_id'] ?? 0;
        $this->product_tank_name = $data['product_tank_name'] ?? '';
        $this->actual_quantity = $data['actual_quantity'] ?? 0;
        $this->actual_weight = $data['actual_weight'] ?? '';
        $this->tank_volume_before = $data['tank_volume_before'] ?? '';
        $this->tank_volume_after = $data['tank_volume_after'] ?? '';
        $this->status = $data['status'] ?? '';
        $this->created_at = $data['created_at'] ?? '';
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->finished_goods_receipt_id;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->finished_goods_receipt_code;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->finished_goods_receipt_name;
    }

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @return int|null
     */
    public function getActualQuantity(): ?int
    {
        return $this->actual_quantity;
    }

    /**
     * @return float|null
     */
    public function getActualWeight(): ?float
    {
        return (float)$this->actual_weight;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'finished_goods_receipt_id' => $this->finished_goods_receipt_id,
            'finished_goods_receipt_code' => $this->finished_goods_receipt_code,
            'finished_goods_receipt_name' => $this->finished_goods_receipt_name,
            'product_type_category' => $this->product_type_category,
            'production_order_id' => $this->production_order_id,
            'production_order_name' => $this->production_order_name,
            'product_type_id' => $this->product_type_id,
            'product_type_name' => $this->product_type_name,
            'product_tank_id' => $this->product_tank_id,
            'product_tank_name' => $this->product_tank_name,
            'actual_quantity' => $this->actual_quantity,
            'actual_weight' => $this->actual_weight,
            'tank_volume_before' => $this->tank_volume_before,
            'tank_volume_after' => $this->tank_volume_after,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
