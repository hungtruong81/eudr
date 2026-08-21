<?php

declare(strict_types=1);

namespace App\Domain\Company;

use JsonSerializable;

class Company implements JsonSerializable
{
    /**
     * @var int|null
    */
    private $company_id;
    /**
     * @var string|null
     */
    private $company_code;
    /**
     * @var string|null
     */
    private $company_name;
    /**
     * @var string|null
     */
    private $short_name;
    /**
     * @var string|null
     */
    private $tax_code;
    /**
     * @var int|null
     */
    private $address;
    /**
     * @var string|null
     */
    private $website;
    /**
     * @var string|null
     */
    private $status;
    /**
     * @var string|null
     */
    private $created_at;
    /**
     * @var int|null
     */
    private $member_count;

    /**
     * @param int|null  $company_id
     * @param array    $data
     */
    public function __construct(?int $company_id, array $data)
    {
        $this->company_id = $company_id;
        $this->company_code = $data['company_code'] ?? '';
        $this->company_name = $data['company_name'] ?? '';
        $this->short_name = $data['short_name'] ?? '';
        $this->tax_code = $data['tax_code'] ?? '';
        $this->address = $data['address'] ?? '';
        $this->website = $data['website'] ?? '';
        $this->status = $data['status'] ?? '';
        $this->created_at = $data['created_at'] ?? '';
        $this->member_count = isset($data['member_count']) ? (int)$data['member_count'] : 0;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->company_id;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->company_code;
    }

    /**
     * @return string|null
     */
    public function getShortName(): ?string
    {
        return $this->short_name;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->company_name;
    }

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @return int|null
     */
    public function getMemberCount(): ?int
    {
        return $this->member_count;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'company_id' => $this->company_id,
            'company_code' => $this->company_code,
            'company_name' => $this->company_name,
            'short_name' => $this->short_name,
            'tax_code' => $this->tax_code,
            'address' => $this->address,
            'website' => $this->website,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'member_count' => $this->member_count,
        ];
    }
}
