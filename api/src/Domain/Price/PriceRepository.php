<?php

declare(strict_types=1);

namespace App\Domain\Price;

interface PriceRepository
{
    public function findAll(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;

    public function findPriceOfId(int $price_id): ?Price;

    public function findPriceOfIdWithPermission(int $price_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Price;

    public function findPriceOfCode(string $price_code): ?Price;

    public function findPriceOfCodeWithPermission(string $price_code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Price;

    public function createPrice(array $data): ?Price;

    public function generateCode(): string;

    public function updatePriceWithPermission(int $price_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): Price;

    public function deletePriceWithPermission(int $price_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void;
}
