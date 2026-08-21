<?php

declare(strict_types=1);

namespace App\Domain\Harvest;

use JsonSerializable;

class HarvestResult implements JsonSerializable
{
    /**
     * @var int|null
     */
    private $harvest_result_id;
    /**
     * @var string
     */
    private $harvest_result_code;
    /**
     * @var int
     */
    private $harvest_plan_id;
    /**
     * @var int
     */
    private $harvest_schedule_id;
    /**
     * @var int
     */
    private $worker_id;
    /**
     * @var date
     */
    private $created_at;
    /**
     * @var int
     */
    private $created_by;
    /**
     * @var float
     */
    private $actual_yield;
    /**
     * @var string
     */
    private $notes;


    /**
     * @param int|null  $harvest_result_id
     * @param array    $data
     */
    public function __construct(?int $harvest_result_id, array $data)
    {
        $this->harvest_result_id = $harvest_result_id;
        $this->harvest_result_code = $data['harvest_result_code'] ?? '';
        $this->harvest_plan_id = $data['harvest_plan_id'] ?? 0;
        $this->harvest_schedule_id = $data['harvest_schedule_id'] ?? 0;
        $this->worker_id = $data['worker_id'] ?? 0;
        $this->created_at = $data['created_at'] ?? '';
        $this->created_by = $data['created_by'] ?? 0;
        $this->actual_yield = $data['actual_yield'] ?? 0.0;
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
        return $this->harvest_result_code;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'harvest_result_id' => $this->harvest_result_id,
            'harvest_result_code' => $this->harvest_result_code,
            'harvest_plan_id' => $this->harvest_plan_id,
            'harvest_schedule_id' => $this->harvest_schedule_id,
            'worker_id' => $this->worker_id,
            'created_at' => $this->created_at,
            'created_by' => $this->created_by,
            'actual_yield' => $this->actual_yield,
        ];
    }
}
        