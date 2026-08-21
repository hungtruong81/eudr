<?php

declare(strict_types=1);

namespace App\Domain\Land;

use JsonSerializable;

class Land implements JsonSerializable
{
    /**
     * @var int|null
     */
    private $plot_id;
    /**
     * @var string
     */
    private $plot_code;
    /**
     * @var string
     */
    private $plot_name;
    /**
     * @var int|null
     */
    private $farmer_user_id;
    /**
     * @var string
     */
    private $farmer_name;
    /**
     * @var string
     */
    private $phone;
    /**
     * @var string
     */
    private $email;
    /**
     * @var string
     */
    private $register_type;
    /**
     * @var string
     */
    private $company_name;
    /**
     * @var string
     */
    private $ownership;
    /**
     * @var string
     */
    private $land_records;
    /**
     * @var string
     */
    private $land_document_detection;
    /**
     * @var number
     */
    private $province_id;
    /**
     * @var string
     */
    private $province_name;
    /**
     * @var string
     */
    private $country;
    /**
     * @var string
     */
    private $coordinates;
    /**
     * @var string
     */
    private $coordinate_origin_points;
    /**
     * @var number
     */
    private $land_area;
    /**
     * @var string
     */
    private $address;
    /**
     * @var string
     */
    private $plant_type;
    /**
     * @var number
     */
    private $altitude_above_sea_level;
    /**
     * @var string
     */
    private $soil;
    /**
     * @var string
     */
    private $status;
    /**
     * @var number
     */
    private $maximum_yield;
    /**
     * @var string
     */
    private $classify;
    /**
     * @var datetime
     */
    private $created_at;
    /**
     * @var int
     */
    private $created_by;
    /**
     * @var datetime
     */
    private $updated_at;
    /**
     * @var int|null
     */
    private $updated_by;
    /**
     * @var datetime
     */
    private $approved_at;
    /**
     * @var int|null
     */
    private $approved_by;
    /**
     * @var float
     */
    private $area_24;
    /**
     * @var string
     */
    private $notes;
    /**
     * @var int|null
     */
    private $eudr_status;
    /**
     * @var int|null
     */
    private $is_approved;
    /**
     * @var int|null
     */
    private $zone_id;
    /**
     * @var string|null
     */
    private $zone_name;
    /**
     * @var string|null
     */
    private $crop_type;
    /**
     * @var string|null
     */
    private $year_of_planting;
    /**
     * @var string|null
     */
    private $plantation_name;

    /**
     * @param int|null  $plot_id
     * @param array    $data
     */
    public function __construct(?int $plot_id, array $data)
    {
        $this->plot_id = $plot_id;
        $this->plot_code = $data['plot_code'] ?? '';
        $this->plot_name = $data['plot_name'] ?? '';
        $this->farmer_user_id = $data['farmer_user_id'] ?? null;
        $this->farmer_name = $data['farmer_name'] ?? '';
        $this->phone = $data['phone'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->register_type = $data['register_type'] ?? '';
        $this->company_name = $data['company_name'] ?? '';
        $this->ownership = $data['ownership'] ?? '';
        $this->land_records = !empty($data['land_records']) ? json_decode($data['land_records'], true) : [];
        $this->land_document_detection = $data['land_document_detection'] ?? '';
        $this->province_id = $data['province_id'] ?? 0;
        $this->province_name = $data['province_name'] ?? '';
        $this->country = $data['country'] ?? '';
        $this->coordinates = !empty($data['coordinates']) ? json_decode($data['coordinates'], true) : [];
        $this->coordinate_origin_points = !empty($data['coordinate_origin_points']) ? json_decode($data['coordinate_origin_points'], true) : [];
        $this->land_area = $data['land_area'] ?? 0;
        $this->address = $data['address'] ?? '';
        $this->plant_type = $data['plant_type'] ?? '';
        $this->altitude_above_sea_level = $data['altitude_above_sea_level'] ?? 0;
        $this->soil = $data['soil'] ?? '';
        $this->status = $data['status'] ?? '';
        $this->maximum_yield = $data['maximum_yield'] ?? 0;
        $this->classify = $data['classify'] ?? '';
        $this->created_at = $data['created_at'] ?? NULL;
        $this->created_by = $data['created_by'] ?? 0;
        $this->updated_at = $data['updated_at'] ?? NULL;
        $this->updated_by = $data['updated_by'] ?? 0;
        $this->approved_at = $data['approved_at'] ?? NULL;
        $this->approved_by = $data['approved_by'] ?? 0;
        $this->area_24 = $data['area_24'] ?? 0;
        $this->notes = $data['notes'] ?? '';
        $this->eudr_status = $data['eudr_status'] ?? 0;
        $this->is_approved = $data['is_approved'] ?? 0;
        $this->zone_id = $data['zone_id'] ?? 0;
        $this->zone_name = $data['zone_name'] ?? '';
        $this->crop_type = $data['crop_type'] ?? null;
        $this->year_of_planting = $data['year_of_planting'] ?? null;
        $this->plantation_name = $data['plantation_name'] ?? null;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->plot_id;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->plot_code;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->plot_name;
    }

    /**
     * @return int|null
     */
    public function getCreatedBy(): ?int
    {
        return $this->created_by;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'plot_id' => $this->plot_id,
            'plot_code' => $this->plot_code,
            'plot_name' => $this->plot_name,
            'farmer_user_id' => $this->farmer_user_id,
            'farmer_name' => $this->farmer_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'register_type' => $this->register_type,
            'company_name' => $this->company_name,
            'ownership' => $this->ownership,
            'land_records' => $this->land_records,
            'land_document_detection' => $this->land_document_detection,
            'province_id' => $this->province_id,
            'province_name' => $this->province_name,
            'country' => $this->country,
            'coordinates' => $this->coordinates,
            'coordinate_origin_points' => $this->coordinate_origin_points,
            'land_area' => $this->land_area,
            'address' => $this->address,
            'plant_type' => $this->plant_type,
            'altitude_above_sea_level' => $this->altitude_above_sea_level,
            'soil' => $this->soil,
            'status' => $this->status,
            'maximum_yield' => $this->maximum_yield,
            'classify' => $this->classify,
            'created_at' => $this->created_at,
            'created_by' => $this->created_by,
            'updated_at' => $this->updated_at,
            'updated_by' => $this->updated_by,
            'approved_at' => $this->approved_at,
            'approved_by' => $this->approved_by,
            'area_24' => $this->area_24,
            'notes' => $this->notes,
            'eudr_status' => $this->eudr_status,
            'is_approved' => $this->is_approved,
            'zone_id' => $this->zone_id,
            'zone_name' => $this->zone_name,
            'crop_type' => $this->crop_type,
            'year_of_planting' => $this->year_of_planting,
            'plantation_name' => $this->plantation_name,
        ];
    }
}
