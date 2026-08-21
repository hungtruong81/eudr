<?php

declare(strict_types=1);

namespace App\Domain\Factory;

use JsonSerializable;

class Factory implements JsonSerializable
{
    /**
     * @var int|null
    */
    private $factory_id;
    /**
     * @var string|null
     */
    private $factory_code;
    /**
     * @var string|null
     */
    private $factory_name;
    /**
     * @var string|null
     */
    private $address;
    /**
     * @var string|null
     */
    private $created_at;

    /**
     * @param int|null  $factory_id
     * @param array    $data
     */
    public function __construct(?int $factory_id, array $data)
    {
        $this->factory_id = $factory_id;
        $this->factory_code = $data['factory_code'] ?? '';
        $this->factory_name = $data['factory_name'] ?? '';
        $this->address = $data['address'] ?? '';
        $this->created_at = $data['created_at'] ?? '';
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->factory_id;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->factory_code;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->factory_name;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'factory_id' => $this->factory_id,
            'factory_code' => $this->factory_code,
            'factory_name' => $this->factory_name,
            'address' => $this->address,
            'created_at' => $this->created_at,
        ];
    }
}
