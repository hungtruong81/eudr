<?php

declare(strict_types=1);

namespace App\Domain\Sales\Issue;

use JsonSerializable;

class SalesIssue implements JsonSerializable
{
    /**
     * @var int|null
     */
    private ?int $issue_id;
    /**
     * @var string
     */
    private string $issue_code;
    /**
     * @var int
     */
    private int $sale_order_id;
    /**
     * @var int
     */
    private int $company_id;
    /**
     * @var int|null
     */
    private ?int $warehouse_id;
    /**
     * @var string
     */
    private string $issue_date;
    /**
     * @var string
     */
    private string $status;
    /**
     * @var string|null
     */
    private ?string $document_ref;
    /**
     * @var string|null
     */
    private ?string $shipper;
    /**
     * @var string|null
     */
    private ?string $vehicle_no;
    /**
     * @var string|null
     */
    private ?string $receiver;
    /**
     * @var string|null
     */
    private ?string $reason_code;
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
    private ?string $cancelled_at;
    /**
     * @var int|null
     */
    private ?int $cancelled_by;
    /**
     * @var string|null
     */
    private ?string $deleted_at;
    /**
     * @var int|null
     */
    private ?int $deleted_by;
    /** 
     * @var array<int,array<string,mixed>> 
     */
    private array $items;

    /**
     * @param int|null  $issue_id
     * @param array    $data
     */
    public function __construct(?int $issue_id, array $data)
    {
        $this->issue_id = $issue_id;
        $this->issue_code = (string)($data['issue_code'] ?? '');
        $this->sale_order_id = (int)($data['sale_order_id'] ?? 0);
        $this->company_id = (int)($data['company_id'] ?? 0);
        $this->warehouse_id = isset($data['warehouse_id']) ? (int)$data['warehouse_id'] : null;
        $this->issue_date = (string)($data['issue_date'] ?? '');
        $this->status = (string)($data['status'] ?? 'draft');
        $this->document_ref = $data['document_ref'] ?? null;
        $this->shipper = $data['shipper'] ?? null;
        $this->vehicle_no = $data['vehicle_no'] ?? null;
        $this->receiver = $data['receiver'] ?? null;
        $this->reason_code = $data['reason_code'] ?? null;
        $this->notes = $data['notes'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->created_by = isset($data['created_by']) ? (int)$data['created_by'] : null;
        $this->updated_at = $data['updated_at'] ?? null;
        $this->updated_by = isset($data['updated_by']) ? (int)$data['updated_by'] : null;
        $this->cancelled_at = $data['cancelled_at'] ?? null;
        $this->cancelled_by = isset($data['cancelled_by']) ? (int)$data['cancelled_by'] : null;
        $this->deleted_at = $data['deleted_at'] ?? null;
        $this->deleted_by = isset($data['deleted_by']) ? (int)$data['deleted_by'] : null;
        $this->items = $data['items'] ?? [];
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->issue_id;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->issue_code;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return [
            'issue_id' => $this->issue_id,
            'issue_code' => $this->issue_code,
            'sale_order_id' => $this->sale_order_id,
            'company_id' => $this->company_id,
            'warehouse_id' => $this->warehouse_id,
            'issue_date' => $this->issue_date,
            'status' => $this->status,
            'document_ref' => $this->document_ref,
            'shipper' => $this->shipper,
            'vehicle_no' => $this->vehicle_no,
            'receiver' => $this->receiver,
            'reason_code' => $this->reason_code,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'created_by' => $this->created_by,
            'updated_at' => $this->updated_at,
            'updated_by' => $this->updated_by,
            'cancelled_at' => $this->cancelled_at,
            'cancelled_by' => $this->cancelled_by,
            'deleted_at' => $this->deleted_at,
            'deleted_by' => $this->deleted_by,
            'items' => $this->items,
        ];
    }
}
