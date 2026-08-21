<?php

declare(strict_types=1);

namespace App\Domain\Sales\Issue;

use JsonSerializable;

class SalesIssueAllocation implements JsonSerializable
{
    /**
     * @var int|null
     */
    private ?int $issue_allocation_id;
    /**
     * @var int
     */
    private int $issue_item_id;
    /**
     * @var int
     */
    private int $sale_order_item_id;
    /**
     * @var int|null
     */
    private ?int $product_tank_id;
    /**
     * @var int|null
     */
    private ?int $raw_material_tank_id;
    /**
     * @var int|null
     */
    private ?int $transaction_ticket_id;
    /**
     * @var int|null
     */
    private ?int $lot_id;
    /**
     * @var float
     */
    private float $qty_issued;
    /**
     * @var float|null
     */
    private ?float $weight_issued;
    /**
     * @var string|null
     */
    private ?string $notes;

    /**
     * @param int|null  $issue_allocation_id
     * @param array    $data
     */
    public function __construct(?int $issue_allocation_id, array $data)
    {
        $this->issue_allocation_id = $issue_allocation_id;
        $this->issue_item_id = (int)($data['issue_item_id'] ?? 0);
        $this->sale_order_item_id = (int)($data['sale_order_item_id'] ?? 0);
        $this->product_tank_id = isset($data['product_tank_id']) ? (int)$data['product_tank_id'] : null;
        $this->raw_material_tank_id = isset($data['raw_material_tank_id']) ? (int)$data['raw_material_tank_id'] : null;
        $this->transaction_ticket_id = isset($data['transaction_ticket_id']) ? (int)$data['transaction_ticket_id'] : null;
        $this->lot_id = isset($data['lot_id']) ? (int)$data['lot_id'] : null;
        $this->qty_issued = (float)($data['qty_issued'] ?? 0);
        $this->weight_issued = isset($data['weight_issued']) ? (float)$data['weight_issued'] : null;
        $this->notes = $data['notes'] ?? null;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return [
            'issue_allocation_id' => $this->issue_allocation_id,
            'issue_item_id' => $this->issue_item_id,
            'sale_order_item_id' => $this->sale_order_item_id,
            'product_tank_id' => $this->product_tank_id,
            'raw_material_tank_id' => $this->raw_material_tank_id,
            'transaction_ticket_id' => $this->transaction_ticket_id,
            'lot_id' => $this->lot_id,
            'qty_issued' => $this->qty_issued,
            'weight_issued' => $this->weight_issued,
            'notes' => $this->notes,
        ];
    }
}
