<?php

declare(strict_types=1);

namespace App\Application\Actions\Plant;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;


class CreatePlantAction extends PlantAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        // Check Authentication
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);

        // Check permission to create plant
        $plantScope = Utils::resolveScope($permissions, 'plant', 'create');
        if (empty($plantScope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $formData = $this->getFormData();

        // Validate data fields
        $required_fields = ['plot_code', 'crop_type', 'year_of_planting', 'year_of_start_tapping', 'clone_type_of_tree', 'effective_tree_density'];

        $missing_fields = Utils::validFields($required_fields, $formData);

        if (!empty($missing_fields)) {
            throw new HttpBadRequestException($this->request, "Thiếu trường dữ liệu ".implode(", ", $missing_fields));
        }

        $plot_code = "";
        if (!empty($formData['plot_code'])) {
            $plot_code = htmlspecialchars(trim($formData['plot_code']));
        }

        $crop_type = "";
        if (!empty($formData['crop_type'])) {
            $crop_type = htmlspecialchars(trim($formData['crop_type']));
        }

        $year_of_planting = 0;
        if (!empty($formData['year_of_planting'])) {
            $year_of_planting = intval($formData['year_of_planting']);
        }

        $plantation_name = "";
        if (!empty($formData['plantation_name'])) {
            $plantation_name = htmlspecialchars(trim($formData['plantation_name']));
        }

        $expected_harvest = 0;
        if (!empty($formData['expected_harvest'])) {
            $expected_harvest = floatval($formData['expected_harvest']);
        }

        $plant_status = "";
        if (!empty($formData['plant_status'])) {
            $plant_status = htmlspecialchars(trim($formData['plant_status']));
        }

        $date_end_of_planting = NULL;
        if (!empty($formData['date_end_of_planting'])) {
            $date_end_of_planting = htmlspecialchars(trim($formData['date_end_of_planting']));
        }

        $type_of_plantation = "";
        if (!empty($formData['type_of_plantation'])) {
            $type_of_plantation = htmlspecialchars(trim($formData['type_of_plantation']));
        }

        $planting_method = "";
        if (!empty($formData['planting_method'])) {
            $planting_method = htmlspecialchars(trim($formData['planting_method']));
        }

        $planting_distance = "";
        if (!empty($formData['planting_distance'])) {
            $planting_distance = htmlspecialchars(trim($formData['planting_distance']));
        }

        $year_of_start_tapping = 0;
        if (!empty($formData['year_of_start_tapping'])) {
            $year_of_start_tapping = intval($formData['year_of_start_tapping']);
        }
        
        $year_of_upward_tapping = 0;
        if (!empty($formData['year_of_upward_tapping'])) {
            $year_of_upward_tapping = intval($formData['year_of_upward_tapping']);
        }

        $percentage_of_trees_meeting_perimeter_standards = 0;
        if (!empty($formData['percentage_of_trees_meeting_perimeter_standards'])) {
            $percentage_of_trees_meeting_perimeter_standards = floatval($formData['percentage_of_trees_meeting_perimeter_standards']);
        }

        $denity_of_tapping_tree = 0;
        if (!empty($formData['denity_of_tapping_tree'])) {
            $denity_of_tapping_tree = intval($formData['denity_of_tapping_tree']);
        }

        $tapping_method = "";
        if (!empty($formData['tapping_method'])) {
            $tapping_method = htmlspecialchars(trim($formData['tapping_method']));
        }

        $annual_yield = 0;
        if (!empty($formData['annual_yield'])) {
            $annual_yield = intval($formData['annual_yield']);
        }

        $clone_type_of_tree = "";
        if (!empty($formData['clone_type_of_tree'])) {
            $clone_type_of_tree = htmlspecialchars(trim($formData['clone_type_of_tree']));
        }

        $effective_tree_density = 0;
        if (!empty($formData['effective_tree_density'])) {
            $effective_tree_density = intval($formData['effective_tree_density']);
        }

        $standard_deviation = 0;
        if (!empty($formData['standard_deviation'])) {
            $standard_deviation = floatval($formData['standard_deviation']);
        }

        $production_24 = 0;
        if (!empty($formData['production_24'])) {
            $production_24 = floatval($formData['production_24']);
        }

        // Check if plot exists and permission to access
        $landScope = Utils::resolveScope($permissions, 'land', 'view');
        if (empty($landScope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }
        
        $land = $this->landRepository->findLandOfCodeWithPermission($plot_code, $this->auth_data['user_id'], (string)$landScope);
        if (empty($land)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy lô đất");
        }

        // Create code
        $plant_code = $this->plantRepository->generateCode();

        // Data Land
        $data_update = [
            "plant_code" => $plant_code,
            "plot_id" => $land->getId(),
            "company_id" => $this->auth_data['company_id'] ?? 0,
            "crop_type" => $crop_type,
            "year_of_planting" => $year_of_planting,
            "plantation_name" => $plantation_name,
            "expected_harvest" => $expected_harvest,
            "plant_status" => $plant_status,
            "date_end_of_planting" => $date_end_of_planting,
            "type_of_plantation" => $type_of_plantation,
            "planting_method" => $planting_method,
            "planting_distance" => $planting_distance,
            "year_of_start_tapping" => $year_of_start_tapping,
            "year_of_upward_tapping" => $year_of_upward_tapping,
            "percentage_of_trees_meeting_perimeter_standards" => $percentage_of_trees_meeting_perimeter_standards,
            "denity_of_tapping_tree" => $denity_of_tapping_tree,
            "tapping_method" => $tapping_method,
            "annual_yield" => $annual_yield,
            "clone_type_of_tree" => $clone_type_of_tree,
            "effective_tree_density" => $effective_tree_density,
            "standard_deviation" => $standard_deviation,
            "production_24" => $production_24,
            "created_at" => date("Y-m-d H:i:s", time()),
            "created_by" => $this->auth_data['user_id'],
        ];

        $plant = $this->plantRepository->createPlant($data_update);

        $action = 'create';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'plant',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$plant->getId(),
        );

        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['plant'] = $plant->jsonSerialize();

        return $this->respondWithData($res_return);
        
    }
}
