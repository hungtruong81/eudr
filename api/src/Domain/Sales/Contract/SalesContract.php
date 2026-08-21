<?php

declare(strict_types=1);

namespace App\Domain\Sales\Contract;

use JsonSerializable;

class SalesContract implements JsonSerializable
{
    /**
     * @var int|null
     */
    private ?int $contract_id;
    /**
     * @var string
     */
    private string $contract_code;
    /**
     * @var int
     */
    private int $company_id;
    /**
     * @var int
     */
    private int $customer_id;
    /**
     * @var string
     */
    private string $title;
    /**
     * @var string
     */
    private string $start_date;
    /**
     * @var string|null
     */
    private ?string $end_date;
    /**
     * @var string|null
     */
    private ?string $payment_terms;
    /**
     * @var string|null
     */
    private ?string $delivery_terms;
    /**
     * @var string
     */
    private string $currency;
    /**
     * @var string
     */
    private string $status;
    /**
     * @var int
     */
    private int $version_no;
    /**
     * @var string|null
     */
    private ?string $signed_at;
    /**
     * @var int|null
     */
    private ?int $signed_by;
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
     */
    private array $items;

    /**
     * @param int|null  $contract_id
     * @param array    $data
     */
    public function __construct(?int $contract_id, array $data)
    {
        $this->contract_id = $contract_id;
        $this->contract_code = (string)($data['contract_code'] ?? '');
        $this->company_id = (int)($data['company_id'] ?? 0);
        $this->customer_id = (int)($data['customer_id'] ?? 0);
        $this->title = (string)($data['title'] ?? '');
        $this->start_date = (string)($data['start_date'] ?? '');
        $this->end_date = $data['end_date'] ?? null;
        $this->payment_terms = $data['payment_terms'] ?? null;
        $this->delivery_terms = $data['delivery_terms'] ?? null;
        $this->currency = (string)($data['currency'] ?? 'VND');
        $this->status = (string)($data['status'] ?? 'draft');
        $this->version_no = (int)($data['version_no'] ?? 1);
        $this->signed_at = $data['signed_at'] ?? null;
        $this->signed_by = isset($data['signed_by']) ? (int)$data['signed_by'] : null;
        $this->notes = $data['notes'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->created_by = isset($data['created_by']) ? (int)$data['created_by'] : null;
        $this->updated_at = $data['updated_at'] ?? null;
        $this->updated_by = isset($data['updated_by']) ? (int)$data['updated_by'] : null;
        $this->items = $data['items'] ?? [];
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->contract_id;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->contract_code;
    }
    
    /**
     * @return array<int,array<string,mixed>>
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return [
            'contract_id' => $this->contract_id,
            'contract_code' => $this->contract_code,
            'company_id' => $this->company_id,
            'customer_id' => $this->customer_id,
            'title' => $this->title,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'payment_terms' => $this->payment_terms,
            'delivery_terms' => $this->delivery_terms,
            'currency' => $this->currency,
            'status' => $this->status,
            'version_no' => $this->version_no,
            'signed_at' => $this->signed_at,
            'signed_by' => $this->signed_by,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'created_by' => $this->created_by,
            'updated_at' => $this->updated_at,
            'updated_by' => $this->updated_by,
            'items' => $this->items,
        ];
    }
}
