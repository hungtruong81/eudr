<?php

declare(strict_types=1);

namespace App\Domain\PurchasingTransport;

interface PurchasingTransportRepository
{
    /**
     * @param array $data
     * @return PurchasingTransport
     */
    public function create(array $data): PurchasingTransport;

    /**
     * @param string $code
     * @param int $companyId
     * @return PurchasingTransport|null
     */
    public function findByCode(string $code, int $companyId): ?PurchasingTransport;

    /**
     * @param array $params
     * @param int $companyId
     * @param int $userId
     * @param string $scope
     * @return array
     */
    public function findAll(array $params, int $companyId, int $userId, string $scope): array;

    /**
     * @param string $code
     * @param int $companyId
     * @param array $data
     * @param int $userId
     * @return PurchasingTransport
     */
    public function update(string $code, int $companyId, array $data, int $userId): PurchasingTransport;

    /**
     * @param string $code
     * @param int $companyId
     * @param int $userId
     * @return PurchasingTransport
     */
    public function addLine(string $code, int $companyId, array $data, int $userId): PurchasingTransport;

    /**
     * @param string $code
     * @param int $lineId
     * @param int $companyId
     * @param array $data
     * @param int $userId
     * @return PurchasingTransport
     */
    public function updateLine(string $code, int $lineId, int $companyId, array $data, int $userId): PurchasingTransport;

    /**
     * @param string $code
     * @param int $lineId
     * @param int $companyId
     * @param int $userId
     * @return PurchasingTransport
     */
    public function deleteLine(string $code, int $lineId, int $companyId, int $userId): PurchasingTransport;

    /**
     * @param string $code
     * @param int $companyId
     * @param array $data
     * @param int $userId
     * @return PurchasingTransport
     */
    public function dispatch(string $code, int $companyId, array $data, int $userId): PurchasingTransport;

    /**
     * @param string $code
     * @param int $companyId
     * @param array $data
     * @param int $userId
     * @return PurchasingTransport
     */
    public function arrive(string $code, int $companyId, array $data, int $userId): PurchasingTransport;

    /**
     * @param string $code
     * @param int $companyId
     * @param array $data
     * @param int $userId
     * @return PurchasingTransport
     */
    public function cancel(string $code, int $companyId, array $data, int $userId): PurchasingTransport;
}
