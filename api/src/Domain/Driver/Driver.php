<?php

declare(strict_types=1);

namespace App\Domain\Driver;

use JsonSerializable;

class Driver implements JsonSerializable
{
    /**
     * @var int|null
    */
    private $driver_id;
    /**
     * @var string|null
     */
    private $driver_code;
    /**
     * @var string|null
     */
    private $driver_name;
    /**
     * @var date
     */
    private $created_at;

    /**
     * @param int|null  $driver_id
     * @param array    $data
     */
    public function __construct(?int $driver_id, array $data)
    {
        $this->driver_id = $driver_id;
        $this->driver_code = $data['driver_code'] ?? '';
        $this->driver_name = $data['driver_name'] ?? '';
        $this->created_at = $data['created_at'] ?? '';
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->driver_id;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->driver_code;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->driver_name;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'driver_id' => $this->driver_id,
            'driver_code' => $this->driver_code,
            'driver_name' => $this->driver_name,
            'created_at' => $this->created_at,
        ];
    }
}
