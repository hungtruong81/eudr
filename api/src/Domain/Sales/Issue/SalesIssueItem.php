<?php

declare(strict_types=1);

namespace App\Domain\Sales\Issue;

use JsonSerializable;

class SalesIssueItem implements JsonSerializable
{
    /** 
     * @var int|null 
     */
    private ?int $issue_item_id;
    /** 
     * @var int 
     */
    private int $issue_id;
    /** 
     * @var int 
     */
    private int $sale_order_item_id;
    /** 
     * @var int 
     */
    private int $company_id;
    /** 
     * @var int 
     */
    private int $product_id;
    /** 
     * @var string 
     */
    private string $uom;
    /** 
     * @var float 
     */
    private float $qty_issued;
    /** 
     * @var float|null 
     */
    private ?float $price;
    /** 
     * @var string|null 
     */
    private ?string $currency;
    /** 
     * @var string|null 
     */
    private ?string $notes;
    /** 
     * @var array<int,array<string,mixed>>
    */
    private array $allocations;

    /**
     * @param int|null  $issue_item_id
     * @param array    $data
     */
    public function __construct(?int $issue_item_id, array $data)
    {
        $this->issue_item_id = $issue_item_id;
        $this->issue_id = (int)($data['issue_id'] ?? 0);
        $this->sale_order_item_id = (int)($data['sale_order_item_id'] ?? 0);
        $this->company_id = (int)($data['company_id'] ?? 0);
        $this->product_id = (int)($data['product_id'] ?? 0);
        $this->uom = (string)($data['uom'] ?? '');
        $this->qty_issued = (float)($data['qty_issued'] ?? 0);
        $this->price = isset($data['price']) ? (float)$data['price'] : null;
        $this->currency = isset($data['currency']) ? (string)$data['currency'] : null;
        $this->notes = $data['notes'] ?? null;
        $this->allocations = $data['allocations'] ?? [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return [
            'issue_item_id' => $this->issue_item_id,
            'issue_id' => $this->issue_id,
            'sale_order_item_id' => $this->sale_order_item_id,
            'company_id' => $this->company_id,
            'product_id' => $this->product_id,
            'uom' => $this->uom,
            'qty_issued' => $this->qty_issued,
            'price' => $this->price,
            'currency' => $this->currency,
            'notes' => $this->notes,
            'allocations' => $this->allocations,
        ];
    }
}
