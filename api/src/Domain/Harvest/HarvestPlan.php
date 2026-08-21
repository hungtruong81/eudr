<?php

declare(strict_types=1);

namespace App\Domain\Harvest;

use JsonSerializable;

class HarvestPlan implements JsonSerializable
{
    /**
     * @var int|null
     */
    private $harvest_plan_id;
    /**
     * @var string
     */
    private $farmer_name;
    /**
     * @var string
     */
    private $contract_code;
    /**
     * @var string
     */
    private $harvest_plan_code;
    /**
     * @var string
     */
    private $harvest_start_date;
    /**
     * @var string
     */
    private $harvest_end_date;
    /**
     * @var string
     */
    private $tapping_regime;
    /**
     * @var int
     */
    private $expected_yield;
    /**
     * @var int
     */
    private $actual_yield;
    /**
     * @var int
     */
    private $eudr_status;
    /**
     * @var string
     */
    private $notes;
    /**
     * @var array
     */
    private $lands;
    /**
     * @var int|null
     */
    private $schedule_count;
    /**
     * @var int|null
     */
    private $harvest_count;
    /**
     * @var date
     */
    private $created_at;

    /**
     * @param int|null  $harvest_plan_id
     * @param array    $data
     */
    public function __construct(?int $harvest_plan_id, array $data)
    {
        $this->harvest_plan_id = $harvest_plan_id;
        $this->harvest_plan_code = $data['harvest_plan_code'] ?? '';
        $this->farmer_name = $data['farmer_name'] ?? '';
        $this->contract_code = $data['contract_code'] ?? '';
        $this->harvest_start_date = $data['harvest_start_date'] ?? '';
        $this->harvest_end_date = $data['harvest_end_date'] ?? '';
        $this->tapping_regime = $data['tapping_regime'] ?? '';
        $this->expected_yield = $data['expected_yield'] ?? 0;
        $this->actual_yield = $data['actual_yield'] ?? 0;
        $this->eudr_status = $data['eudr_status'] ?? 0;
        $this->notes = $data['notes'] ?? '';
        $this->lands = $data['lands'] ?? [];
        $this->schedule_count = $data['schedule_count'] ?? 0;
        $this->harvest_count = $data['harvest_count'] ?? 0;
        $this->created_at = $data['created_at'] ?? '';

    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->harvest_plan_id;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->harvest_plan_code;
    }
    /**
     * @return string|null
     */
    public function getContractCode(): ?string
    {
        return $this->contract_code;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'harvest_plan_id' => $this->harvest_plan_id,
            'harvest_plan_code' => $this->harvest_plan_code,
            'farmer_name' => $this->farmer_name,
            'contract_code' => $this->contract_code,
            'harvest_start_date' => $this->harvest_start_date,
            'harvest_end_date' => $this->harvest_end_date,
            'tapping_regime' => $this->tapping_regime,
            'expected_yield' => $this->expected_yield,
            'actual_yield' => $this->actual_yield,
            'schedule_count' => $this->schedule_count,
            'harvest_count' => $this->harvest_count,
            'eudr_status' => $this->eudr_status,
            'notes' => $this->notes,
            'lands' => $this->lands,
            'created_at' => $this->created_at,
        ];
    }
}
