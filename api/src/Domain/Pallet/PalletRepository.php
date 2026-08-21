<?php

declare(strict_types=1);

namespace App\Domain\Pallet;

interface PalletRepository
{
    public function findAll(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;

    public function generateCode(): string;

    public function findPalletOfId(int $pallet_id): ?Pallet;

    public function findPalletOfIdWithPermission(int $pallet_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Pallet;

    public function findPalletOfCode(string $code): ?Pallet;

    public function findPalletOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Pallet;

    public function createPallet(array $data): ?Pallet;

    public function updatePalletWithPermission(int $pallet_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): Pallet;

    public function deletePalletWithPermission(int $pallet_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void;

    public function listPalletItems(int $pallet_id): array;

    public function addPalletItems(int $pallet_id, array $rubber_block_ids): array;

    public function removePalletItem(int $pallet_id, int $pallet_item_id): void;

    public function recalculatePalletTotals(int $pallet_id): ?Pallet;

    public function updateStatusWithPermission(int $pallet_id, string $status, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Pallet;
}
