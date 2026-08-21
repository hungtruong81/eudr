<?php

declare(strict_types=1);

namespace App\Domain\ProductLot;

use JsonSerializable;

class ProductLot implements JsonSerializable
{
    /**
     * @var int|null
     */
    private $product_lot_id;
    /**
     * @var string|null
     */
    private $product_lot_code;
    /**
     * @var string
     */
    private string $lot_type;
    /**
     * @var string
     */
    private string $eudr_type;
    /**
     * @var string|null
     */
    private $grade;
    /**
     * @var int|null
     */
    private $factory_id;
    /**
     * @var int
     */
    private int $owner_company_id;
    /**
     * @var int
     */
    private int $owner_id;
    /**
     * @var string|null
     */
    private $production_date_from;
    /**
     * @var string|null
     */
    private $production_date_to;
    /**
     * @var int
     */
    private $total_blocks;
    /**
     * @var string
     */
    private $total_weight;
    /**
     * @var string|null
     */
    private $status;
    /**
     * @var string|null
     */
    private $confirmed_at;
    /**
     * @var string|null
     */
    private $factory_name;
    /**
     * @var string|null
     */
    private $factory_code;
    /**
     * @var string
     */
    private string $supplier_company_name;
    /**
     * @var string
     */
    private string $supplier_factory_name;
    /**
     * @var string
     */
    private string $supplier_phone;
    /**
     * @var string|null
     */
    private ?string $supplier_address;
    /**
     * @var string
     */
    private string $original_product_lot_code;
    /**
     * @var string
     */
    private string $external_contract_code;
    /**
     * @var string|null
     */
    private ?string $purchase_date;
    /**
     * @var float
     */
    private float $purchase_amount;
    /**
     * @var string|null
     */
    private ?string $notes;
    /**
     * @var int
     */
    private int $created_by;
    /**
     * @var int
     */
    private int $updated_by;
    /**
     * @var array
     */
    private array $lands;
    /**
     * @var array|null
     */
    private ?array $transport;
    /**
     * @var array
     */
    private array $non_eudr_items;
    /**
     * @var array
     */
    private array $attachments;

    /**
     * @param int|null $product_lot_id
     * @param array $data
     */
    public function __construct(?int $product_lot_id, array $data)
    {
        $this->product_lot_id = $product_lot_id;
        $this->product_lot_code = $data['product_lot_code'] ?? '';
        $this->lot_type = $data['lot_type'] ?? 'internal';
        $this->eudr_type = $data['eudr_type'] ?? 'eudr';
        $this->grade = $data['grade'] ?? '';
        $this->factory_id = $data['factory_id'] ?? 0;
        $this->owner_company_id = (int)($data['owner_company_id'] ?? 0);
        $this->owner_id = (int)($data['owner_id'] ?? 0);
        $this->production_date_from = $data['production_date_from'] ?? null;
        $this->production_date_to = $data['production_date_to'] ?? null;
        $this->total_blocks = $data['total_blocks'] ?? 0;
        $this->total_weight = $data['total_weight'] ?? '0.00';
        $this->status = $data['status'] ?? 'draft';
        $this->confirmed_at = $data['confirmed_at'] ?? null;
        $this->factory_name = $data['factory_name'] ?? null;
        $this->factory_code = $data['factory_code'] ?? null;
        $this->supplier_company_name = $data['supplier_company_name'] ?? '';
        $this->supplier_factory_name = $data['supplier_factory_name'] ?? '';
        $this->supplier_phone = $data['supplier_phone'] ?? '';
        $this->supplier_address = $data['supplier_address'] ?? null;
        $this->original_product_lot_code = $data['original_product_lot_code'] ?? '';
        $this->external_contract_code = $data['external_contract_code'] ?? '';
        $this->purchase_date = $data['purchase_date'] ?? null;
        $this->purchase_amount = (float)($data['purchase_amount'] ?? 0);
        $this->notes = $data['notes'] ?? null;
        $this->created_by = (int)($data['created_by'] ?? 0);
        $this->updated_by = (int)($data['updated_by'] ?? 0);
        $this->lands = $data['lands'] ?? [];
        $this->transport = $data['transport'] ?? null;
        $this->non_eudr_items = $data['non_eudr_items'] ?? [];
        $this->attachments = $data['attachments'] ?? [];
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->product_lot_id;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->product_lot_code;
    }

    /**
     * @return string
     */
    public function getLotType(): string
    {
        return $this->lot_type;
    }

    /**
     * @return string
     */
    public function getEudrType(): string
    {
        return $this->eudr_type;
    }

    /**
     * @return string|null
     */
    public function getGrade(): ?string
    {
        return $this->grade;
    }

    /**
     * @return int|null
     */
    public function getFactoryId(): ?int
    {
        return $this->factory_id;
    }

    /**
     * @return int
     */
    public function getOwnerCompanyId(): int
    {
        return $this->owner_company_id;
    }

    /**
     * @return int
     */
    public function getOwnerId(): int
    {
        return $this->owner_id;
    }

    /**
     * @return int
     */
    public function getTotalBlocks(): int
    {
        return $this->total_blocks;
    }

    /**
     * @return float
     */
    public function getTotalWeight(): float
    {
        return (float)$this->total_weight;
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
        $data = [
            'product_lot_id' => $this->product_lot_id,
            'product_lot_code' => $this->product_lot_code,
            'lot_type' => $this->lot_type,
            'grade' => $this->grade,
            'factory_id' => $this->factory_id,
            'owner_company_id' => $this->owner_company_id,
            'owner_id' => $this->owner_id,
            'factory_name' => $this->factory_name,
            'factory_code' => $this->factory_code,
            'production_date_from' => $this->production_date_from,
            'production_date_to' => $this->production_date_to,
            'total_blocks' => $this->total_blocks,
            'total_weight' => $this->total_weight,
            'status' => $this->status,
            'confirmed_at' => $this->confirmed_at,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
        ];

        if ($this->lot_type === 'external') {
            $data['eudr_type'] = $this->eudr_type;
            $data['supplier_company_name'] = $this->supplier_company_name;
            $data['supplier_factory_name'] = $this->supplier_factory_name;
            $data['supplier_phone'] = $this->supplier_phone;
            $data['supplier_address'] = $this->supplier_address;
            $data['original_product_lot_code'] = $this->original_product_lot_code;
            $data['external_contract_code'] = $this->external_contract_code;
            $data['purchase_date'] = $this->purchase_date;
            $data['purchase_amount'] = $this->purchase_amount;
            $data['notes'] = $this->notes;

            if ($this->eudr_type === 'eudr') {
                $data['lands'] = $this->lands;
                $data['transport'] = $this->transport;
            } else {
                // non_eudr
                $data['non_eudr_items'] = $this->non_eudr_items;
                $data['attachments'] = $this->attachments;
            }
        }

        return $data;
    }
}
