<?php

declare(strict_types=1);

namespace App\Domain\PurchasingSubTankIntake;

use JsonSerializable;

final class PurchasingSubTankIntake implements JsonSerializable
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
        return array_merge(['sub_tank_intake_id' => $this->id], $this->data);
    }
}
