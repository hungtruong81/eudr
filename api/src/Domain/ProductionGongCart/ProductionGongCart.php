<?php

declare(strict_types=1);

namespace App\Domain\ProductionGongCart;

use JsonSerializable;

class ProductionGongCart implements JsonSerializable
{
    /**
     * @var int|null
     */
    private $gong_cart_id;
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
    private $gong_cart_code;
    /**
     * @var string|null
     */
    private $gong_cart_name;
    /**
     * @var int|null
     */
    private $max_poles;
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
     * @param int|null $gong_cart_id
     * @param array $data
     */
    public function __construct(?int $gong_cart_id, array $data)
    {
        $this->gong_cart_id = $gong_cart_id;
        $this->gong_cart_code = $data['gong_cart_code'] ?? '';
        $this->gong_cart_name = $data['gong_cart_name'] ?? '';
        $this->company_id = $data['company_id'] ?? 0;
        $this->factory_id = $data['factory_id'] ?? 0;
        $this->factory_name = $data['factory_name'] ?? '';
        $this->max_poles = $data['max_poles'] ?? 0;
        $this->status = $data['status'] ?? 'available';
        $this->created_at = $data['created_at'] ?? '';
        $this->updated_at = $data['updated_at'] ?? null;
        $this->deleted_at = $data['deleted_at'] ?? null;
    }

    public function getId(): ?int
    {
        return $this->gong_cart_id;
    }

    public function getCode(): ?string
    {
        return $this->gong_cart_code;
    }

    public function getName(): ?string
    {
        return $this->gong_cart_name;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'gong_cart_id' => $this->gong_cart_id,
            'gong_cart_code' => $this->gong_cart_code,
            'gong_cart_name' => $this->gong_cart_name,
            'company_id' => $this->company_id,
            'factory_id' => $this->factory_id,
            'factory_name' => $this->factory_name,
            'max_poles' => $this->max_poles,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
