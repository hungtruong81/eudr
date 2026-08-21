<?php

declare(strict_types=1);

namespace App\Domain\Harvest;

interface HarvestRepository
{
    /**
     * @param array $params
     * @return Harvest[]
     */
    public function findAllHarvestPlans(array $params = []): array;
    /**
     * @return string
     */
    public function generateHarvestPlanCode(): string;
    /**
     * @param array $data
     * @return HarvestPlan
     */
    public function createHarvestPlan(array $data): HarvestPlan;
    /**
     * @param int $harvest_plan_id
     * @param array $data
     * @return HarvestPlan
     */
    public function updateHarvestPlan(int $harvest_plan_id, array $data): HarvestPlan;
    /**
     * @param string $harvest_plan_code
     * @return HarvestPlan|null
     */
    public function findHarvestPlanOfCode(string $harvest_plan_code): ?HarvestPlan;
    /**
     * @param string $code
     * @param int $user_id
     * @param string $scope
     * @return HarvestPlan|null
     */
    public function findHarvestPlanOfCodeWithPermission(string $harvest_plan_code, int $user_id, string $scope): ?HarvestPlan;
    /**
     * @param string $harvest_schedule_code
     * @return HarvestSchedule|null
     */
    public function findHarvestScheduleOfCode(string $harvest_schedule_code): ?HarvestSchedule;
    /**
     * @return string
     */
    public function generateHarvestScheduleCode(): string;
    /**
     * @param array $data
     * @return array
     */
    public function createHarvestSchedule(array $data): array;
    /**
     * @param array $data
     * @return array
     */
    public function createOrUpdateHarvestSchedules(array $data): array;
    /**
     * @param int $harvest_plan_id
     * @param int $user_id
     * @return void
     */
    public function deleteHarvestPlan(int $harvest_plan_id, int $user_id): void;
    /**
     * @param array $params
     * @return HarvestSchedule[]
     */
    public function findAllHarvestSchedules(array $params = []): ?array;
    /**
     * @param int $harvest_schedule_code
     * @param int $current_user_id
     * @param int $worker_id
     * @return bool
     */
    public function canUpdateHarvestResult(string $harvest_schedule_code, int $current_user_id, int $worker_id, array $data): bool;
    /**
     * @param string $harvest_schedule_code
     * @param int $user_id
     * @param string $scope
     * @param string $user_role
     * @return HarvestSchedule|null
     */
    public function findHarvestScheduleOfCodeWithPermission(string $harvest_schedule_code, int $user_id, string $scope, string $user_role): HarvestSchedule|null;
    /**
     * @param string $harvest_schedule_code
     * @param int $user_id
     * @param array $data
     * @return array|null
     */
    public function updateActualYieldOfHarvestSchedule(string $harvest_schedule_code, int $user_id, array $data): array|null;

}
