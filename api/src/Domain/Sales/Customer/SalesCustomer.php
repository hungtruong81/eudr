<?php

declare(strict_types=1);

namespace App\Domain\Sales\Customer;

use JsonSerializable;

class SalesCustomer implements JsonSerializable
{
    /**
     * @var int
    */
    private ?int $customer_id;
    /**
     * @var string
     */
    private string $customer_code;
    /**
     * @var int
     */
    private int $company_id;
    /**
     * @var string|null
     */
    private ?string $customer_company_name;
    /**
     * @var string
     */
    private string $customer_name;
    /**
     * @var string|null
     */
    private ?string $tax_code;
    /**
     * @var string|null
     */
    private ?string $customer_email;
    /**
     * @var string|null
     */
    private ?string $customer_phone;
    /**
     * @var array<int>
     */
    private array $business_license_file_ids;
    /**
     * @var array<int,string>
     */
    private array $business_license_file_urls;
    /**
     * @var string|null
     */
    private ?string $billing_address;
    /**
     * @var string|null
     */
    private ?string $shipping_address;
    /**
     * @var string|null
     */
    private ?string $customer_type;
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
     * @param int|null  $customer_id
     * @param array    $data
     */
    public function __construct(?int $customer_id, array $data)
    {
        $this->customer_id = $customer_id;
        $this->customer_code = (string)($data['customer_code'] ?? '');
        $this->company_id = (int)($data['company_id'] ?? 0);
        $this->customer_company_name = $data['customer_company_name'] ?? null;
        $this->customer_name = (string)($data['customer_name'] ?? '');
        $this->tax_code = $data['tax_code'] ?? null;
        $this->customer_email = $data['customer_email'] ?? null;
        $this->customer_phone = $data['customer_phone'] ?? null;
        $this->business_license_file_ids = is_array($data['business_license_file_ids'] ?? null)
            ? array_values(array_map('intval', $data['business_license_file_ids']))
            : [];
        $this->business_license_file_urls = is_array($data['business_license_file_urls'] ?? null)
            ? array_values(array_filter($data['business_license_file_urls']))
            : [];
        $this->billing_address = $data['billing_address'] ?? null;
        $this->shipping_address = $data['shipping_address'] ?? null;
        $this->customer_type = $data['customer_type'] ?? null;
        $this->status = (string)($data['status'] ?? 'active');
        $this->notes = $data['notes'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->created_by = isset($data['created_by']) ? (int)$data['created_by'] : null;
        $this->updated_at = $data['updated_at'] ?? null;
        $this->updated_by = isset($data['updated_by']) ? (int)$data['updated_by'] : null;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->customer_id;
    }

    /**
     * @return int
     */
    public function getCompanyId(): int
    {
        return $this->company_id;
    }

    /**
     * @return string|null
     */
    public function getCustomerCompanyName(): ?string
    {
        return $this->customer_company_name;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->customer_code;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return [
            'customer_id' => $this->customer_id,
            'customer_code' => $this->customer_code,
            'customer_name' => $this->customer_name,
            'customer_company_name' => $this->customer_company_name,
            'customer_email' => $this->customer_email,
            'customer_phone' => $this->customer_phone,
            'company_id' => $this->company_id,
            'business_license_file_ids' => $this->business_license_file_ids,
            'business_license_file_urls' => $this->business_license_file_urls,
            'tax_code' => $this->tax_code,
            'billing_address' => $this->billing_address,
            'shipping_address' => $this->shipping_address,
            'customer_type' => $this->customer_type,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'created_by' => $this->created_by,
            'updated_at' => $this->updated_at,
            'updated_by' => $this->updated_by,
        ];
    }
}
