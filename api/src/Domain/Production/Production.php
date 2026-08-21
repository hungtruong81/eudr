<?php

declare(strict_types=1);

namespace App\Domain\Production;

use JsonSerializable;

class Production implements JsonSerializable
{
    /**
     * @var int|null
    */
    private $production_id;
    /**
     * @var string
    */
    private $plant_code;
    /**
     * @var string
    */
    private $plot_code;
    /**
     * @var string
    */
    private $plot_name;
    /**
     * @var string
    */
    private $crop_type;
    /**
     * @var int
    */
    private $year_of_planting;
    /**
     * @var string
    */
    private $plantation_name;
    /**
     * @var float
    */
    private $expected_harvest;
    /**
     * @var string
    */
    private $plant_status;
    /**
     * @var date
    */
    private $date_end_of_planting;
    /**
     * @var string
    */
    private $type_of_plantation;
    /**
     * @var string
    */
    private $planting_method;
    /**
     * @var string
    */
    private $planting_distance;
    /**
     * @var int
    */
    private $year_of_start_tapping;
    /**
     * @var int
    */
    private $year_of_upward_tapping;
    /**
     * @var float
    */
    private $percentage_of_trees_meeting_perimeter_standards;
    /**
     * @var int
    */
    private $denity_of_tapping_tree;
    /**
     * @var string
    */
    private $tapping_method;
    /**
     * @var int
    */
    private $annual_yield;
    /**
     * @var string
    */
    private $clone_type_of_tree;
    /**
     * @var int
    */
    private $effective_tree_density;
    /**
     * @var float
    */
    private $standard_deviation;
    /**
     * @var float
    */
    private $production_24;
    /**
     * @var date
     */
    private $created_at;

    /**
     * @param int|null  $plant_id
     * @param array    $data
     */
    public function __construct(?int $production_id, array $data)
    {
        $this->production_id = $production_id;
        $this->plant_code = $data['plant_code'] ?? '';
        $this->plot_code = $data['plot_code'] ?? '';
        $this->plot_name = $data['plot_name'] ?? '';
        $this->crop_type = $data['crop_type'] ?? '';
        $this->year_of_planting = $data['year_of_planting'] ?? 0;
        $this->plantation_name = $data['plantation_name'] ?? '';
        $this->expected_harvest = $data['expected_harvest'] ?? 0;
        $this->plant_status = $data['plant_status'] ?? '';
        $this->date_end_of_planting = $data['date_end_of_planting'] ?? '';
        $this->type_of_plantation = $data['type_of_plantation'] ?? '';
        $this->planting_method = $data['planting_method'] ?? '';
        $this->planting_distance = $data['planting_distance'] ?? '';
        $this->year_of_start_tapping = $data['year_of_start_tapping'] ?? 0;
        $this->year_of_upward_tapping = $data['year_of_upward_tapping'] ?? 0;
        $this->percentage_of_trees_meeting_perimeter_standards = $data['percentage_of_trees_meeting_perimeter_standards'] ?? 0;
        $this->denity_of_tapping_tree = $data['denity_of_tapping_tree'] ?? 0;
        $this->tapping_method = $data['tapping_method'] ?? '';
        $this->annual_yield = $data['annual_yield'] ?? 0;
        $this->clone_type_of_tree = $data['clone_type_of_tree'] ?? '';
        $this->effective_tree_density = $data['effective_tree_density'] ?? 0;
        $this->standard_deviation = $data['standard_deviation'] ?? 0;
        $this->production_24 = $data['production_24'] ?? 0;
        $this->created_at = $data['created_at'] ?? '';
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->production_id;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->plant_code;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->plantation_name;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'production_id' => $this->production_id,
            'plant_code' => $this->plant_code,
            'plot_code' => $this->plot_code,
            'plot_name' => $this->plot_name,
            'crop_type' => $this->crop_type,
            'year_of_planting' => $this->year_of_planting,
            'plantation_name' => $this->plantation_name,
            'expected_harvest' => $this->expected_harvest,
            'plant_status' => $this->plant_status,
            'date_end_of_planting' => $this->date_end_of_planting,
            'type_of_plantation' => $this->type_of_plantation,
            'planting_method' => $this->planting_method,
            'planting_distance' => $this->planting_distance,
            'year_of_start_tapping' => $this->year_of_start_tapping,
            'year_of_upward_tapping' => $this->year_of_upward_tapping,
            'percentage_of_trees_meeting_perimeter_standards' => $this->percentage_of_trees_meeting_perimeter_standards,
            'denity_of_tapping_tree' => $this->denity_of_tapping_tree,
            'tapping_method' => $this->tapping_method,
            'annual_yield' => $this->annual_yield,
            'clone_type_of_tree' => $this->clone_type_of_tree,
            'effective_tree_density' => $this->effective_tree_density,
            'standard_deviation' => $this->standard_deviation,
            'production_24' => $this->production_24,
            'created_at' => $this->created_at,
        ];
    }
}
