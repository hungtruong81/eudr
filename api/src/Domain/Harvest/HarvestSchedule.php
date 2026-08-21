<?php

declare(strict_types=1);

namespace App\Domain\Harvest;

use JsonSerializable;

class HarvestSchedule implements JsonSerializable
{
    /**
     * @var int|null
     */
    private $harvest_schedule_id;
    /**
     * @var string
     */
    private $harvest_schedule_code;
    /**
     * @var int
     */
    private $harvest_plan_id;
    /**
     * @var string
     */
    private $harvest_plan_code;
    /**
     * @var int
     */
    private $plot_id;
    /**
     * @var string
     */
    private $plot_name;
    /**
     * @var date
     */
    private $pickup_date;
    /**
     * @var time
     */
    private $pickup_time;
    /**
     * @var float
     */
    private $expected_yield;
    /**
     * @var float
     */
    private $actual_yield;
    /**
     * @var array|string|null
     */
    private $workers = [];
    /**
     * @var string
     */
    private $notes;


    /**
     * @param int|null  $harvest_schedule_id
     * @param array    $data
     */
    public function __construct(?int $harvest_schedule_id, array $data)
    {
        $this->harvest_schedule_id = $harvest_schedule_id;
        $this->harvest_schedule_code = $data['harvest_schedule_code'] ?? '';
        $this->harvest_plan_id = $data['harvest_plan_id'] ?? 0;
        $this->harvest_plan_code = $data['harvest_plan_code'] ?? '';
        $this->plot_id = $data['plot_id'] ?? 0;
        $this->plot_name = $data['plot_name'] ?? '';
        $this->pickup_date = $data['pickup_date'] ?? '';
        $this->pickup_time = $data['pickup_time'] ?? '';
        $this->expected_yield = $data['expected_yield'] ?? 0;
        $this->actual_yield = $data['actual_yield'] ?? 0;
        $this->notes = $data['notes'] ?? '';
        $this->workers = $data['workers'] ?? [];
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->harvest_schedule_id;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->harvest_schedule_code;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'harvest_schedule_id' => $this->harvest_schedule_id,
            'harvest_schedule_code' => $this->harvest_schedule_code,
            'harvest_plan_id' => $this->harvest_plan_id,
            'harvest_plan_code' => $this->harvest_plan_code, 
            'plot_id' => $this->plot_id,
            'plot_name' => $this->plot_name,
            'pickup_date' => $this->pickup_date,
            'pickup_time' => $this->pickup_time,
            'expected_yield' => $this->expected_yield,
            'actual_yield' => $this->actual_yield,
            'notes' => $this->notes,
            'workers' => $this->workers,
        ];
    }
}
        