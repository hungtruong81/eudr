<?php

declare(strict_types=1);

namespace App\Domain\ProductionChannel;

use JsonSerializable;

class ProductionChannel implements JsonSerializable
{
    /**
     * @var int|null
     */
    private $channel_id;
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
    private $channel_code;
    /**
     * @var string|null
     */
    private $channel_name;
    /**
     * @var float|null
     */
    private $capacity_kg;
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
     * @param int|null $channel_id
     * @param array $data
     */
    public function __construct(?int $channel_id, array $data)
    {
        $this->channel_id = $channel_id;
        $this->channel_code = $data['channel_code'] ?? '';
        $this->channel_name = $data['channel_name'] ?? '';
        $this->company_id = $data['company_id'] ?? 0;
        $this->factory_id = $data['factory_id'] ?? 0;
        $this->factory_name = $data['factory_name'] ?? '';
        $this->capacity_kg = isset($data['capacity_kg']) ? (float)$data['capacity_kg'] : 0.0;
        $this->status = $data['status'] ?? 'available';
        $this->created_at = $data['created_at'] ?? '';
        $this->updated_at = $data['updated_at'] ?? null;
        $this->deleted_at = $data['deleted_at'] ?? null;
    }

    public function getId(): ?int
    {
        return $this->channel_id;
    }

    public function getCode(): ?string
    {
        return $this->channel_code;
    }

    public function getName(): ?string
    {
        return $this->channel_name;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getFactoryName(): ?string
    {
        return $this->factory_name;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'channel_id' => $this->channel_id,
            'channel_code' => $this->channel_code,
            'channel_name' => $this->channel_name,
            'company_id' => $this->company_id,
            'factory_id' => $this->factory_id,
            'factory_name' => $this->factory_name,
            'capacity_kg' => $this->capacity_kg,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
