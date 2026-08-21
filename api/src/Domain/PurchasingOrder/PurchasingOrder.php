<?php

declare(strict_types=1);

namespace App\Domain\PurchasingOrder;

use JsonSerializable;

class PurchasingOrder implements JsonSerializable
{
    /**
     * @var int|null
     */
    private ?int $purchase_order_id;
    /**
     * @var string
     */
    private string $purchase_order_code;
    /**
     * @var int
     */
    private int $company_id;
    /**
     * @var int
     */
    private int $buyer_user_id;
    /**
     * @var int
     */
    private int $buyer_company_id;
    /**
     * @var string
     */
    private string $buyer_name;
    /**
     * @var string|null
     */
    private ?string $buyer_phone;
    /**
     * @var string|null
     */
    private ?string $buyer_address;
    /**
     * @var string
     */
    private string $seller_source_type;
    /**
     * @var int|null
     */
    private ?int $seller_user_id;
    /**
     * @var int|null
     */
    private ?int $seller_vendor_id;
    /**
     * @var int
     */
    private int $seller_company_id;
    /**
     * @var string
     */
    private string $seller_name;
    /**
     * @var string|null
     */
    private ?string $seller_phone;
    /**
     * @var string|null
     */
    private ?string $seller_address;
    /**
     * @var string
     */
    private string $seller_account_type;
    /**
     * @var string|null
     */
    private ?string $purchase_date;
    /**
     * @var string|null
     */
    private ?string $expected_delivery_at;
    /**
     * @var string
     */
    private string $currency;
    /**
     * @var float
     */
    private float $total_quantity;
    /**
     * @var float
     */
    private float $total_weight_kg;
    /**
     * @var float
     */
    private float $total_estimated_amount;
    /**
     * @var string
     */
    private string $status;
    /**
     * @var string|null
     */
    private ?string $seller_confirmed_at;
    /**
     * @var string|null
     */
    private ?string $buyer_reconfirmed_at;
    /**
     * @var string|null
     */
    private ?string $closed_at;
    /**
     * @var string|null
     */
    private ?string $cancelled_at;
    /**
     * @var string|null
     */
    private ?string $cancel_reason;
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
     * @var array<int,array<string,mixed>> 
     * 
     */
    private array $items;

    /**
     * @param int|null $purchase_order_id
     * @param array<string,mixed> $data
     */
    public function __construct(?int $purchase_order_id, array $data)
    {
        $this->purchase_order_id = $purchase_order_id;
        $this->purchase_order_code = (string)($data['purchase_order_code'] ?? '');
        $this->company_id = (int)($data['company_id'] ?? 0);
        $this->buyer_user_id = (int)($data['buyer_user_id'] ?? 0);
        $this->buyer_company_id = (int)($data['buyer_company_id'] ?? 0);
        $this->buyer_name = (string)($data['buyer_name'] ?? '');
        $this->buyer_phone = isset($data['buyer_phone']) ? (string)$data['buyer_phone'] : null;
        $this->buyer_address = isset($data['buyer_address']) ? (string)$data['buyer_address'] : null;
        $this->seller_source_type = (string)($data['seller_source_type'] ?? 'system_user');
        $this->seller_user_id = isset($data['seller_user_id']) ? (int)$data['seller_user_id'] : null;
        $this->seller_vendor_id = isset($data['seller_vendor_id']) ? (int)$data['seller_vendor_id'] : null;
        $this->seller_company_id = (int)($data['seller_company_id'] ?? 0);
        $this->seller_name = (string)($data['seller_name'] ?? '');
        $this->seller_phone = isset($data['seller_phone']) ? (string)$data['seller_phone'] : null;
        $this->seller_address = isset($data['seller_address']) ? (string)$data['seller_address'] : null;
        $this->seller_account_type = (string)($data['seller_account_type'] ?? 'farmer');
        $this->purchase_date = isset($data['purchase_date']) ? (string)$data['purchase_date'] : null;
        $this->expected_delivery_at = isset($data['expected_delivery_at']) ? (string)$data['expected_delivery_at'] : null;
        $this->currency = (string)($data['currency'] ?? 'VND');
        $this->total_quantity = isset($data['total_quantity']) ? (float)$data['total_quantity'] : 0.0;
        $this->total_weight_kg = isset($data['total_weight_kg']) ? (float)$data['total_weight_kg'] : 0.0;
        $this->total_estimated_amount = isset($data['total_estimated_amount']) ? (float)$data['total_estimated_amount'] : 0.0;
        $this->status = (string)($data['status'] ?? 'draft');
        $this->seller_confirmed_at = isset($data['seller_confirmed_at']) ? (string)$data['seller_confirmed_at'] : null;
        $this->buyer_reconfirmed_at = isset($data['buyer_reconfirmed_at']) ? (string)$data['buyer_reconfirmed_at'] : null;
        $this->closed_at = isset($data['closed_at']) ? (string)$data['closed_at'] : null;
        $this->cancelled_at = isset($data['cancelled_at']) ? (string)$data['cancelled_at'] : null;
        $this->cancel_reason = isset($data['cancel_reason']) ? (string)$data['cancel_reason'] : null;
        $this->notes = isset($data['notes']) ? (string)$data['notes'] : null;
        $this->created_at = isset($data['created_at']) ? (string)$data['created_at'] : null;
        $this->created_by = isset($data['created_by']) ? (int)$data['created_by'] : null;
        $this->updated_at = isset($data['updated_at']) ? (string)$data['updated_at'] : null;
        $this->updated_by = isset($data['updated_by']) ? (int)$data['updated_by'] : null;
        $this->items = is_array($data['items'] ?? null) ? $data['items'] : [];
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->purchase_order_id;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return [
            'purchase_order_id' => $this->purchase_order_id,
            'purchase_order_code' => $this->purchase_order_code,
            'company_id' => $this->company_id,
            'buyer_user_id' => $this->buyer_user_id,
            'buyer_company_id' => $this->buyer_company_id,
            'buyer_name' => $this->buyer_name,
            'buyer_phone' => $this->buyer_phone,
            'buyer_address' => $this->buyer_address,
            'seller_source_type' => $this->seller_source_type,
            'seller_user_id' => $this->seller_user_id,
            'seller_vendor_id' => $this->seller_vendor_id,
            'seller_company_id' => $this->seller_company_id,
            'seller_name' => $this->seller_name,
            'seller_phone' => $this->seller_phone,
            'seller_address' => $this->seller_address,
            'seller_account_type' => $this->seller_account_type,
            'purchase_date' => $this->purchase_date,
            'expected_delivery_at' => $this->expected_delivery_at,
            'currency' => $this->currency,
            'total_quantity' => $this->total_quantity,
            'total_weight_kg' => $this->total_weight_kg,
            'total_estimated_amount' => $this->total_estimated_amount,
            'status' => $this->status,
            'seller_confirmed_at' => $this->seller_confirmed_at,
            'buyer_reconfirmed_at' => $this->buyer_reconfirmed_at,
            'closed_at' => $this->closed_at,
            'cancelled_at' => $this->cancelled_at,
            'cancel_reason' => $this->cancel_reason,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'created_by' => $this->created_by,
            'updated_at' => $this->updated_at,
            'updated_by' => $this->updated_by,
            'items' => $this->items,
        ];
    }
}
