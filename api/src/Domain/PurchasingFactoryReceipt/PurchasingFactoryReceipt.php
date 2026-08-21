<?php

declare(strict_types=1);

namespace App\Domain\PurchasingFactoryReceipt;

use JsonSerializable;

final class PurchasingFactoryReceipt implements JsonSerializable
{
    /**
     * @param int $id
     * @param array<string, mixed> $data
     */
    public function __construct(private int $id, private array $data) {}

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return ['factory_receipt_id' => $this->id] + $this->data;
    }
}
