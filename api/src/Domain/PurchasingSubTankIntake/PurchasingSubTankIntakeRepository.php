<?php

declare(strict_types=1);

namespace App\Domain\PurchasingSubTankIntake;

interface PurchasingSubTankIntakeRepository
{
    /**
     * @param array $data
     * @return PurchasingSubTankIntake
     */
    public function create(array $data): PurchasingSubTankIntake;

    /**
     * @param int $id
     * @param int $companyId
     * @return PurchasingSubTankIntake|null
     */
    public function findById(int $id, int $companyId): ?PurchasingSubTankIntake;

    /**
     * @param array $params
     * @param int $companyId
     * @return array
     */
    public function findAll(array $params, int $companyId): array;
}
