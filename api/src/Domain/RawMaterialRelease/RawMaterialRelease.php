<?php

declare(strict_types=1);

namespace App\Domain\RawMaterialRelease;

use JsonSerializable;

class RawMaterialRelease implements JsonSerializable
{
    /**
     * @var int|null
    */
    private $material_release_id;
    /**
     * @var string|null
     */
    private $material_release_code;
    /**
     * @var string|null
     */
    private $material_release_name;
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
     * @var string|null
     */
    private $total_requested_weight;
    /**
     * @var array|null
     */
    private $raw_material_tanks;
    /**
     * @var string|null
     */
    private $status;
    /**
     * @var string|null
     */
    private $created_at;

    /**
     * @param int|null  $material_release_id
     * @param array    $data
     */
    public function __construct(?int $material_release_id, array $data)
    {
        $this->material_release_id = $material_release_id;
        $this->material_release_code = $data['material_release_code'] ?? '';
        $this->material_release_name = $data['material_release_name'] ?? '';
        $this->production_order_id = $data['production_order_id'] ?? 0;
        $this->production_order_code = $data['production_order_code'] ?? '';
        $this->production_order_name = $data['production_order_name'] ?? '';
        $this->total_requested_weight = $data['total_requested_weight'] ?? '';
        $this->raw_material_tanks = $data['raw_material_tanks'] ?? [];
        $this->status = $data['status'] ?? '';
        $this->status = $data['status'] ?? '';
        $this->created_at = $data['created_at'] ?? '';
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->material_release_id;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->material_release_code;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->material_release_name;
    }

    /**
     * @return string|null
     */
    public function getStatus(): ?string
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
            'material_release_id' => $this->material_release_id,
            'material_release_code' => $this->material_release_code,
            'material_release_name' => $this->material_release_name,
            'production_order_id' => $this->production_order_id,
            'production_order_code' => $this->production_order_code,
            'production_order_name' => $this->production_order_name,
            'total_requested_weight' => $this->total_requested_weight,
            'raw_material_tanks' => $this->raw_material_tanks,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
