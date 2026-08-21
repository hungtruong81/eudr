<?php

declare(strict_types=1);

namespace App\Domain\Sales\Order;

use JsonSerializable;

class SalesOrder implements JsonSerializable
{
    /**
     * @var int|null
     */
    private ?int $sale_order_id;
    /**
     * @var string
     */
    private string $sale_order_code;
    /**
     * @var int
     */
    private int $company_id;
    /**
     * @var int
     */
    private int $customer_id;
    /**
     * @var int
     */
    private int $buyer_company_id;
    /**
     * @var int
     */
    private int $buyer_user_id;
    /**
     * @var int|null
     */
    private ?int $contract_id;
    /**
     * @var int|null
     */
    private ?int $quotation_id;
    /**
     * @var string
     */
    private string $order_date;
    /**
     * @var string|null
     */
    private ?string $delivery_date;
    /**
     * @var string
     */
    private string $order_source_type;
    /**
     * @var string|null
     */
    private ?string $payment_terms;
    /**
     * @var string|null
     */
    private ?string $delivery_address;
    /**
     * @var string
     */
    private string $currency;
    /**
     * @var string
     */
    private string $status;
    /**
     * @var float
     */
    private float $total_amount;
    /**
     * @var string|null
     */
    private ?string $notes;
    /**
     * @var string|null
     */
    private ?string $created_at;
    /**
     * @var int|null
     */
    private ?int $created_by;
    /**
     * @var string|null
     */
    private ?string $updated_at;
    /**
     * @var int|null
     */
    private ?int $updated_by;
    /**
     * @var string|null
     */
    private ?string $customer_code;
    /**
     * @var string|null
     */
    private ?string $customer_name;
    /**
     * @var string|null
     */
    private ?string $customer_phone;
    /**
     * @var string|null
     */
    private ?string $customer_email;
    /**
     * @var string|null
     */
    private ?string $customer_company_name;
    /**
     * @var string|null
     */
    private ?string $tax_code;
    /**
     * @var string|null
     */
    private ?string $customer_type;
    /**
     * @var string|null
     */
    private ?string $buyer_company_name;
    /**
     * @var string|null
     */
    private ?string $buyer_company_code;
    /**
     * @var string|null
     */
    private ?string $buyer_user_name;
    /** 
     * @var array<int,array<string,mixed>> 
     */
    private array $items;

    /**
     * @param int|null  $sale_order_id
     * @param array    $data
     */
    public function __construct(?int $sale_order_id, array $data)
    {
        $this->sale_order_id = $sale_order_id;
        $this->sale_order_code = (string)($data['sale_order_code'] ?? '');
        $this->company_id = (int)($data['company_id'] ?? 0);
        $this->customer_id = (int)($data['customer_id'] ?? 0);
        $this->buyer_company_id = (int)($data['buyer_company_id'] ?? 0);
        $this->buyer_user_id = (int)($data['buyer_user_id'] ?? 0);
        $this->contract_id = isset($data['contract_id']) ? (int)$data['contract_id'] : 0;
        $this->quotation_id = isset($data['quotation_id']) ? (int)$data['quotation_id'] : 0;
        $this->order_date = (string)($data['order_date'] ?? '');
        $this->delivery_date = $data['delivery_date'] ?? null;
        $this->order_source_type = (string)($data['order_source_type'] ?? 'warehouse');
        $this->payment_terms = $data['payment_terms'] ?? null;
        $this->delivery_address = $data['delivery_address'] ?? null;
        $this->currency = (string)($data['currency'] ?? 'VND');
        $this->status = (string)($data['status'] ?? 'draft');
        $this->total_amount = isset($data['total_amount']) ? (float)$data['total_amount'] : 0.0;
        $this->notes = $data['notes'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->created_by = isset($data['created_by']) ? (int)$data['created_by'] : null;
        $this->updated_at = $data['updated_at'] ?? null;
        $this->updated_by = isset($data['updated_by']) ? (int)$data['updated_by'] : null;
        $this->customer_code = $data['customer_code'] ?? null;
        $this->customer_name = $data['customer_name'] ?? null;
        $this->customer_phone = $data['customer_phone'] ?? null;
        $this->customer_email = $data['customer_email'] ?? null;
        $this->customer_company_name = $data['customer_company_name'] ?? null;
        $this->tax_code = $data['tax_code'] ?? null;
        $this->customer_type = $data['customer_type'] ?? null;
        $this->buyer_company_name = $data['buyer_company_name'] ?? null;
        $this->buyer_company_code = $data['buyer_company_code'] ?? null;
        $this->buyer_user_name = $data['buyer_user_name'] ?? null;
        $this->items = $data['items'] ?? [];
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->sale_order_id;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->sale_order_code;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return int
     */
    public function getCompanyId(): int
    {
        return $this->company_id;
    }

    /**
     * @return int
     */
    public function getBuyerCompanyId(): int
    {
        return $this->buyer_company_id;
    }

    /**
     * @return int
     */
    public function getBuyerUserId(): int
    {
        return $this->buyer_user_id;
    }
    
    /**
     * @return array<int,array<string,mixed>>
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return [
            'sale_order_id' => $this->sale_order_id,
            'sale_order_code' => $this->sale_order_code,
            'company_id' => $this->company_id,
            'customer_id' => $this->customer_id,
            'buyer_company_id' => $this->buyer_company_id,
            'buyer_user_id' => $this->buyer_user_id,
            'contract_id' => $this->contract_id,
            'quotation_id' => $this->quotation_id,
            'order_date' => $this->order_date,
            'delivery_date' => $this->delivery_date,
            'order_source_type' => $this->order_source_type,
            'payment_terms' => $this->payment_terms,
            'delivery_address' => $this->delivery_address,
            'currency' => $this->currency,
            'status' => $this->status,
            'total_amount' => $this->total_amount,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'created_by' => $this->created_by,
            'updated_at' => $this->updated_at,
            'updated_by' => $this->updated_by,
            'customer_code' => $this->customer_code,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer_email' => $this->customer_email,
            'customer_company_name' => $this->customer_company_name,
            'tax_code' => $this->tax_code,
            'customer_type' => $this->customer_type,
            'buyer_company_name' => $this->buyer_company_name,
            'buyer_company_code' => $this->buyer_company_code,
            'buyer_user_name' => $this->buyer_user_name,
            'items' => $this->items,
        ];
    }
}
