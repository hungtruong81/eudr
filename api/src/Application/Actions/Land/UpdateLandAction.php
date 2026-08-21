<?php

declare(strict_types=1);

namespace App\Application\Actions\Land;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;


class UpdateLandAction extends LandAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        // Check authentication
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);

        // Check permission
        $scope = Utils::resolveScope($permissions, 'land', 'update');

        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $land_code = addslashes(trim((string)$this->resolveArg('code')));
   
        $land = $this->landRepository->findLandOfCodeWithPermission(
            $land_code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($land)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy thông tin mảnh đất");
        }

        $formData = $this->getFormData();

        // Validate data fields
        /*
        $required_fields = [
            'plot_name', 
            'farmer_name', 
            'ownership', 
            'land_records', 
            'province_id', 
            'coordinates', 
            'land_area', 
            'address', 
            'soil',
            'maximum_yield',
            'classify',
            'area_24',
        ];

        $missing_fields = Utils::validFields($required_fields, $formData);
        if (!empty($missing_fields)) {
            throw new LandErrorException("MISSING ".implode(", ", $missing_fields), 101);
        }
        */

        $data_update = [];

        $plot_name = "";
        if (!empty($formData['plot_name'])) {
            $plot_name = htmlspecialchars(trim($formData['plot_name']));
            $data_update["plot_name"] = $plot_name;
        }

        $farmer_name = "";
        if (!empty($formData['farmer_name'])) {
            $farmer_name = htmlspecialchars(trim($formData['farmer_name']));
            $data_update["farmer_name"] = $farmer_name;
        }

        $company_name = "";
        if (!empty($formData['company_name'])) {
            $company_name = htmlspecialchars(trim($formData['company_name']));
            $data_update["company_name"] = $company_name;
        }

        $ownership = "";
        if (!empty($formData['ownership'])) {
            $ownership = htmlspecialchars(trim($formData['ownership']));
            $data_update["ownership"] = $ownership;
        }
        
        $land_records = [];
        if (!empty($formData['land_records']) && is_array($formData['land_records'])) {
            $land_records = $formData['land_records'];
            $data_update["land_records"] = json_encode($land_records);
        }

        $land_document_detection = intval($formData['land_document_detection'] ?? 0);
        if(!empty($land_document_detection)){
            $data_update["land_document_detection"] = $land_document_detection;
        }

        $province_id = intval($formData['province_id'] ?? 0);
        if(!empty($province_id)){
            $data_update["province_id"] = $province_id;
        }

        $country = "Vietnam";

        $coordinate_origin_points = [];
        if (!empty($formData['coordinate_origin_points']) && is_array($formData['coordinate_origin_points'])) {
            $coordinate_origin_points = $formData['coordinate_origin_points'];
            $data_update["coordinate_origin_points"] = json_encode($coordinate_origin_points);
        }

        $coordinates = [];
        if (!empty($formData['coordinates']) && is_array($formData['coordinates'])) {
            $coordinates = $formData['coordinates'];
            $data_update["coordinates"] = json_encode($coordinates);
        }
        
        $land_area = 0;
        if (!empty($formData['land_area'])) {
            $land_area = floatval($formData['land_area']);
            $data_update["land_area"] = $land_area;
        }

        $address = "";
        if (!empty($formData['address'])) {
            $address = htmlspecialchars(trim($formData['address']));
            $data_update["address"] = $address;
        }

        $altitude_above_sea_level = 0;
        if (!empty($formData['altitude_above_sea_level'])) {
            $altitude_above_sea_level = floatval($formData['altitude_above_sea_level']);
            $data_update["altitude_above_sea_level"] = $altitude_above_sea_level;
        }

        $soil = "";
        if (!empty($formData['soil'])) {
            $soil = htmlspecialchars(trim($formData['soil']));
            $data_update["soil"] = $soil;
        }

        $status = "";
        if (!empty($formData['status'])) {
            $status = htmlspecialchars(trim($formData['status']));
            $data_update["status"] = $status;
        }

        $maximum_yield = 0;
        if (!empty($formData['maximum_yield'])) {
            $maximum_yield = intval($formData['maximum_yield']);
            $data_update["maximum_yield"] = $maximum_yield;
        }

        $classify = "";
        if (!empty($formData['classify'])) {
            $classify = htmlspecialchars(trim($formData['classify']));
            $data_update["classify"] = $classify;
        }

        $area_24 = 0;
        if (!empty($formData['area_24'])) {
            $area_24 = floatval($formData['area_24']);
            $data_update["area_24"] = $area_24;
        }

        $notes = "";
        if (!empty($formData['notes'])) {
            $notes = htmlspecialchars(trim($formData['notes']));
            $data_update["notes"] = $notes;
        }

        $zone_id = 0;
        if (!empty($formData['zone_id'])) {
            $zone_id = intval($formData['zone_id']);
            $data_update["zone_id"] = $zone_id;
        }

        if(empty($data_update)) {
            throw new HttpBadRequestException($this->request, "Không có dữ liệu để cập nhật");
        }

        // if(!empty($coordinates)) {
        //     // check duplicate coordinates
        //     $check_duplicate = $this->landRepository->checkDuplicateCoordinates($coordinates, $tolerance = 0.000001, $land->getId());
        //     if ($check_duplicate) {
        //         throw new HttpBadRequestException($this->request, "Tọa độ bị trùng lặp, đã có trong hệ thống");
        //     }

        // }
        
        // Update land
        $data_update["updated_at"] = date("Y-m-d H:i:s", time());
        $data_update["updated_by"] = $this->auth_data['user_id'];
        
        $land = $this->landRepository->updateLandWithPermission(
            $land->getId(),
            $data_update,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        $action = 'update';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'land',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$land->getId(),
        );

        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['land'] = $land->jsonSerialize();
        if(!empty($res_return['land']['land_records'])) {
            $res_return['land']['land_records'] = $this->fileRepository->mapFileIdsToMap($res_return['land']['land_records']);
        }
        if (!empty($res_return['land']['land_document_detection'])) {
            $res_return['land']['land_document_detection'] = $this->settings->get('url_cdn') . '/' . ltrim($res_return['land']['land_document_detection'], '/');
        }

        return $this->respondWithData($res_return);
        

    }
}
