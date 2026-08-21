<?php

declare(strict_types=1);

namespace App\Domain\CompanyGroup;

use JsonSerializable;

class CompanyGroup implements JsonSerializable
{
    /**
     * @var int|null
    */
    private ?int $company_group_id;
    /**
     * @var string
     */
    private string $company_group_code;
    /**
     * @var int
     */
    private int $company_id;
    /**
     * @var string
     */
    private string $company_code;
    /**
     * @var string
     */
    private string $company_name;
    /**
     * @var string
     */
    private string $company_short_name;
    /**
     * @var string
     */
    private string $name;
    /**
     * @var string|null
     */
    private ?string $description;
    /**
     * @var bool
     */
    private bool $is_default;
    /**
     * @var string
     */
    private string $status;
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
    private ?string $deleted_at;
    /**
     * @var int|null
     */
    private ?int $deleted_by;
    /**
     * @var int|null
     */
    private ?int $member_count;
    /**
     * @param int|null  $company_group_id
     * @param array    $data
     */
    public function __construct(?int $company_group_id, array $data)
    {
        $this->company_group_id   = $company_group_id;
        $this->company_group_code = (string)($data['company_group_code'] ?? '');
        $this->company_id = (int)($data['company_id'] ?? 0);
        $this->company_code = (string)($data['company_code'] ?? '');
        $this->company_name = (string)($data['company_name'] ?? '');
        $this->company_short_name = (string)($data['short_name'] ?? '');
        $this->name       = (string)($data['name'] ?? '');
        $this->description = $data['description'] ?? null;
        $this->is_default = !empty($data['is_default']);
        $this->status     = (string)($data['status'] ?? 'active');
        $this->created_at = $data['created_at'] ?? null;
        $this->created_by = isset($data['created_by']) ? (int)$data['created_by'] : 0;
        $this->updated_at = $data['updated_at'] ?? null;
        $this->updated_by = isset($data['updated_by']) ? (int)$data['updated_by'] : 0;
        $this->deleted_at = $data['deleted_at'] ?? null;
        $this->deleted_by = isset($data['deleted_by']) ? (int)$data['deleted_by'] : 0;
        $this->member_count = isset($data['member_count']) ? (int)$data['member_count'] : 0;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->company_group_id;
    }
    /**
     * @return int|null
     */
    public function getCompanyId(): int
    {
        return $this->company_id;
    }

    /**
     * @return string|null
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getCompanyCode(): string
    {
        return $this->company_code;
    }

    /**
     * @return string
     */
    public function getCompanyName(): string
    {
        return $this->company_name;
    }

    /**
     * @return string
     */
    public function getCompanyShortName(): string
    {
        return $this->company_short_name;
    }
    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }
    /**
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->is_default;
    }
    /**
     * @return string
     */
    public function getStatus(): string
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
    public function jsonSerialize(): array
    {
        return [
            'company_group_id'    => $this->company_group_id,
            'company_group_code'  => $this->company_group_code,
            'company_id'  => $this->company_id,
            'company_code' => $this->company_code,
            'company_name' => $this->company_name,
            'company_short_name' => $this->company_short_name,
            'name'        => $this->name,
            'description' => $this->description,
            'is_default'  => $this->is_default,
            'status'      => $this->status,
            'created_at'  => $this->created_at,
            'created_by'  => $this->created_by,
            'updated_at'  => $this->updated_at,
            'updated_by'  => $this->updated_by,
            'deleted_at'  => $this->deleted_at,
            'deleted_by'  => $this->deleted_by,
            'member_count'  => $this->member_count,
        ];
    }
}
