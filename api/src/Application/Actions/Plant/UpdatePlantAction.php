<?php

declare(strict_types=1);

namespace App\Application\Actions\Plant;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;


class UpdatePlantAction extends PlantAction
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

        // Check permission to update plant
        $scope = Utils::resolveScope($permissions, 'plant', 'update');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $plant_code = addslashes(trim((string)$this->resolveArg('code')));
   
        $plant = $this->plantRepository->findPlantOfCodeWithPermission(
            $plant_code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($plant)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy thông tin cây trồng");
        }
        
        $formData = $this->getFormData();

        $data_update = [];

        $plot_code = "";
        if (!empty($formData['plot_code'])) {
            $plot_code = htmlspecialchars(trim($formData['plot_code']));
        }

        $crop_type = "";
        if (!empty($formData['crop_type'])) {
            $crop_type = htmlspecialchars(trim($formData['crop_type']));
            $data_update["crop_type"] = $crop_type;
        }

        $year_of_planting = 0;
        if (!empty($formData['year_of_planting'])) {
            $year_of_planting = intval($formData['year_of_planting']);
            $data_update["year_of_planting"] = $year_of_planting;
        }

        $plantation_name = "";
        if (!empty($formData['plantation_name'])) {
            $plantation_name = htmlspecialchars(trim($formData['plantation_name']));
            $data_update["plantation_name"] = $plantation_name;
        }

        $expected_harvest = 0;
        if (!empty($formData['expected_harvest'])) {
            $expected_harvest = floatval($formData['expected_harvest']);
            $data_update["expected_harvest"] = $expected_harvest;
        }

        $plant_status = "";
        if (!empty($formData['plant_status'])) {
            $plant_status = htmlspecialchars(trim($formData['plant_status']));
            $data_update["plant_status"] = $plant_status;
        }

        $date_end_of_planting = "";
        if (!empty($formData['date_end_of_planting'])) {
            $date_end_of_planting = htmlspecialchars(trim($formData['date_end_of_planting']));
            $data_update["date_end_of_planting"] = $date_end_of_planting;
        }

        $type_of_plantation = "";
        if (!empty($formData['type_of_plantation'])) {
            $type_of_plantation = htmlspecialchars(trim($formData['type_of_plantation']));
            $data_update["type_of_plantation"] = $type_of_plantation;
        }

        $planting_method = "";
        if (!empty($formData['planting_method'])) {
            $planting_method = htmlspecialchars(trim($formData['planting_method']));
            $data_update["planting_method"] = $planting_method;
        }

        $planting_distance = "";
        if (!empty($formData['planting_distance'])) {
            $planting_distance = htmlspecialchars(trim($formData['planting_distance']));
            $data_update["planting_distance"] = $planting_distance;
        }

        $year_of_start_tapping = 0;
        if (!empty($formData['year_of_start_tapping'])) {
            $year_of_start_tapping = intval($formData['year_of_start_tapping']);
            $data_update["year_of_start_tapping"] = $year_of_start_tapping;
        }
        
        $year_of_upward_tapping = 0;
        if (!empty($formData['year_of_upward_tapping'])) {
            $year_of_upward_tapping = intval($formData['year_of_upward_tapping']);
            $data_update["year_of_upward_tapping"] = $year_of_upward_tapping;
        }

        $percentage_of_trees_meeting_perimeter_standards = 0;
        if (!empty($formData['percentage_of_trees_meeting_perimeter_standards'])) {
            $percentage_of_trees_meeting_perimeter_standards = floatval($formData['percentage_of_trees_meeting_perimeter_standards']);
            $data_update["percentage_of_trees_meeting_perimeter_standards"] = $percentage_of_trees_meeting_perimeter_standards;
        }

        $denity_of_tapping_tree = 0;
        if (!empty($formData['denity_of_tapping_tree'])) {
            $denity_of_tapping_tree = intval($formData['denity_of_tapping_tree']);
            $data_update["denity_of_tapping_tree"] = $denity_of_tapping_tree;
        }

        $tapping_method = "";
        if (!empty($formData['tapping_method'])) {
            $tapping_method = htmlspecialchars(trim($formData['tapping_method']));
            $data_update["tapping_method"] = $tapping_method;
        }

        $annual_yield = 0;
        if (!empty($formData['annual_yield'])) {
            $annual_yield = intval($formData['annual_yield']);
            $data_update["annual_yield"] = $annual_yield;
        }

        $clone_type_of_tree = "";
        if (!empty($formData['clone_type_of_tree'])) {
            $clone_type_of_tree = htmlspecialchars(trim($formData['clone_type_of_tree']));
            $data_update["clone_type_of_tree"] = $clone_type_of_tree;
        }

        $effective_tree_density = 0;
        if (!empty($formData['effective_tree_density'])) {
            $effective_tree_density = intval($formData['effective_tree_density']);
            $data_update["effective_tree_density"] = $effective_tree_density;
        }

        $standard_deviation = 0;
        if (!empty($formData['standard_deviation'])) {
            $standard_deviation = floatval($formData['standard_deviation']);
            $data_update["standard_deviation"] = $standard_deviation;
        }

        $production_24 = 0;
        if (!empty($formData['production_24'])) {
            $production_24 = floatval($formData['production_24']);
            $data_update["production_24"] = $production_24;
        }

        // Check if plot exists
        if(!empty($plot_code)) {
            $landScope = Utils::resolveScope($permissions, 'land', 'view');
            if (empty($landScope)) {
                throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
            }
            $land = $this->landRepository->findLandOfCodeWithPermission($plot_code, $this->auth_data['user_id'], (string)$landScope);
            if (empty($land)) {
                throw new HttpNotFoundException($this->request, "Không tìm thấy thông tin lô đất");
            }
            $data_update["plot_id"] = $land->getId();
        }

        if(empty($data_update)) {
            throw new HttpBadRequestException($this->request, "Không có trường nào để cập nhật");
        }

        $data_update["updated_at"] = date("Y-m-d H:i:s", time());
        $data_update["updated_by"] = $this->auth_data['user_id'];
    
        $plant = $this->plantRepository->updatePlantWithPermission(
            $plant->getId(),
            $data_update,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        $action = 'update';
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
