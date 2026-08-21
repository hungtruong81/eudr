<?php

declare(strict_types=1);

namespace App\Domain\ExternalMaterial;

interface ExternalMaterialRepository
{
    public function findAll(array $params = []): array;

    public function generateCode(): string;

    public function findExternalMaterialOfId(int $external_material_id): ?ExternalMaterial;

    public function findExternalMaterialOfCode(string $code): ?ExternalMaterial;

    public function createExternalMaterial(array $data): ?ExternalMaterial;

    public function updateExternalMaterial(int $external_material_id, array $data): ?ExternalMaterial;

    public function deleteExternalMaterial(int $external_material_id, int $deleted_by): void;

    public function confirmExternalMaterial(int $external_material_id, int $confirmed_by): ?ExternalMaterial;

    public function cancelExternalMaterial(int $external_material_id, int $cancelled_by): ?ExternalMaterial;

    public function findLandsByExternalMaterialId(int $external_material_id): array;

    public function findTransportByExternalMaterialId(int $external_material_id): ?array;
}
