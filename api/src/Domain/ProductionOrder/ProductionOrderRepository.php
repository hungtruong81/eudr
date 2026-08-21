<?php

declare(strict_types=1);

namespace App\Domain\ProductionOrder;

interface ProductionOrderRepository
{
    /**
     * @param array $params
     * @return ProductionOrder[]
     */
    public function findAll(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;
    /**
     * @return string
     */
    public function generateCode(): string;
    /**
     * @param int $production_order_id
     * @return ProductionOrder|null
     * @throws ProductionOrderNotFoundException
     */
    public function findProductionOrderOfId(int $production_order_id): ?ProductionOrder;
    public function findProductionOrderOfIdWithPermission(int $production_order_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductionOrder;
    /**
     * @param string $code
     * @return ProductionOrder|null
     */
    public function findProductionOrderOfCode(string $code): ?ProductionOrder;
    public function findProductionOrderOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?ProductionOrder;
    /**
     * @param array $data
     * @return ProductionOrder|null
     */
    public function createProductionOrder(array $data): ?ProductionOrder;
    /**
     * @param int $production_order_id
     * @param array $data_update
     * @return ProductionOrder
     */
    public function updateProductionOrder(int $production_order_id, array $data_update): ProductionOrder;
    public function updateProductionOrderWithPermission(int $production_order_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ProductionOrder;
    /**
     * @param int $production_order_id
     * @param int $deleted_by
     * @return void
     */
    public function deleteProductionOrder(int $production_order_id, int $deleted_by): void;
    public function deleteProductionOrderWithPermission(int $production_order_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void;

    /**
     * Upsert one raw tank entry in eudr_production_order_raw_tank_setup.
     * Returns all raw tank setup rows for this production order.
     */
    public function setupRawTank(int $production_order_id, int $raw_tank_id, float $planned_volume_kg, ?float $actual_volume_kg, ?string $started_at, ?string $ended_at, ?string $notes, int $company_id, int $factory_id, int $user_id): array;

    /**
     * Upsert the single settling tank entry in eudr_production_order_settling_tank_setup.
     * Returns the settling tank setup row for this production order.
     */
    public function setupSettlingTank(int $production_order_id, int $settling_tank_id, ?int $settling_duration_hours, ?string $started_at, ?string $ended_at, ?string $notes, int $company_id, int $factory_id, int $user_id): array;

    /**
     * Upsert one channel entry in eudr_production_order_channel_setup.
     * Returns all channel setup rows for this production order.
     */
    public function setupChannel(int $production_order_id, int $channel_id, float $planned_volume_kg, ?string $coagulation_agent_type, ?string $coagulation_agent_volume, ?string $started_at, ?string $ended_at, ?string $notes, int $company_id, int $factory_id, int $user_id): array;

    /**
     * Upsert the single cutting machine entry in eudr_production_order_cutting_machine_setup.
     * Returns the cutting machine setup row for this production order.
     */
    public function setupCuttingMachine(int $production_order_id, int $cutting_machine_id, float $expected_cutting_weight_kg, int $expected_sheet_quantity, ?string $started_at, ?string $ended_at, ?string $notes, int $company_id, int $factory_id, int $user_id): array;

    /**
     * Upsert one roller setup by quality entry in eudr_production_order_roller_setup_by_quality.
     * Returns all roller setup rows for this production order.
     */
    public function setupRollerByQuality(int $production_order_id, int $grade_id, string $quality_type, int $roller_id, ?float $expected_output_thickness_min_mm, ?float $expected_output_thickness_max_mm, ?string $started_at, ?string $ended_at, int $expected_sheet_quantity, ?string $notes, int $company_id, int $factory_id, int $user_id): array;

    /**
     * Upsert the single hanging setup entry in eudr_production_order_hanging_setup.
     * Returns the hanging setup row for this production order.
     */
    public function setupHanging(int $production_order_id, int $gong_cart_id, ?int $expected_hanging_hours, ?string $started_at, ?string $ended_at, ?array $details, ?string $notes, int $company_id, int $factory_id, int $user_id): array;

    /**
     * Upsert the single drying setup entry in eudr_production_order_drying_setup.
     * Returns the drying setup row for this production order.
     */
    public function setupDrying(int $production_order_id, int $oven_id, ?int $expected_drying_hours, ?float $expected_final_moisture_percent, ?string $started_at, ?string $ended_at, ?string $notes, int $company_id, int $factory_id, int $user_id): array;

    /**
     * Upsert one pressing setup by quality entry in eudr_production_order_pressing_setup.
     * Returns all pressing setup rows for this production order.
     */
    public function setupPressing(int $production_order_id, int $grade_id, int $product_type_id, int $planned_sheet_quantity, ?string $started_at, ?string $ended_at, ?string $notes, int $company_id, int $factory_id, int $user_id): array;

    /**
     * Upsert the single pallet setup entry in eudr_production_order_pallet_setup.
     * Returns the pallet setup row for this production order.
     */
    public function setupPallet(int $production_order_id, int $planned_pallet_quantity, ?string $started_at, ?string $ended_at, ?string $notes, int $company_id, int $factory_id, int $warehouse_id, int $user_id): array;

    /**
     * Create a setup change request in eudr_production_order_setup_change_requests.
     */
    public function createSetupChangeRequest(int $production_order_id, string $change_type, string $change_description, ?array $old_value, ?array $new_value, ?string $reason, int $requested_by, int $company_id, int $factory_id): array;

    /**
     * List setup change requests with pagination and scope filtering.
     */
    public function findAllSetupChangeRequests(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;

    /**
     * Approve a setup change request.
     */
    public function approveSetupChangeRequest(int $change_request_id, int $approved_by, ?string $approval_notes = null, ?array $step_time_overrides = null): array;

    /**
     * Reject a setup change request.
     */
    public function rejectSetupChangeRequest(int $change_request_id, int $approved_by, ?string $approval_notes = null): array;

    /**
     * Returns all configured setup data of a production order.
     */
    public function getFullSetupOfProductionOrder(int $production_order_id): array;

    /**
     * Returns execution data entered/confirmed by workers for each production step.
     */
    public function getExecutionDataOfProductionOrder(int $production_order_id, array $filters = []): array;
}
