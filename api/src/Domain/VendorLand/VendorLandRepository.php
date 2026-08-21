<?php

declare(strict_types=1);

namespace App\Domain\VendorLand;

interface VendorLandRepository
{
    /**
     * @param int $vendorId
     * @param array $params
     * @return array
     */
    public function findAll(int $vendorId, array $params = []): array;

    /**
     * @param int $vendorId
     * @param int $vendorLandId
     * @return array|null
     */
    public function findOne(int $vendorId, int $vendorLandId): ?array;

    /**
     * @param int $vendorId
     * @param int $plotId
     * @return bool
     */
    public function activeRelationExists(int $vendorId, int $plotId): bool;

    /**
     * @param array $data
     * @return array|null
     */
    public function create(array $data): ?array;

    /**
     * @param int $vendorId
     * @param int $vendorLandId
     * @param array $data
     * @return array|null
     */
    public function update(int $vendorId, int $vendorLandId, array $data): ?array;

    /**
     * @param int $vendorId
     * @param int $vendorLandId
     * @param int $deletedBy
     * @return void
     */
    public function delete(int $vendorId, int $vendorLandId, int $deletedBy): void;
}