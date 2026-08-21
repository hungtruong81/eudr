<?php

declare(strict_types=1);

namespace App\Domain\Vendor;

use JsonSerializable;

class Vendor implements JsonSerializable
{
    /**
     * @var int|null
     */
    private ?int $vendor_id;
    /**
     * @var string
     */
    private string $vendor_code;
    /**
     * @var int
     */
    private int $company_id;
    /**
     * @var string
     */
    private string $vendor_name;
    /**
     * @var string
     */
    private string $vendor_type;
    /**
     * @var string|null
     */
    private ?string $identity_number;
    /**
     * @var string|null
     */
    private ?string $contact_name;
    /**
     * @var string|null
     */
    private ?string $contact_phone;
    /**
     * @var string|null
     */
    private ?string $tax_code;
    /**
     * @var string|null
     */
    private ?string $address;
    /**
     * @var int
     */
    private int $province_id;
    /**
     * @var string
     */
    private string $status;
    /**
     * @var string|null
     */
    private ?string $notes;
    /**
     * @var string|null
     */
    private ?string $created_at;

    /**
     * @param int|null $vendor_id
     * @param array $data
     */
    public function __construct(?int $vendor_id, array $data)
    {
        $this->vendor_id = $vendor_id;
        $this->vendor_code = (string)($data['vendor_code'] ?? '');
        $this->company_id = (int)($data['company_id'] ?? 0);
        $this->vendor_name = (string)($data['vendor_name'] ?? '');
        $this->vendor_type = (string)($data['vendor_type'] ?? 'company');
        $this->identity_number = isset($data['identity_number']) ? (string)$data['identity_number'] : null;
        $this->contact_name = isset($data['contact_name']) ? (string)$data['contact_name'] : null;
        $this->contact_phone = isset($data['contact_phone']) ? (string)$data['contact_phone'] : null;
        $this->tax_code = isset($data['tax_code']) ? (string)$data['tax_code'] : null;
        $this->address = isset($data['address']) ? (string)$data['address'] : null;
        $this->province_id = (int)($data['province_id'] ?? 0);
        $this->status = (string)($data['status'] ?? 'active');
        $this->notes = isset($data['notes']) ? (string)$data['notes'] : null;
        $this->created_at = isset($data['created_at']) ? (string)$data['created_at'] : null;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->vendor_id;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->vendor_code;
    }

    public function getName(): string
    {
        return $this->vendor_name;
    }

    public function getType(): string
    {
        return $this->vendor_type;
    }

    public function getContactPhone(): ?string
    {
        return $this->contact_phone;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'vendor_id' => $this->vendor_id,
            'vendor_code' => $this->vendor_code,
            'company_id' => $this->company_id,
            'vendor_name' => $this->vendor_name,
            'vendor_type' => $this->vendor_type,
            'identity_number' => $this->identity_number,
            'contact_name' => $this->contact_name,
            'contact_phone' => $this->contact_phone,
            'tax_code' => $this->tax_code,
            'address' => $this->address,
            'province_id' => $this->province_id,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
