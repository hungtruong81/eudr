<?php

declare(strict_types=1);

namespace App\Domain\PurchasingTransport;

use JsonSerializable;

final class PurchasingTransport implements JsonSerializable
{
    /**
     * @param int $id
     * @param array $data
     */
    public function __construct(private int $id, private array $data) {}

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return ['purchase_transport_id' => $this->id] + $this->data;
    }
}
