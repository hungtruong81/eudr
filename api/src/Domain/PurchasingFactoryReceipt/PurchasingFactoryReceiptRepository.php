<?php

declare(strict_types=1);

namespace App\Domain\PurchasingFactoryReceipt;

interface PurchasingFactoryReceiptRepository
{
    /**
     * @param string $transportCode
     * @param int $companyId
     * @param array<string, mixed> $data
     * @param int $userId
     * @return PurchasingFactoryReceipt
     */
    public function createForTransport(string $transportCode, int $companyId, array $data, int $userId): PurchasingFactoryReceipt;

    /**
     * @param string $code
     * @param int $companyId
     * @return PurchasingFactoryReceipt|null
     */
    public function findByCode(string $code, int $companyId): ?PurchasingFactoryReceipt;

    /**
     * @param array<string, mixed> $params
     * @param int $companyId
     * @param int $userId
     * @param string $scope
     * @return PurchasingFactoryReceipt[]
     */
    public function findAll(array $params, int $companyId, int $userId, string $scope): array;

    /**
     * @param string $code
     * @param int $companyId
     * @param int $userId
     * @return PurchasingFactoryReceipt
     */
    public function post(string $code, int $companyId, int $userId): PurchasingFactoryReceipt;

    /**
     * @param string $code
     * @param int $companyId
     * @param string|null $notes
     * @param int $userId
     * @return PurchasingFactoryReceipt
     */
    public function cancel(string $code, int $companyId, ?string $notes, int $userId): PurchasingFactoryReceipt;
}
