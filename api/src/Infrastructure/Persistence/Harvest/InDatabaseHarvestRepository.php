<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Harvest;

use App\Domain\Harvest\HarvestNotFoundException;
use App\Domain\Harvest\HarvestErrorException;
use App\Domain\Harvest\HarvestRepository;
use App\Application\Utility\Utils;
use App\Domain\Harvest\HarvestPlan;
use App\Domain\Harvest\HarvestSchedule;
use App\Application\Utility\CurrentUserContext;

class InDatabaseHarvestRepository implements HarvestRepository
{
    /**
     * @var MysqliDb
     */
    private $db;

    /**
     * @var CurrentUserContext
     */
    private $currentUser;

    /**
     * InDatabaseHarvestRepository constructor.
     *
     * @param MysqliDb $db
     * @param CurrentUserContext $currentUserContext
     */
    public function __construct(\MysqliDb $db, CurrentUserContext $currentUserContext)
    {
        $this->db = $db;
        $this->currentUser = $currentUserContext;
    }

    /**
     * Apply scope-based filtering (self/own/all) for harvest plans.
     */
    private function scopePlan(string $scope, int $authUserId, int $companyId, ?int $companyIdParam = null, string $alias = 'plan'): void
    {
        $prefix = $alias ? $alias . '.' : '';
        $this->db->where($prefix . 'deleted_by', 0);

        if ($scope === 'self') {
            // $this->db->where($prefix . 'created_by', $authUserId);
            // $this->db->where($prefix . 'company_id', $companyId);
            $this->db->where('(' . $prefix . 'created_by = ? AND ' . $prefix . 'company_id = ?) OR (' . $prefix . 'buyer_user_id = ? AND ' . $prefix . 'buyer_company_id = ?)', [$authUserId, $companyId, $authUserId, $companyId]);
            return;
        }

        if ($scope === 'own') {
            //$this->db->where($prefix . 'company_id', $companyId);
            $this->db->where('(' . $prefix . 'company_id = ? OR ' . $prefix . 'buyer_company_id = ?)', [$companyId, $companyId]);
            return;
        }

        if ($scope === 'all' && !empty($companyIdParam)) {
            $this->db->where($prefix . 'company_id', $companyIdParam);
        }
    }

    /**
     * Apply scope-based filtering (self/own/all) for harvest schedules using plan company context.
     */
    private function scopeSchedule(string $scope, int $authUserId, int $companyId, ?int $companyIdParam = null, string $scheduleAlias = 'hs', string $planAlias = 'hp'): void
    {
        $hsPrefix = $scheduleAlias ? $scheduleAlias . '.' : '';
        $hpPrefix = $planAlias ? $planAlias . '.' : '';

        $this->db->where($hsPrefix . 'deleted_by', 0);

        if ($scope === 'self') {
            // $this->db->where($hsPrefix . 'created_by', $authUserId);
            // $this->db->where($hpPrefix . 'company_id', $companyId);
            $this->db->where('(' . $hpPrefix . 'created_by = ? AND ' . $hpPrefix . 'company_id = ?) OR (' . $hpPrefix . 'buyer_user_id = ? AND ' . $hpPrefix . 'buyer_company_id = ?)', [$authUserId, $companyId, $authUserId, $companyId]);

            return;
        }

        if ($scope === 'own') {
            // $this->db->where($hpPrefix . 'company_id', $companyId);
            $this->db->where('(' . $hpPrefix . 'company_id = ? OR ' . $hpPrefix . 'buyer_company_id = ?)', [$companyId, $companyId]);
            return;
        }

        if ($scope === 'all' && !empty($companyIdParam)) {
            $this->db->where($hpPrefix . 'company_id', $companyIdParam);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findAllHarvestPlans($params = []): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $search = $params['search'] ?? '';
        $harvest_start_date = $params['harvest_start_date'] ?? '';
        $harvest_end_date = $params['harvest_end_date'] ?? '';
        $tapping_regime = $params['tapping_regime'] ?? '';
        $scope = $params['scope'] ?? '';
        $user_id = $params['user_id'] ?? 0;
        $company_id = $params['company_id'] ?? ($this->currentUser->getCompanyId() ?? 0);
        $company_id_param = $params['company_id_param'] ?? null;
        $contract_code = $params['contract_code'] ?? '';

        $authUserId = (int)($user_id ?: ($this->currentUser->getUserId() ?? 0));
        $companyId = (int)$company_id;
        $companyIdParam = $company_id_param !== null ? (int)$company_id_param : null;

        // Count total records
        $this->scopePlan((string)$scope, $authUserId, $companyId, $companyIdParam, 'plan');
        if (!empty($search)) {
            $this->db->where("(plan.notes LIKE '%$search%')");
        }
        if (!empty($tapping_regime)) {
            $this->db->where("plan.tapping_regime", $tapping_regime);
        }
        if (!empty($harvest_start_date)) {
            $this->db->where("plan.harvest_start_date", $harvest_start_date, '>=');
        }
        if (!empty($harvest_end_date)) {
            $this->db->where("plan.harvest_end_date", $harvest_end_date, '<=');
        }
        if (!empty($contract_code)) {
            $this->db->where("plan.contract_code", $contract_code);
        }
        $total_records = $this->db->getValue("eudr_harvest_plans plan", "count(*)");

        // Query paginated records
        $this->db->pageLimit = $page_limit;
        $this->scopePlan((string)$scope, $authUserId, $companyId, $companyIdParam, 'plan');
        if (!empty($search)) {
            $this->db->where("(plan.notes LIKE '%$search%')");
        }
        if (!empty($tapping_regime)) {
            $this->db->where("plan.tapping_regime", $tapping_regime);
        }
        if (!empty($harvest_start_date)) {
            $this->db->where("plan.harvest_start_date", $harvest_start_date, '>=');
        }
        if (!empty($harvest_end_date)) {
            $this->db->where("plan.harvest_end_date", $harvest_end_date, '<=');
        }
        if (!empty($contract_code)) {
            $this->db->where("plan.contract_code", $contract_code);
        }
        if (!empty($params['order_by'])) {
            $this->db->orderBy('plan.' . $params['order_by'], $params['order_type'] ?? 'ASC');
        } else {
            $this->db->orderBy("plan.harvest_plan_id", "DESC");
        }

        $cols = "plan.*, u.full_name AS farmer_name";
        $this->db->join("eudr_users u", "u.user_id = plan.farmer_id", "LEFT");
        $records = $this->db->arraybuilder()->paginate("eudr_harvest_plans plan", $page, $cols);

        $items = [];

        if ($this->db->count > 0) {
            $harvest_plan_ids = array_column($records, 'harvest_plan_id');

            // Fetch schedule aggregates in a single query
            $this->db->where("hs.harvest_plan_id", $harvest_plan_ids, 'IN');
            $this->db->where("hs.deleted_by", 0);
            $this->db->groupBy("hs.harvest_plan_id");
            $data_schedule_agg = $this->db->get("eudr_harvest_schedules hs", null, [
                "hs.harvest_plan_id",
                "COUNT(hs.harvest_schedule_id) AS schedule_count",
                "SUM(hs.actual_yield) AS actual_yield",
                "COUNT(CASE WHEN hs.actual_yield > 0 THEN 1 END) AS harvest_count"
            ]);

            $aggregates = [];
            foreach ($data_schedule_agg as $row) {
                $aggregates[$row['harvest_plan_id']] = [
                    'schedule_count' => $row['schedule_count'],
                    'actual_yield' => $row['actual_yield'],
                    'harvest_count' => $row['harvest_count'],
                ];
            }

            // Fetch lands associated with harvest plans
            $this->db->join("eudr_lands l", "l.plot_id = pl.plot_id", "LEFT");
            $this->db->where("pl.harvest_plan_id", $harvest_plan_ids, "IN");
            $land_records = $this->db->get("eudr_harvest_plan_lands pl", null, [
                "pl.harvest_plan_id",
                "l.plot_id",
                "l.plot_code",
                "l.plot_name"
            ]);

            // Group lands by harvest_plan_id
            $land_map = [];
            foreach ($land_records as $land) {
                $plan_id = $land['harvest_plan_id'];
                unset($land['harvest_plan_id']);
                $land_map[$plan_id][] = $land;
            }

            foreach ($records as $item) {
                $agg = $aggregates[$item['harvest_plan_id']] ?? ['schedule_count' => 0, 'actual_yield' => 0, 'harvest_count' => 0];
                $item['lands'] = $land_map[$item['harvest_plan_id']] ?? [];
                $item['schedule_count'] = $agg['schedule_count'];
                $item['actual_yield'] = $agg['actual_yield'];
                $item['harvest_count'] = $agg['harvest_count'];
                $items[] = new HarvestPlan($item['harvest_plan_id'], $item);
            }
        }


        return [
            "current_page" => $page,
            "total_pages" => $this->db->totalPages,
            "total_records" => $total_records,
            "page_limit" => $this->db->pageLimit,
            "records" => $items,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function findHarvestPlanOfCodeWithPermission(string $harvest_plan_code, int $user_id, string $scope): ?HarvestPlan
    {
        $authUserId = (int)($user_id ?: ($this->currentUser->getUserId() ?? 0));
        $companyId = (int)($this->currentUser->getCompanyId() ?? 0);

        $this->scopePlan((string)$scope, $authUserId, $companyId, null, '');
        $this->db->where("harvest_plan_code", $harvest_plan_code);
        $harvest_plan = $this->db->getOne("eudr_harvest_plans");
        if (empty($harvest_plan)) {
            return null;
        }

        $harvest_plan_id = $harvest_plan['harvest_plan_id'];

        $harvest_plan_details = $this->getDetailOfHarvestPlan($harvest_plan_id);

        $harvest_plan['lands'] = $harvest_plan_details['lands'] ?? [];
        $harvest_plan['schedule_count'] = $harvest_plan_details['schedule_count'] ?? 0;
        $harvest_plan['actual_yield'] = $harvest_plan_details['actual_yield'] ?? 0;
        $harvest_plan['harvest_count'] = $harvest_plan_details['harvest_count'] ?? 0;

        return new HarvestPlan($harvest_plan['harvest_plan_id'], $harvest_plan);
    }

    /**
     * {@inheritdoc}
     */
    public function findHarvestPlanOfCode(string $harvest_plan_code): ?HarvestPlan
    {
        $this->db->where("harvest_plan_code", $harvest_plan_code);
        $this->db->where("deleted_by", 0);

        $harvest_plan = $this->db->getOne("eudr_harvest_plans");
        if (empty($harvest_plan)) {
            return null;
        }

        return new HarvestPlan($harvest_plan['harvest_plan_id'], $harvest_plan);
    }

    /**
     * {@inheritdoc}
     */
    public function findHarvestScheduleOfCode(string $harvest_schedule_code): ?HarvestSchedule
    {
        $this->db->where("harvest_schedule_code", $harvest_schedule_code);
        $this->db->where("deleted_by", 0);

        $harvest_schedule = $this->db->getOne("eudr_harvest_schedules");
        if (empty($harvest_schedule)) {
            return null;
        }

        return new HarvestSchedule($harvest_schedule['harvest_schedule_id'], $harvest_schedule);
    }

    /**
     * {@inheritdoc}
     */
    public function generateHarvestPlanCode(): string
    {
        $code = '';
        while (true) {
            $code = "hvpl-" . date("ymd") . '-' . Utils::generateRandomString(8);
            $harvest_plan = $this->findHarvestPlanOfCode($code);
            if (!$harvest_plan) {
                break;
            }
        }
        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function generateHarvestScheduleCode(): string
    {
        $code = '';
        while (true) {
            $code = "hvsc-" . date("ymd") . '-' . Utils::generateRandomString(8);
            $harvest_schedule = $this->findHarvestScheduleOfCode($code);
            if (!$harvest_schedule) {
                break;
            }
        }
        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function createHarvestPlansLand(int $harvest_plan_id, array $plot_ids): void
    {
        $this->db->where("harvest_plan_id", $harvest_plan_id);
        $this->db->delete("eudr_harvest_plan_lands");
        if (!empty($plot_ids) && is_array($plot_ids)) {
            foreach ($plot_ids as $plot_id) {
                $this->db->insert("eudr_harvest_plan_lands", [
                    'harvest_plan_id' => $harvest_plan_id,
                    'plot_id' => $plot_id,
                ]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createHarvestPlan(array $data): HarvestPlan
    {

        // Validate required fields
        $plot_ids = $data['plot_ids'] ?? [];
        unset($data['plot_ids']);

        $harvest_plan_id = $this->db->insert("eudr_harvest_plans", $data);
        if (!$harvest_plan_id) {
            throw new HarvestNotFoundException("Failed to create harvest plan");
        }

        // Insert plot IDs into the eudr_harvest_plan_lands table
        if (!empty($plot_ids)) {
            $this->createHarvestPlansLand($harvest_plan_id, $plot_ids);
        }

        // Fetch the newly created harvest plan
        $this->db->where("hvp.harvest_plan_id", $harvest_plan_id);
        $harvest_plan = $this->db->getOne("eudr_harvest_plans hvp", "hvp.*");
        if (empty($harvest_plan)) {
            throw new HarvestNotFoundException();
        }

        $harvest_plan_details = $this->getDetailOfHarvestPlan($harvest_plan_id);

        $harvest_plan['lands'] = $harvest_plan_details['lands'] ?? [];
        $harvest_plan['schedule_count'] = $harvest_plan_details['schedule_count'] ?? 0;
        $harvest_plan['actual_yield'] = $harvest_plan_details['actual_yield'] ?? 0;
        $harvest_plan['harvest_count'] = $harvest_plan_details['harvest_count'] ?? 0;

        return new HarvestPlan($harvest_plan['harvest_plan_id'], $harvest_plan);
    }

    /**
     * {@inheritdoc}
     */
    public function getDetailOfHarvestPlan(int $harvest_plan_id): array
    {

        $data_result = [];

        // Fetch count of harvest schedules for each plan
        $this->db->where("hs.harvest_plan_id", $harvest_plan_id);
        $this->db->where("hs.deleted_by", 0);
        $this->db->groupBy("hs.harvest_plan_id");
        $data_schedule_counts = $this->db->get("eudr_harvest_schedules hs", null, [
            "hs.harvest_plan_id",
            "COUNT(hs.harvest_schedule_id) AS schedule_count"
        ]);

        // Map schedule counts by harvest_plan_id
        $schedule_counts = [];
        foreach ($data_schedule_counts as $row) {
            $schedule_counts[$row['harvest_plan_id']] = $row['schedule_count'];
        }
        $data_result['schedule_count'] = $schedule_counts[$harvest_plan_id] ?? 0;

        // Fetch actual harvest from eudr_harvest_schedules (actual_yield column)
        $this->db->where("hs.harvest_plan_id", $harvest_plan_id);
        $this->db->where("hs.deleted_by", 0);
        $this->db->groupBy("hs.harvest_plan_id");
        $data_actual_harvests = $this->db->get("eudr_harvest_schedules hs", null, [
            "hs.harvest_plan_id",
            "SUM(hs.actual_yield) AS actual_yield",
            "COUNT(CASE WHEN hs.actual_yield > 0 THEN 1 END) AS harvest_count"
        ]);

        // Map actual yields by harvest_plan_id
        $data_harvests = [];
        foreach ($data_actual_harvests as $row) {
            $data_harvests[$row['harvest_plan_id']] = $row;
        }
        $data_result['actual_yield'] = $data_harvests[$harvest_plan_id]['actual_yield'] ?? 0;
        $data_result['harvest_count'] = $data_harvests[$harvest_plan_id]['harvest_count'] ?? 0;

        // Fetch lands associated with harvest plans
        $this->db->join("eudr_lands l", "l.plot_id = pl.plot_id", "LEFT");
        $this->db->where("pl.harvest_plan_id", $harvest_plan_id);
        $land_records = $this->db->get("eudr_harvest_plan_lands pl", null, [
            "pl.harvest_plan_id",
            "l.plot_id",
            "l.plot_code",
            "l.plot_name"
        ]);

        // Group lands by harvest_plan_id
        $land_map = [];
        foreach ($land_records as $land) {
            $plan_id = $land['harvest_plan_id'];
            unset($land['harvest_plan_id']);
            $land_map[$plan_id][] = $land;
        }
        $data_result['lands'] = $land_map[$harvest_plan_id] ?? [];

        return $data_result;
    }

    /**
     * {@inheritdoc}
     */
    public function updateHarvestPlan(int $harvest_plan_id, array $data): HarvestPlan
    {
        // Validate required fields
        $plot_ids = $data['plot_ids'] ?? [];
        unset($data['plot_ids']);

        // Update the harvest plan
        $this->db->where("harvest_plan_id", $harvest_plan_id);
        if (!$this->db->update("eudr_harvest_plans", $data)) {
            throw new HarvestNotFoundException("Failed to update harvest plan");
        }

        // If plot IDs are provided, update the eudr_harvest_plan_lands table
        if (!empty($plot_ids)) {
            $this->createHarvestPlansLand($harvest_plan_id, $plot_ids);
        }

        $this->db->where("harvest_plan_id", $harvest_plan_id);
        $this->db->where("deleted_by", 0);
        $harvest_plan = $this->db->getOne("eudr_harvest_plans");

        $harvest_plan_details = $this->getDetailOfHarvestPlan($harvest_plan_id);

        $harvest_plan['lands'] = $harvest_plan_details['lands'] ?? [];
        $harvest_plan['schedule_count'] = $harvest_plan_details['schedule_count'] ?? 0;
        $harvest_plan['actual_yield'] = $harvest_plan_details['actual_yield'] ?? 0;
        $harvest_plan['harvest_count'] = $harvest_plan_details['harvest_count'] ?? 0;

        return new HarvestPlan($harvest_plan_id, $harvest_plan);
    }

    /**
     * {@inheritdoc}
     */
    public function createHarvestSchedule(array $data): array
    {
        $this->db->startTransaction();

        foreach ($data['schedules'] as $schedule) {
            $harvest_schedule_id = $this->db->insert('eudr_harvest_schedules', [
                'harvest_schedule_code' => $this->generateHarvestScheduleCode(),
                'harvest_plan_id'   => $data['harvest_plan_id'],
                'plot_id'           => $schedule['plot_id'],
                'pickup_date'       => $schedule['pickup_date'],
                'pickup_time'       => $schedule['pickup_time'],
                'expected_yield'    => $schedule['expected_yield'],
                'company_id'        => $this->currentUser->getCompanyId() ?? 0,
                'buyer_user_id'     => $schedule['buyer_user_id'],
                'buyer_company_id'  => $schedule['buyer_company_id'] ?? 0,
                'created_at'        => date('Y-m-d H:i:s'),
                'created_by'        => $data['created_by'],
            ]);

            if (!$harvest_schedule_id) {
                $this->db->rollback();
                throw new HarvestErrorException("Failed to create harvest schedule", 115);
            }
        }

        $this->db->commit();

        // Fetch list of harvest schedules
        $this->db->where("hs.harvest_plan_id", $data['harvest_plan_id']);
        $this->db->join("eudr_harvest_plans hp", "hp.harvest_plan_id = hs.harvest_plan_id", "LEFT");
        $this->db->join("eudr_lands l", "l.plot_id = hs.plot_id", "LEFT");
        $harvest_schedules = $this->db->get(
            "eudr_harvest_schedules hs",
            null,
            ["hs.*", "l.plot_code", "l.plot_name", "hp.harvest_plan_code"]
        );

        return $harvest_schedules;
    }

    /**
     * {@inheritdoc}
     */
    public function updateActualYieldOfHarvestSchedule(string $harvest_schedule_code, int $user_id, array $data): array
    {
        $this->db->where('harvest_schedule_code', $harvest_schedule_code);
        //$this->db->where('buyer_user_id', $user_id);
        $this->db->where('deleted_by', 0);
        if (!$this->db->update('eudr_harvest_schedules', $data)) {
            throw new HarvestErrorException("Failed to update harvest schedule", 123);
        }

        $this->db->where('harvest_schedule_code', $harvest_schedule_code);
        return $this->db->getOne('eudr_harvest_schedules', null, ['*']);
    }

    /**
     * {@inheritdoc}
     */
    public function createOrUpdateHarvestSchedules(array $data): array
    {

        $this->db->startTransaction();

        try {
            $now = date('Y-m-d H:i:s');

            // 1. Lấy tất cả harvest_schedule_id hiện tại trong DB
            $this->db->where('harvest_plan_id', $data['harvest_plan_id']);
            $this->db->where('deleted_by', 0);
            $currentSchedules = $this->db->get('eudr_harvest_schedules', null, ['harvest_schedule_id']);
            $currentIds = array_column($currentSchedules, 'harvest_schedule_id');

            // 2. Lấy danh sách ID từ request
            $requestIds = [];
            foreach ($data['schedules'] as $schedule) {
                if (!empty($schedule['harvest_schedule_id'])) {
                    $requestIds[] = $schedule['harvest_schedule_id'];
                }
            }

            // 3. Tìm những lịch cần xóa
            $toDeleteIds = array_diff($currentIds, $requestIds);
            if (!empty($toDeleteIds)) {
                foreach ($toDeleteIds as $delId) {
                    // Xóa schedule
                    $this->db->where('harvest_schedule_id', $delId);
                    $this->db->update('eudr_harvest_schedules', [
                        'deleted_by' => $data['updated_by'],
                        'deleted_at' => $now
                    ]);
                }
            }

            // 4. Tiến hành Insert / Update các schedule trong request
            foreach ($data['schedules'] as $schedule) {
                $isNew = false;
                if (empty($schedule['harvest_schedule_id']) || $schedule['harvest_schedule_id'] == 0) {
                    $isNew = true;
                }

                if ($isNew) {
                    // --- CREATE ---
                    $harvest_schedule_id = $this->db->insert('eudr_harvest_schedules', [
                        'harvest_schedule_code' => $this->generateHarvestScheduleCode(),
                        'harvest_plan_id'       => $data['harvest_plan_id'],
                        'plot_id'               => $schedule['plot_id'],
                        'pickup_date'           => $schedule['pickup_date'],
                        'pickup_time'           => $schedule['pickup_time'],
                        'expected_yield'        => $schedule['expected_yield'],
                        'buyer_user_id'         => $schedule['buyer_user_id'],
                        'buyer_company_id'      => $schedule['buyer_company_id'] ?? 0,
                        'created_at'            => $now,
                        'created_by'            => $data['created_by'] ?? $data['updated_by'],
                    ]);
                    if (!$harvest_schedule_id) {
                        $this->db->rollback();
                        throw new HarvestErrorException("Failed to create harvest schedule", 115);
                    }
                } else {
                    $harvest_schedule_id = $schedule['harvest_schedule_id'];

                    // --- UPDATE ---
                    $updateData = [
                        'plot_id'        => $schedule['plot_id'],
                        'pickup_date'    => $schedule['pickup_date'],
                        'pickup_time'    => $schedule['pickup_time'],
                        'expected_yield' => $schedule['expected_yield'],
                        'buyer_user_id'  => $schedule['buyer_user_id'],
                        'buyer_company_id'  => $schedule['buyer_company_id'] ?? 0,
                        'updated_at'     => $now,
                        'updated_by'     => $data['updated_by'],
                    ];
                    $this->db->where('harvest_schedule_id', $harvest_schedule_id);
                    if (!$this->db->update('eudr_harvest_schedules', $updateData)) {
                        $this->db->rollback();
                        throw new HarvestErrorException("Failed to update harvest schedule", 121);
                    }
                }
            }

            $this->db->commit();
            // Lấy lại danh sách schedules
            $this->db->where("hs.harvest_plan_id", $data['harvest_plan_id']);
            $this->db->where("hs.deleted_by", 0);
            $this->db->join("eudr_harvest_plans hp", "hp.harvest_plan_id = hs.harvest_plan_id", "LEFT");
            $this->db->join("eudr_lands l", "l.plot_id = hs.plot_id", "LEFT");
            $harvest_schedules = $this->db->get(
                "eudr_harvest_schedules hs",
                null,
                ["hs.*", "l.plot_code", "l.plot_name", "hp.harvest_plan_code"]
            );

            return $harvest_schedules;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw new HarvestErrorException($e->getMessage(), 124);
        }
    }


    /**
     * {@inheritdoc}
     */
    public function deleteHarvestPlan(int $harvest_plan_id, int $user_id): void
    {
        $this->db->where("harvest_plan_id", $harvest_plan_id);
        $this->db->where("deleted_by", 0);
        $harvest_plan = $this->db->getOne("eudr_harvest_plans");
        if (empty($harvest_plan)) {
            throw new HarvestNotFoundException("Harvest plan not found");
        }

        // Check if there are any existing harvest results
        $this->db->where("harvest_plan_id", $harvest_plan_id);
        $count = $this->db->getValue("eudr_harvest_results", "count(*)");
        if ($count > 0) {
            throw new HarvestErrorException("Cannot delete harvest plan with existing harvest results", 118);
        }

        // Start transaction for deletion
        $this->db->startTransaction();
        // Delete associated harvest schedules
        $data_update = [
            'deleted_by' => $user_id,
            'deleted_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->where("harvest_plan_id", $harvest_plan_id);
        $this->db->where("deleted_by", 0);
        if (!$this->db->update("eudr_harvest_schedules", $data_update)) {
            $this->db->rollback();
            throw new HarvestErrorException("Failed to delete harvest schedules", 119);
        }

        // Delete associated harvest assignments
        $this->db->where("harvest_plan_id", $harvest_plan_id);
        $this->db->where("deleted_by", 0);
        if (!$this->db->update("eudr_harvest_assignment", $data_update)) {
            $this->db->rollback();
            throw new HarvestErrorException("Failed to delete harvest assignments", 120);
        }

        // Delete the harvest plan
        $this->db->where("harvest_plan_id", $harvest_plan_id);
        if (!$this->db->update("eudr_harvest_plans", $data_update)) {
            $this->db->rollback();
            throw new HarvestErrorException("Failed to delete harvest plan", 117);
        }

        $this->db->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function findAllHarvestSchedules(array $params = []): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $search = $params['search'] ?? '';
        $scope = $params['scope'] ?? '';
        $user_id = $params['user_id'] ?? 0;
        $harvest_plan_id = $params['harvest_plan_id'] ?? 0;
        $plot_id = $params['plot_id'] ?? 0;
        $pickup_date = $params['pickup_date'] ?? '';

        $company_id = $params['company_id'] ?? ($this->currentUser->getCompanyId() ?? 0);
        $company_id_param = $params['company_id_param'] ?? null;
        $authUserId = (int)($user_id ?: ($this->currentUser->getUserId() ?? 0));
        $companyId = (int)$company_id;
        $companyIdParam = $company_id_param !== null ? (int)$company_id_param : null;

        // Count total records with scope and filters
        $this->db->join("eudr_harvest_plans hp", "hp.harvest_plan_id = hs.harvest_plan_id", "LEFT");
        $this->scopeSchedule((string)$scope, $authUserId, $companyId, $companyIdParam, 'hs', 'hp');
        if (!empty($search)) {
            $this->db->where("hs.notes LIKE ?", ["%$search%"]);
        }
        if (!empty($pickup_date)) {
            $this->db->where("hs.pickup_date", $pickup_date);
        }
        if (!empty($harvest_plan_id)) {
            $this->db->where("hs.harvest_plan_id", $harvest_plan_id);
        }
        if (!empty($plot_id)) {
            $this->db->where("hs.plot_id", $plot_id);
        }

        $total_records = $this->db->getValue("eudr_harvest_schedules hs", "count(*)");


        $cols = "hs.*";
        // Query lại với limit
        $this->db->pageLimit = $page_limit;
        $this->db->join("eudr_harvest_plans hp", "hp.harvest_plan_id = hs.harvest_plan_id", "LEFT");
        $this->scopeSchedule((string)$scope, $authUserId, $companyId, $companyIdParam, 'hs', 'hp');
        if (!empty($search)) {
            $this->db->where("hs.notes LIKE ?", ["%$search%"]);
        }
        if (!empty($pickup_date)) {
            $this->db->where("hs.pickup_date", $pickup_date);
        }
        if (!empty($harvest_plan_id)) {
            $this->db->where("hs.harvest_plan_id", $harvest_plan_id);
        }
        if (!empty($plot_id)) {
            $this->db->where("hs.plot_id", $plot_id);
        }

        // Lấy dữ liệu có phân trang
        $cols = "hs.*, hp.harvest_plan_code as harvest_plan_code, l.plot_code as plot_code, l.plot_name as plot_name, hp.tapping_regime as tapping_regime";
        $this->db->join("eudr_lands l", "l.plot_id = hs.plot_id", "LEFT");
        $harvest_schedules = $this->db->arraybuilder()->paginate("eudr_harvest_schedules hs", $page, $cols);

        return [
            "current_page" => $page,
            "total_pages" => $this->db->totalPages,
            "total_records" => $total_records,
            "page_limit" => $this->db->pageLimit,
            "records" => $harvest_schedules,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function canUpdateHarvestResult(string $harvest_schedule_code, int $current_user_id, int $worker_id, array $data): bool
    {
        // 1. Lấy thông tin lịch thu hoạch
        $schedule = $this->db
            ->where('harvest_schedule_code', $harvest_schedule_code)
            ->where('deleted_by', 0)
            ->getOne('eudr_harvest_schedules');

        if (!$schedule) {
            throw new HarvestErrorException("Harvest schedule not found", 101);
        }

        $harvest_schedule_id = $schedule['harvest_schedule_id'];
        $created_by  = $schedule['created_by'];
        $buyer_user_id = $schedule['buyer_user_id'];
        // 2. Kiểm tra nếu là Admin -> full quyền
        if (!empty($data['user_role']) && $data['user_role'] === 'admin') {
            return true;
        }

        // 3. Nếu là Farmer (người tạo lịch thu hoạch) -> có quyền
        if ($current_user_id == $created_by) {
            return true;
        }

        if ($current_user_id === $buyer_user_id) {
            return true;
        }

        // 4. Các trường hợp khác: không có quyền
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function findHarvestScheduleOfCodeWithPermission(string $harvest_schedule_code, int $user_id, string $scope, string $user_role): ?HarvestSchedule
    {
        $authUserId = (int)($user_id ?: ($this->currentUser->getUserId() ?? 0));
        $companyId = (int)($this->currentUser->getCompanyId() ?? 0);

        $schedule = $this->db
            ->where('hs.harvest_schedule_code', $harvest_schedule_code)
            ->join('eudr_harvest_plans hp', 'hp.harvest_plan_id = hs.harvest_plan_id', 'LEFT')
            ->join('eudr_lands l', 'l.plot_id = hs.plot_id', 'LEFT')
            ->getOne('eudr_harvest_schedules hs', 'hs.*, l.plot_code, l.plot_name, hp.tapping_regime as tapping_regime, hp.harvest_plan_code as harvest_plan_code, hp.company_id as company_id');

        if (!$schedule) {
            throw new HarvestErrorException("Harvest schedule not found", 101);
        }
        // Scope checks
        if ($scope === 'self') {
            // if ($schedule['created_by'] !== $authUserId || (int)$schedule['company_id'] !== $companyId) {
            //     throw new HarvestErrorException("Permission denied", 105);
            // }
            if ($schedule['created_by'] !== $authUserId && $schedule['buyer_user_id'] !== $authUserId) {
                throw new HarvestErrorException("Permission denied", 105);
            }
        } elseif ($scope === 'own') {
            // if ((int)$schedule['buyer_company_id'] !== $companyId) {
            //     throw new HarvestErrorException("Permission denied", 106);
            // }
        }

        return new HarvestSchedule($schedule['harvest_schedule_id'], $schedule);
    }
}
