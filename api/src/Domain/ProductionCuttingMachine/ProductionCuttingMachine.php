<?php

declare(strict_types=1);

namespace App\Domain\ProductionCuttingMachine;

use JsonSerializable;

class ProductionCuttingMachine implements JsonSerializable
{
    /**
     * @var int|null
     */
    private $cutting_machine_id;
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
    private $cutting_machine_code;
    /**
     * @var string|null
     */
    private $cutting_machine_name;
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
     * @param int|null $cutting_machine_id
     * @param array $data
     */
    public function __construct(?int $cutting_machine_id, array $data)
    {
        $this->cutting_machine_id = $cutting_machine_id;
        $this->cutting_machine_code = $data['cutting_machine_code'] ?? '';
        $this->cutting_machine_name = $data['cutting_machine_name'] ?? '';
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
        return $this->cutting_machine_id;
    }

    public function getCode(): ?string
    {
        return $this->cutting_machine_code;
    }

    public function getName(): ?string
    {
        return $this->cutting_machine_name;
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
            'cutting_machine_id' => $this->cutting_machine_id,
            'cutting_machine_code' => $this->cutting_machine_code,
            'cutting_machine_name' => $this->cutting_machine_name,
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
