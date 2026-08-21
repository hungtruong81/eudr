<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\ProductionChannel;

use App\Application\Utility\CurrentUserContext;
use App\Application\Utility\Utils;
use App\Domain\ProductionChannel\ProductionChannel;
use App\Domain\ProductionChannel\ProductionChannelNotFoundException;
use App\Domain\ProductionChannel\ProductionChannelRepository;

class InDatabaseProductionChannelRepository implements ProductionChannelRepository
{
    /**
     * @var \MysqliDb
     */
    private $db;

    /**
     * @var CurrentUserContext
     */
    private $currentUser;

    public function __construct(\MysqliDb $db, CurrentUserContext $currentUserContext)
    {
        $this->db = $db;
        $this->currentUser = $currentUserContext;
    }

    /**
     * Apply scope-based where conditions for queries.
     *
     * @param string $scope
     * @param int $authUserId
     * @param int $companyId
     * @param int|null $companyIdParam
     * @param string $alias
     * @return void
     */
    private function scopeWhere(string $scope, int $authUserId, int $companyId, ?int $companyIdParam = null, string $alias = 'pc'): void
    {
        $prefix = $alias ? $alias . '.' : '';
        $this->db->where($prefix . 'deleted_by', 0);

        if ($scope === 'self') {
            $this->db->where($prefix . 'created_by', $authUserId);
            $this->db->where($prefix . 'company_id', $companyId);
        } elseif ($scope === 'own') {
            $this->db->where($prefix . 'company_id', $companyId);
        } elseif ($scope === 'all' && !empty($companyIdParam)) {
            $this->db->where($prefix . 'company_id', $companyIdParam);
        }
    }

    /**
     * List channel runs with filters and pagination.
     *
     * @param array $params
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return array
     */
    public function findAllChannelRuns(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $productionOrderId = $params['production_order_id'] ?? 0;
        $factoryId = $params['factory_id'] ?? 0;
        $channelId = $params['channel_id'] ?? 0;
        $status = $params['status'] ?? 'all';
        $companyIdParam = $company_id_param ?? 0;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'pcr');
        if (!empty($productionOrderId)) {
            $this->db->where('pcr.production_order_id', (int)$productionOrderId);
        }
        if (!empty($factoryId)) {
            $this->db->where('pcr.factory_id', (int)$factoryId);
        }
        if (!empty($channelId)) {
            $this->db->where('pcr.channel_id', (int)$channelId);
        }
        if ($status !== 'all') {
            $this->db->where('pcr.status', $status);
        }
        $total_records = (int)$this->db->getValue('eudr_production_channel_runs pcr', 'count(*)');

        $this->db->pageLimit = $page_limit;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'pcr');
        if (!empty($productionOrderId)) {
            $this->db->where('pcr.production_order_id', (int)$productionOrderId);
        }
        if (!empty($factoryId)) {
            $this->db->where('pcr.factory_id', (int)$factoryId);
        }
        if (!empty($channelId)) {
            $this->db->where('pcr.channel_id', (int)$channelId);
        }
        if ($status !== 'all') {
            $this->db->where('pcr.status', $status);
        }

        $cols = 'pcr.*, pc.channel_code, pc.channel_name, rmt.raw_material_tank_code, rmt.raw_material_tank_name';
        $this->db->join('eudr_production_channels pc', 'pc.channel_id = pcr.channel_id', 'LEFT');
        $this->db->join('eudr_tanks_raw_material rmt', 'rmt.raw_material_tank_id = pcr.raw_tank_id', 'LEFT');
        $this->db->orderBy('pcr.channel_run_id', 'DESC');
        $records = $this->db->arraybuilder()->paginate('eudr_production_channel_runs pcr', $page, $cols);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = $item;
            }
        }

        return [
            'current_page' => $page,
            'total_pages' => $this->db->totalPages,
            'total_records' => $total_records,
            'page_limit' => $this->db->pageLimit,
            'records' => $items,
        ];
    }

    public function findChannelRunOfIdWithPermission(int $channel_run_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?array
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'pcr');
        $this->db->where('pcr.channel_run_id', $channel_run_id);
        $record = $this->db->getOne('eudr_production_channel_runs pcr', 'pcr.*');

        return !empty($record) ? $record : null;
    }

    public function createCuttingRunFromChannel(array $data): ?array
    {
        $channelRunId = (int)($data['channel_run_id'] ?? 0);
        $cuttingMachineId = (int)($data['cutting_machine_id'] ?? 0);
        $inputChannelLatexKg = (float)($data['input_channel_latex_kg'] ?? 0);
        $notes = $data['notes'] ?? null;
        $updatedBy = (int)($data['updated_by'] ?? 0);

        if ($channelRunId <= 0 || $cuttingMachineId <= 0 || $inputChannelLatexKg <= 0 || $updatedBy <= 0) {
            return null;
        }

        $this->db->startTransaction();

        $this->db->where('channel_run_id', $channelRunId);
        $this->db->where('deleted_by', 0);
        $channelRun = $this->db->getOne('eudr_production_channel_runs', '*');
        if (empty($channelRun)) {
            $this->db->rollback();
            return null;
        }

        $now = date('Y-m-d H:i:s', time());

        $insertData = [
            'channel_run_id' => $channelRunId,
            'production_order_id' => (int)($channelRun['production_order_id'] ?? 0),
            'company_id' => (int)($channelRun['company_id'] ?? 0),
            'factory_id' => (int)($channelRun['factory_id'] ?? 0),
            'cutting_machine_id' => $cuttingMachineId,
            'input_channel_latex_kg' => $inputChannelLatexKg,
            'started_at' => $now,
            'ended_at' => null,
            'status' => 'in_progress',
            'notes' => $notes,
            'created_by' => $updatedBy,
            'created_at' => $now,
            'updated_by' => 0,
            'updated_at' => null,
            'deleted_by' => 0,
            'deleted_at' => null,
        ];

        $this->db->insert('eudr_production_cutting_runs', $insertData);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $cuttingRunId = (int)$this->db->getInsertId();

        $this->db->where('channel_run_id', $channelRunId);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_production_channel_runs', [
            'coagulation_done' => 1,
            'output_ready_for_cutting_kg' => $inputChannelLatexKg,
            'status' => 'completed',
            'ended_at' => $now,
            'updated_by' => $updatedBy,
            'updated_at' => $now,
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->where('channel_id', (int)($channelRun['channel_id'] ?? 0));
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_production_channels', [
            'status' => 'available',
            'updated_by' => $updatedBy,
            'updated_at' => $now,
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->where('cutting_machine_id', $cuttingMachineId);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_production_cutting_machines', [
            'status' => 'in_use',
            'updated_by' => $updatedBy,
            'updated_at' => $now,
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->commit();

        return [
            'cutting_run_id' => $cuttingRunId,
            'channel_run_id' => $channelRunId,
            'input_channel_latex_kg' => $inputChannelLatexKg,
            'status' => 'in_progress',
            'started_at' => $now,
        ];
    }

    /**
     * Find all production channels with optional filters and pagination.
     *
     * @param array $params
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return array
     */
    public function findAll($params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $search = $params['search'] ?? '';
        $channelCode = $params['channel_code'] ?? '';
        $channelName = $params['channel_name'] ?? '';
        $status = $params['status'] ?? 'all';
        $factoryId = $params['factory_id'] ?? 0;
        $companyIdParam = $company_id_param ?? 0;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'pc');
        if (!empty($search)) {
            $this->db->where('(pc.channel_code LIKE ? OR pc.channel_name LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($channelCode)) {
            $this->db->where('pc.channel_code', $channelCode);
        }
        if (!empty($channelName)) {
            $this->db->where('pc.channel_name', $channelName);
        }
        if ($status !== 'all') {
            $this->db->where('pc.status', $status);
        }
        if (!empty($factoryId)) {
            $this->db->where('pc.factory_id', $factoryId);
        }
        $total_records = (int)$this->db->getValue('eudr_production_channels pc', 'count(*)');

        $this->db->pageLimit = $page_limit;

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $companyIdParam, 'pc');
        if (!empty($search)) {
            $this->db->where('(pc.channel_code LIKE ? OR pc.channel_name LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($channelCode)) {
            $this->db->where('pc.channel_code', $channelCode);
        }
        if (!empty($channelName)) {
            $this->db->where('pc.channel_name', $channelName);
        }
        if ($status !== 'all') {
            $this->db->where('pc.status', $status);
        }
        if (!empty($factoryId)) {
            $this->db->where('pc.factory_id', $factoryId);
        }

        $cols = 'pc.*, f.factory_name';

        if (!empty($params['order_by'])) {
            $this->db->orderBy('pc.' . $params['order_by'], $params['order_type'] ?? 'ASC');
        } else {
            $this->db->orderBy('pc.channel_id', 'DESC');
        }
        $this->db->join('eudr_factories f', 'f.factory_id = pc.factory_id', 'LEFT');
        $records = $this->db->arraybuilder()->paginate('eudr_production_channels pc', $page, $cols);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = new ProductionChannel($item['channel_id'], $item);
            }
        }

        return [
            'current_page' => $page,
            'total_pages' => $this->db->totalPages,
            'total_records' => $total_records,
            'page_limit' => $this->db->pageLimit,
            'records' => $items,
        ];
    }

    /**
     * Find a production channel by ID.
     *
     * @param int $channel_id
     * @return ProductionChannel|null
     */
    public function findProductionChannelOfId(int $channel_id): ?ProductionChannel
    {
        $this->db->where('pc.channel_id', $channel_id);
        $this->db->where('pc.deleted_by', 0);
        $this->db->join('eudr_factories f', 'f.factory_id = pc.factory_id', 'LEFT');
        $production_channel = $this->db->getOne('eudr_production_channels pc', 'pc.*, f.factory_name');
        if (empty($production_channel)) {
            return null;
        }
        return new ProductionChannel($production_channel['channel_id'], $production_channel);
    }

    /**
     * Find a production channel by ID with permission check.
     *
     * @param int $channel_id
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return ProductionChannel|null
     */
    public function findProductionChannelOfIdWithPermission(int $channel_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductionChannel
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'pc');
        $this->db->where('pc.channel_id', $channel_id);
        $this->db->join('eudr_factories f', 'f.factory_id = pc.factory_id', 'LEFT');
        $production_channel = $this->db->getOne('eudr_production_channels pc', 'pc.*, f.factory_name');
        if (empty($production_channel)) {
            return null;
        }
        return new ProductionChannel($production_channel['channel_id'], $production_channel);
    }

    /**
     * Find a production channel by code.
     *
     * @param string $code
     * @return ProductionChannel|null
     */
    public function findProductionChannelOfCode(string $code): ?ProductionChannel
    {
        $this->db->where('pc.channel_code', $code);
        $this->db->where('pc.deleted_by', 0);
        $this->db->join('eudr_factories f', 'f.factory_id = pc.factory_id', 'LEFT');
        $production_channel = $this->db->getOne('eudr_production_channels pc', 'pc.*, f.factory_name');
        if (empty($production_channel)) {
            return null;
        }
        return new ProductionChannel($production_channel['channel_id'], $production_channel);
    }

    /**
     * Find a production channel by code with permission check.
     *
     * @param string $code
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return ProductionChannel|null
     */
    public function findProductionChannelOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductionChannel
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, 'pc');
        $this->db->where('pc.channel_code', $code);
        $this->db->join('eudr_factories f', 'f.factory_id = pc.factory_id', 'LEFT');
        $production_channel = $this->db->getOne('eudr_production_channels pc', 'pc.*, f.factory_name');
        if (empty($production_channel)) {
            return null;
        }
        return new ProductionChannel($production_channel['channel_id'], $production_channel);
    }

    /**
     * Generate a unique code for production channel.
     *
     * @return string
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = 'pchl-' . date('ymd') . '-' . Utils::generateRandomString(8);
            $production_channel = $this->findProductionChannelOfCode($code);
            if (!$production_channel) {
                break;
            }
        }
        return $code;
    }

    /**
     * Create a new production channel.
     *
     * @param array $data
     * @return ProductionChannel|null
     */
    public function createProductionChannel(array $data): ?ProductionChannel
    {
        $this->db->insert('eudr_production_channels', $data);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }

        $channel_id = $this->db->getInsertId();
        return $this->findProductionChannelOfId($channel_id);
    }

    /**
     * Update a production channel.
     *
     * @param int $channel_id
     * @param array $data_update
     * @return ProductionChannel
     * @throws ProductionChannelNotFoundException
     */
    public function updateProductionChannel(int $channel_id, array $data_update): ProductionChannel
    {
        $this->db->where('channel_id', $channel_id);
        $this->db->update('eudr_production_channels', $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new ProductionChannelNotFoundException("Production Channel not found with ID: $channel_id");
        }
        return $this->findProductionChannelOfId($channel_id);
    }

    /**
     * Update a production channel with permission check.
     *
     * @param int $channel_id
     * @param array $data_update
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return ProductionChannel
     */
    public function updateProductionChannelWithPermission(int $channel_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ProductionChannel
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, '');
        $this->db->where('channel_id', $channel_id);
        $this->db->update('eudr_production_channels', $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new ProductionChannelNotFoundException("Production Channel not found with ID: $channel_id");
        }
        return $this->findProductionChannelOfId($channel_id);
    }

    /**
     * Soft delete a production channel.
     *
     * @param int $channel_id
     * @param int $deleted_by
     * @return void
     */
    public function deleteProductionChannel(int $channel_id, int $deleted_by): void
    {
        $this->db->where('channel_id', $channel_id);
        $this->db->update('eudr_production_channels', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

    /**
     * Soft delete a production channel with permission check.
     *
     * @param int $channel_id
     * @param int $deleted_by
     * @param int|null $auth_user_id
     * @param string $scope
     * @param int|null $company_id
     * @param int|null $company_id_param
     * @return void
     */
    public function deleteProductionChannelWithPermission(int $channel_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);

        $this->scopeWhere($scope, $authUserId, (int)$companyId, $company_id_param, '');
        $this->db->where('channel_id', $channel_id);
        $this->db->update('eudr_production_channels', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

    /**
     * Pour raw material from one tank to one or many channels and persist channel runs/history.
     *
     * @param array $data
     * @return array|null
     */
    public function pourRawMaterialToChannels(array $data): ?array
    {
        $productionOrderId = (int)($data['production_order_id'] ?? 0);
        $companyId = (int)($data['company_id'] ?? 0);
        $factoryId = (int)($data['factory_id'] ?? 0);
        $rawTankId = (int)($data['raw_tank_id'] ?? 0);
        $rawTankType = (string)($data['raw_tank_type'] ?? '');
        $inputQualityNote = $data['input_quality_note'] ?? null;
        $inputPh = $data['input_ph'] ?? null;
        $notes = $data['notes'] ?? null;
        $createdBy = (int)($data['created_by'] ?? 0);
        $channels = $data['channels'] ?? [];

        if ($productionOrderId <= 0 || $companyId <= 0 || $factoryId <= 0 || $rawTankId <= 0 || $createdBy <= 0) {
            return null;
        }
        if (!is_array($channels) || count($channels) === 0) {
            return null;
        }

        $this->db->where('raw_material_tank_id', $rawTankId);
        $this->db->where('deleted_by', 0);
        $tank = $this->db->getOne('eudr_tanks_raw_material', 'raw_material_tank_id, current_volume');
        if (empty($tank)) {
            return null;
        }

        $rawTankCurrentVolume = (float)($tank['current_volume'] ?? 0);
        $totalPourWeight = 0.0;
        foreach ($channels as $channelItem) {
            $weight = isset($channelItem['input_latex_kg']) && $channelItem['input_latex_kg'] !== null
                ? (float)$channelItem['input_latex_kg']
                : (float)($channelItem['capacity_kg'] ?? 0);
            $totalPourWeight += $weight;
        }

        /*
        if ($rawTankCurrentVolume < $totalPourWeight) {
            return null;
        }
        */

        $now = date('Y-m-d H:i:s', time());
        $remainingVolume = $rawTankCurrentVolume;
        $createdRuns = [];

        $this->db->startTransaction();

        foreach ($channels as $channelItem) {
            $channelId = (int)($channelItem['channel_id'] ?? 0);
            $channelCode = (string)($channelItem['channel_code'] ?? '');
            $channelName = (string)($channelItem['channel_name'] ?? '');
            $weight = isset($channelItem['input_latex_kg']) && $channelItem['input_latex_kg'] !== null
                ? (float)$channelItem['input_latex_kg']
                : (float)($channelItem['capacity_kg'] ?? 0);

            if ($channelId <= 0 || $weight <= 0) {
                $this->db->rollback();
                return null;
            }

            $volumeBefore = $remainingVolume;
            $remainingVolume -= $weight;

            $channelRunData = [
                'production_order_id' => $productionOrderId,
                'company_id' => $companyId,
                'factory_id' => $factoryId,
                'raw_tank_id' => $rawTankId,
                'channel_id' => $channelId,
                'input_latex_kg' => $weight,
                'input_quality_note' => $inputQualityNote,
                'input_ph' => $inputPh,
                'coagulation_done' => 0,
                'output_ready_for_cutting_kg' => 0,
                'started_at' => $now,
                'ended_at' => null,
                'status' => 'in_progress',
                'notes' => $notes,
                'created_by' => $createdBy,
                'created_at' => $now,
                'updated_by' => 0,
                'updated_at' => null,
                'deleted_by' => 0,
                'deleted_at' => null,
            ];

            $this->db->insert('eudr_production_channel_runs', $channelRunData);
            if ($this->db->getLastErrno() !== 0) {
                $this->db->rollback();
                return null;
            }

            $channelRunId = (int)$this->db->getInsertId();

            $historyData = [
                'raw_material_tank_id' => $rawTankId,
                'entity_type' => 'production_channel_run',
                'entity_id' => $channelRunId,
                'action_type' => 'output',
                'rubber_type' => $rawTankType,
                'weight' => $weight,
                'volume_before' => $volumeBefore,
                'volume_after' => $remainingVolume,
                'notes' => 'Xuất mủ từ bồn chứa sang mương ' . $channelCode,
                'created_by' => $createdBy,
                'created_at' => $now,
            ];

            $this->db->insert('eudr_tanks_raw_material_history', $historyData);
            if ($this->db->getLastErrno() !== 0) {
                $this->db->rollback();
                return null;
            }

            $this->db->where('channel_id', $channelId);
            $this->db->where('deleted_by', 0);
            $this->db->update('eudr_production_channels', [
                'status' => 'in_use',
                'updated_by' => $createdBy,
                'updated_at' => $now,
            ]);
            if ($this->db->getLastErrno() !== 0) {
                $this->db->rollback();
                return null;
            }

            $createdRuns[] = [
                'channel_run_id' => $channelRunId,
                'channel_code' => $channelCode,
                'channel_name' => $channelName,
                'input_latex_kg' => $weight,
                'tank_volume_before' => $volumeBefore,
                'tank_volume_after' => $remainingVolume,
                'channel_status_after' => 'in_use',
            ];
        }

        $this->db->where('raw_material_tank_id', $rawTankId);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_tanks_raw_material', [
            'current_volume' => $remainingVolume,
            'updated_by' => $createdBy,
            'updated_at' => $now,
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->commit();

        return [
            'total_output_kg' => $totalPourWeight,
            'remaining_tank_volume' => $remainingVolume,
            'channel_runs' => $createdRuns,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function recordSettlingTankOutput(array $data): ?array
    {
        $productionOrderId = (int)($data['production_order_id'] ?? 0);
        $companyId = (int)($data['company_id'] ?? 0);
        $factoryId = (int)($data['factory_id'] ?? 0);
        $rawTankId = (int)($data['raw_tank_id'] ?? 0);
        $settlingTankCode = trim((string)($data['settling_tank_code'] ?? ''));
        $inputLatexKg = (float)($data['input_latex_kg'] ?? 0);
        $outputLatexKg = (float)($data['output_latex_kg'] ?? 0);
        $inputPh = $data['input_ph'] ?? null;
        $outputPh = $data['output_ph'] ?? null;
        $startedAt = $data['started_at'] ?? null;
        $endedAt = $data['ended_at'] ?? null;
        $settlingDurationHours = (int)($data['settling_duration_hours'] ?? 0);
        $notes = $data['notes'] ?? null;
        $createdBy = (int)($data['created_by'] ?? 0);

        if (
            $productionOrderId <= 0
            || $companyId <= 0
            || $factoryId <= 0
            || $rawTankId <= 0
            || $settlingTankCode === ''
            || $inputLatexKg <= 0
            || $outputLatexKg < 0
            || $createdBy <= 0
        ) {
            return null;
        }

        if ($outputLatexKg > $inputLatexKg) {
            return null;
        }

        $this->db->where('settling_tank_code', $settlingTankCode);
        $this->db->where('company_id', $companyId);
        $this->db->where('factory_id', $factoryId);
        $this->db->where('deleted_by', 0);
        $settlingTank = $this->db->getOne('eudr_production_settling_tanks', 'settling_tank_id, settling_tank_code, settling_tank_name, status');
        if (empty($settlingTank)) {
            return null;
        }

        $settlingTankId = (int)($settlingTank['settling_tank_id'] ?? 0);
        if ($settlingTankId <= 0) {
            return null;
        }

        $lossWeightKg = $inputLatexKg - $outputLatexKg;
        $now = date('Y-m-d H:i:s', time());
        $runStartedAt = !empty($startedAt) ? (string)$startedAt : $now;
        $runEndedAt = !empty($endedAt) ? (string)$endedAt : $now;

        $this->db->startTransaction();

        $this->db->insert('eudr_production_settling_tank_runs', [
            'production_order_id' => $productionOrderId,
            'company_id' => $companyId,
            'factory_id' => $factoryId,
            'raw_tank_id' => $rawTankId,
            'settling_tank_id' => $settlingTankId,
            'input_latex_kg' => $inputLatexKg,
            'output_latex_kg' => $outputLatexKg,
            'loss_weight_kg' => $lossWeightKg,
            'input_ph' => $inputPh,
            'output_ph' => $outputPh,
            'started_at' => $runStartedAt,
            'ended_at' => $runEndedAt,
            'settling_duration_hours' => $settlingDurationHours,
            'status' => 'completed',
            'notes' => $notes,
            'created_by' => $createdBy,
            'created_at' => $now,
            'updated_by' => 0,
            'updated_at' => null,
            'deleted_by' => 0,
            'deleted_at' => null,
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $settlingTankRunId = (int)$this->db->getInsertId();

        $this->db->where('settling_tank_id', $settlingTankId);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_production_settling_tanks', [
            'status' => 'available',
            'updated_by' => $createdBy,
            'updated_at' => $now,
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->commit();

        return [
            'settling_tank_run_id' => $settlingTankRunId,
            'settling_tank_id' => $settlingTankId,
            'settling_tank_code' => (string)($settlingTank['settling_tank_code'] ?? ''),
            'settling_tank_name' => (string)($settlingTank['settling_tank_name'] ?? ''),
            'input_latex_kg' => $inputLatexKg,
            'output_latex_kg' => $outputLatexKg,
            'loss_weight_kg' => $lossWeightKg,
            'status' => 'completed',
            'started_at' => $runStartedAt,
            'ended_at' => $runEndedAt,
            'settling_duration_hours' => $settlingDurationHours,
            'notes' => $notes,
        ];
    }
}
