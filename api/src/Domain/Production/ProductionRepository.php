<?php

declare(strict_types=1);

namespace App\Domain\Production;

interface ProductionRepository
{
    /**
     * @param array $params
     * @return Production[]
     */
    public function findAll(array $params = []): array;

}
