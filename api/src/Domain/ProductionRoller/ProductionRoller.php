<?php

declare(strict_types=1);

namespace App\Domain\ProductionRoller;

use JsonSerializable;

class ProductionRoller implements JsonSerializable
{
    /**
     * @var int|null
     */
    private $roller_id;
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
    private $roller_code;
    /**
     * @var string|null
     */
    private $roller_name;
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
     * @param int|null $roller_id
     * @param array $data
     */
    public function __construct(?int $roller_id, array $data)
    {
        $this->roller_id = $roller_id;
        $this->roller_code = $data['roller_code'] ?? '';
        $this->roller_name = $data['roller_name'] ?? '';
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
        return $this->roller_id;
    }

    public function getCode(): ?string
    {
        return $this->roller_code;
    }

    public function getName(): ?string
    {
        return $this->roller_name;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'roller_id' => $this->roller_id,
            'roller_code' => $this->roller_code,
            'roller_name' => $this->roller_name,
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
