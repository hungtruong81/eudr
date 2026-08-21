<?php

declare(strict_types=1);

namespace App\Domain\Sales\Contract;

use JsonSerializable;

class SalesContractItem implements JsonSerializable
{
    private ?int $contract_item_id;
    private int $contract_id;
    private int $company_id;
    private int $product_id;
    private string $uom;
    private float $qty_committed;
    private float $price;
    private string $currency;
    private ?string $min_qc_grade;
    private ?string $delivery_start;
    private ?string $delivery_end;
    private ?string $notes;

    public function __construct(?int $contract_item_id, array $data)
    {
        $this->contract_item_id = $contract_item_id;
        $this->contract_id = (int)($data['contract_id'] ?? 0);
        $this->company_id = (int)($data['company_id'] ?? 0);
        $this->product_id = (int)($data['product_id'] ?? 0);
        $this->uom = (string)($data['uom'] ?? '');
        $this->qty_committed = (float)($data['qty_committed'] ?? 0);
        $this->price = (float)($data['price'] ?? 0);
        $this->currency = (string)($data['currency'] ?? 'VND');
        $this->min_qc_grade = $data['min_qc_grade'] ?? null;
        $this->delivery_start = $data['delivery_start'] ?? null;
        $this->delivery_end = $data['delivery_end'] ?? null;
        $this->notes = $data['notes'] ?? null;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return [
            'contract_item_id' => $this->contract_item_id,
            'contract_id' => $this->contract_id,
            'company_id' => $this->company_id,
            'product_id' => $this->product_id,
            'uom' => $this->uom,
            'qty_committed' => $this->qty_committed,
            'price' => $this->price,
            'currency' => $this->currency,
            'min_qc_grade' => $this->min_qc_grade,
            'delivery_start' => $this->delivery_start,
            'delivery_end' => $this->delivery_end,
            'notes' => $this->notes,
        ];
    }
}
