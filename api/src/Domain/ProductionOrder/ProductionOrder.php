<?php

declare(strict_types=1);

namespace App\Domain\ProductionOrder;

use JsonSerializable;

class ProductionOrder implements JsonSerializable
{
    /**
     * @var int|null
    */
    private $production_order_id;
    /**
     * @var string|null
     */
    private $production_order_code;
    /**
     * @var string|null
     */
    private $production_order_name;
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
     * @var int|null
     */
    private $contract_id;
    /**
     * @var string|null
     */
    private $contract_code;
    /**
     * @var string|null
     */
    private $product_type_category;
    /**
     * @var int|null
     */
    private $product_type_id;
    /**
     * @var string|null
     */
    private $product_type_name;
    /**
     * @var int|null
     */
    private $required_quantity;
    /**
     * @var string|null
     */
    private $production_date;
    /**
     * @var string|null
     */
    private $status;
    /**
     * @var string|null
     */
    private $created_at;

    /**
     * @var array
     */
    private $production_product_types;

    /**
     * @param int|null  $production_order_id
     * @param array    $data
     */
    public function __construct(?int $production_order_id, array $data)
    {
        $this->production_order_id = $production_order_id;
        $this->production_order_code = $data['production_order_code'] ?? '';
        $this->production_order_name = $data['production_order_name'] ?? '';
        $this->company_id = $data['company_id'] ?? 0;
        $this->factory_id = $data['factory_id'] ?? 0;
        $this->factory_name = $data['factory_name'] ?? '';
        $this->contract_id = $data['contract_id'] ?? 0;
        $this->contract_code = $data['contract_code'] ?? '';
        $this->product_type_category = $data['product_type_category'] ?? '';
        $this->product_type_id = $data['product_type_id'] ?? null;
        $this->product_type_name = $data['product_type_name'] ?? '';
        $this->required_quantity = $data['required_quantity'] ?? null;
        $this->production_date = $data['production_date'] ?? null;
        $this->status = $data['status'] ?? '';
        $this->created_at = $data['created_at'] ?? '';
        $this->production_product_types = is_array($data['production_product_types'] ?? null)
            ? $data['production_product_types']
            : [];
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->production_order_id;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->production_order_code;
    }

    /**
     * @return string|null
     */
    public function getContractCode(): ?string
    {
        return $this->contract_code;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->production_order_name;
    }

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getFactoryId(): ?int
    {
        return (int)$this->factory_id;
    }

    /**
     * @return int|null
     */
    public function getRequiredQuantity(): ?int
    {
        return $this->required_quantity;
    }

    /**
     * @return string|null
     */
    public function getProductionDate(): ?string
    {
        return $this->production_date;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'production_order_id' => $this->production_order_id,
            'production_order_code' => $this->production_order_code,
            'production_order_name' => $this->production_order_name,
            'company_id' => $this->company_id,
            'factory_id' => $this->factory_id,
            'factory_name' => $this->factory_name,
            'contract_id' => $this->contract_id,
            'contract_code' => $this->contract_code,
            'product_type_category' => $this->product_type_category,
            'product_type_id' => $this->product_type_id,
            'product_type_name' => $this->product_type_name,
            'required_quantity' => $this->required_quantity,
            'production_date' => $this->production_date,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'production_product_types' => $this->production_product_types,
        ];
    }
}
