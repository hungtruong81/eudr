<?php

declare(strict_types=1);

namespace App\Domain\ProductionOven;

use JsonSerializable;

class ProductionOven implements JsonSerializable
{
    /**
     * @var int|null
     */
    private $oven_id;
    /**
     * @var int|null
     */
    private $company_id;
    /**
     * @var int|null
     */
    private $factory_id;
    /**
    * @var string|null
    */
    private $factory_name;
    /**
     * @var string|null
     */
    private $oven_code;
    /**
     * @var string|null
     */
    private $oven_name;
    /**
     * @var string|null
     */
    private $status;
    /**
     * @var string|null
     */
    private $created_at;
    /**
     * @var string|null
     */
    private $updated_at;
    /**
     * @var string|null
     */
    private $deleted_at;

    /**
     * @param int|null $oven_id
     * @param array $data
     */
    public function __construct(?int $oven_id, array $data)
    {
        $this->oven_id = $oven_id;
        $this->oven_code = $data['oven_code'] ?? '';
        $this->oven_name = $data['oven_name'] ?? '';
        $this->company_id = $data['company_id'] ?? 0;
        $this->factory_id = $data['factory_id'] ?? 0;
        $this->factory_name = $data['factory_name'] ?? '';
        $this->status = $data['status'] ?? 'available';
        $this->created_at = $data['created_at'] ?? '';
        $this->updated_at = $data['updated_at'] ?? null;
        $this->deleted_at = $data['deleted_at'] ?? null;
    }

    public function getId(): ?int
    {
        return $this->oven_id;
    }

    public function getCode(): ?string
    {
        return $this->oven_code;
    }

    public function getName(): ?string
    {
        return $this->oven_name;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'oven_id' => $this->oven_id,
            'oven_code' => $this->oven_code,
            'oven_name' => $this->oven_name,
            'company_id' => $this->company_id,
            'factory_id' => $this->factory_id,
            'factory_name' => $this->factory_name,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
