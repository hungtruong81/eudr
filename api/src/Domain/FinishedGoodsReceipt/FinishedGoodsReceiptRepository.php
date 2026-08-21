<?php

declare(strict_types=1);

namespace App\Domain\FinishedGoodsReceipt;

interface FinishedGoodsReceiptRepository
{
    /**
     * @param array $params
     * @return FinishedGoodsReceipt[]
     */
    public function findAll(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;
    /**
     * @return string
     */
    public function generateCode(): string;
    /**
     * @param int $finished_goods_receipt_id
     * @return FinishedGoodsReceipt|null
     * @throws FinishedGoodsReceiptNotFoundException
     */
    public function findFinishedGoodsReceiptOfId(int $finished_goods_receipt_id): ?FinishedGoodsReceipt;
    /**
     * @param int $finished_goods_receipt_id
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return FinishedGoodsReceipt|null
     */
    public function findFinishedGoodsReceiptOfIdWithPermission(int $finished_goods_receipt_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?FinishedGoodsReceipt;
    /**
     * @param string $code
     * @return FinishedGoodsReceipt|null
     */
    public function findFinishedGoodsReceiptOfCode(string $code): ?FinishedGoodsReceipt;
    /**
     * @param string $code
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return FinishedGoodsReceipt|null
     */
    public function findFinishedGoodsReceiptOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?FinishedGoodsReceipt;
    /**
     * @param array $data
     * @return FinishedGoodsReceipt|null
     */
    public function createFinishedGoodsReceipt(array $data): ?FinishedGoodsReceipt;
    /**
     * @param int $finished_goods_receipt_id
     * @param array $data_update
     * @return FinishedGoodsReceipt
     */
    public function updateFinishedGoodsReceipt(int $finished_goods_receipt_id, array $data_update): FinishedGoodsReceipt;
    /**
     * @param int $finished_goods_receipt_id
     * @param int $deleted_by
     * @return void
     */
    public function deleteFinishedGoodsReceipt(int $finished_goods_receipt_id, int $deleted_by): void;
    /**
     * @param array $params
     * @return array
     */
    public function productionFinishedGoodsSummary(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;
}
