<?php

declare(strict_types=1);

namespace App\Domain\ProductionChannel;

interface ProductionChannelRepository
{
    /**
     * @param array $params
     * @return ProductionChannel[]
     */
    public function findAll(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;

    /**
     * @return string
     */
    public function generateCode(): string;

    /**
     * @param int $channel_id
     * @return ProductionChannel|null
     * @throws ProductionChannelNotFoundException
     */
    public function findProductionChannelOfId(int $channel_id): ?ProductionChannel;

    public function findProductionChannelOfIdWithPermission(int $channel_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductionChannel;

    /**
     * @param string $code
     * @return ProductionChannel|null
     */
    public function findProductionChannelOfCode(string $code): ?ProductionChannel;

    public function findProductionChannelOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductionChannel;

    /**
     * @param array $data
     * @return ProductionChannel|null
     */
    public function createProductionChannel(array $data): ?ProductionChannel;

    /**
     * @param int $channel_id
     * @param array $data_update
     * @return ProductionChannel
     */
    public function updateProductionChannel(int $channel_id, array $data_update): ProductionChannel;

    public function updateProductionChannelWithPermission(int $channel_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ProductionChannel;

    /**
     * @param int $channel_id
     * @param int $deleted_by
     * @return void
     */
    public function deleteProductionChannel(int $channel_id, int $deleted_by): void;

    public function deleteProductionChannelWithPermission(int $channel_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void;

    /**
     * Pour raw material from one tank to one or many channels and persist channel runs/history.
     *
     * @param array $data
     * @return array|null
     */
    public function pourRawMaterialToChannels(array $data): ?array;

    /**
     * Record output latex quantity after settling process is completed.
     *
     * @param array $data
     * @return array|null
     */
    public function recordSettlingTankOutput(array $data): ?array;

    /**
     * List channel runs with filters.
     *
     * @param array $params
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return array
     */
    public function findAllChannelRuns(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;

    /**
     * Find one channel run by id with scope permission.
     *
     * @param int $channel_run_id
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return array|null
     */
    public function findChannelRunOfIdWithPermission(int $channel_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array;

    /**
     * Create cutting run from a channel run.
     *
     * @param array $data
     * @return array|null
     */
    public function createCuttingRunFromChannel(array $data): ?array;

}
