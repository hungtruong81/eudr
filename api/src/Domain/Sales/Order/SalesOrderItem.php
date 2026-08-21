<?php

declare(strict_types=1);

namespace App\Domain\Sales\Order;

use JsonSerializable;

class SalesOrderItem implements JsonSerializable
{
    /**
     * @var int|null
    */
    private ?int $sale_order_item_id;
    /**
     * @var int
    */
    private int $sale_order_id;
    /**
     * @var int
    */
    private int $company_id;
    /**
     * @var string
    */
    private string $source_type;
    /**
     * @var int|null
    */
    private ?int $transaction_ticket_id;
    /**
     * @var int|null
    */
    private ?int $raw_material_tank_id;
    /**
     * @var int|null
    */
    private ?int $product_tank_id;
    /**
     * @var int|null
    */
    private ?int $product_type_id;
    /**
     * @var int|null
    */
    private ?int $product_lot_id;
    /**
     * @var string|null
    */
    private ?string $rubber_type;
    /**
     * @var float|null
    */
    private ?float $quality_grade;
    /**
     * @var string
    */
    private string $uom;
    /**
     * @var float
    */
    private float $qty_ordered;
    /**
     * @var float
    */
    private float $qty_allocated;
    /**
     * @var float
    */
    private float $qty_shipped;
    /**
     * @var float
    */
    private float $price;
    /**
     * @var float
    */
    private float $discount_rate;
    /**
     * @var float
    */
    private float $surcharge;
    /**
     * @var string
    */
    private string $currency;
    /**
     * @var string|null
    */
    private ?string $notes;

    /**
     * @param int|null  $sale_order_item_id
     * @param array    $data
     */
    public function __construct(?int $sale_order_item_id, array $data)
    {
        $this->sale_order_item_id = $sale_order_item_id;
        $this->sale_order_id = (int)($data['sale_order_id'] ?? 0);
        $this->company_id = (int)($data['company_id'] ?? 0);
        $this->source_type = (string)($data['source_type'] ?? 'finished_product');
        $this->transaction_ticket_id = isset($data['transaction_ticket_id']) ? (int)$data['transaction_ticket_id'] : null;
        $this->raw_material_tank_id = isset($data['raw_material_tank_id']) ? (int)$data['raw_material_tank_id'] : null;
        $this->product_tank_id = isset($data['product_tank_id']) ? (int)$data['product_tank_id'] : null;
        $this->product_type_id = isset($data['product_type_id']) ? (int)$data['product_type_id'] : null;
        $this->product_lot_id = isset($data['product_lot_id']) ? (int)$data['product_lot_id'] : null;
        $this->rubber_type = $data['rubber_type'] ?? null;
        $this->quality_grade = isset($data['quality_grade']) ? (float)$data['quality_grade'] : null;
        $this->uom = (string)($data['uom'] ?? '');
        $this->qty_ordered = (float)($data['qty_ordered'] ?? 0);
        $this->qty_allocated = (float)($data['qty_allocated'] ?? 0);
        $this->qty_shipped = (float)($data['qty_shipped'] ?? 0);
        $this->price = (float)($data['price'] ?? 0);
        $this->discount_rate = (float)($data['discount_rate'] ?? 0);
        $this->surcharge = (float)($data['surcharge'] ?? 0);
        $this->currency = (string)($data['currency'] ?? 'VND');
        $this->notes = $data['notes'] ?? null;
    }
    
    /**
     * @return array<int,array<string,mixed>>
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return [
            'sale_order_item_id' => $this->sale_order_item_id,
            'sale_order_id' => $this->sale_order_id,
            'company_id' => $this->company_id,
            'source_type' => $this->source_type,
            'transaction_ticket_id' => $this->transaction_ticket_id,
            'raw_material_tank_id' => $this->raw_material_tank_id,
            'product_tank_id' => $this->product_tank_id,
            'product_type_id' => $this->product_type_id,
            'product_lot_id' => $this->product_lot_id,
            'rubber_type' => $this->rubber_type,
            'quality_grade' => $this->quality_grade,
            'uom' => $this->uom,
            'qty_ordered' => $this->qty_ordered,
            'qty_allocated' => $this->qty_allocated,
            'qty_shipped' => $this->qty_shipped,
            'price' => $this->price,
            'discount_rate' => $this->discount_rate,
            'surcharge' => $this->surcharge,
            'currency' => $this->currency,
            'notes' => $this->notes,
        ];
    }
}
