<?php

declare(strict_types=1);

namespace App\Application\Actions\Land\Support;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;


class CreateLandAction extends LandSupportAction
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

        // Check permission to create land support
        $scope = Utils::resolveScope($permissions, 'land.support', 'create');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $formData = $this->getFormData();

        // Validate data fields
        $required_fields = [
            'plot_name', 
            'farmer_name', 
            'ownership', 
            'land_records', 
            'province_id',
            //'coordinate_origin_points',
            //'coordinates', 
            'land_area', 
            'address', 
            'soil',
            'maximum_yield',
            'classify',
            'area_24',
            'farmer_user_id',
        ];

        $missing_fields = Utils::validFields($required_fields, $formData);
        if (!empty($missing_fields)) {
            throw new HttpBadRequestException($this->request, "Thiếu trường dữ liệu ".implode(", ", $missing_fields));
        }

        $plot_name = "";
        if (!empty($formData['plot_name'])) {
            $plot_name = htmlspecialchars(trim($formData['plot_name']));
        }

        $farmer_name = "";
        if (!empty($formData['farmer_name'])) {
            $farmer_name = htmlspecialchars(trim($formData['farmer_name']));
        }

        $company_name = "";
        if (!empty($formData['company_name'])) {
            $company_name = htmlspecialchars(trim($formData['company_name']));
        }

        $ownership = "";
        if (!empty($formData['ownership'])) {
            $ownership = htmlspecialchars(trim($formData['ownership']));
        }
        
        $land_records = [];
        if (!empty($formData['land_records']) && is_array($formData['land_records'])) {
            $land_records = $formData['land_records'];
        }

        $province_id = intval($formData['province_id'] ?? 0);
        $country = "Vietnam";

        $coordinate_origin_points = [];
        if (!empty($formData['coordinate_origin_points']) && is_array($formData['coordinate_origin_points'])) {
            $coordinate_origin_points = $formData['coordinate_origin_points'];
        }

        $coordinates = [];
        if (!empty($formData['coordinates']) && is_array($formData['coordinates'])) {
            $coordinates = $formData['coordinates'];
        }
        
        $land_area = 0;
        if (!empty($formData['land_area'])) {
            $land_area = floatval($formData['land_area']);
        }

        $address = "";
        if (!empty($formData['address'])) {
            $address = htmlspecialchars(trim($formData['address']));
        }

        $altitude_above_sea_level = 0;
        if (!empty($formData['altitude_above_sea_level'])) {
            $altitude_above_sea_level = floatval($formData['altitude_above_sea_level']);
        }

        $soil = "";
        if (!empty($formData['soil'])) {
            $soil = htmlspecialchars(trim($formData['soil']));
        }

        $status = "";
        if (!empty($formData['status'])) {
            $status = htmlspecialchars(trim($formData['status']));
        }

        $maximum_yield = 0;
        if (!empty($formData['maximum_yield'])) {
            $maximum_yield = intval($formData['maximum_yield']);
        }

        $classify = "";
        if (!empty($formData['classify'])) {
            $classify = htmlspecialchars(trim($formData['classify']));
        }

        $area_24 = 0;
        if (!empty($formData['area_24'])) {
            $area_24 = floatval($formData['area_24']);
        }

        $notes = "";
        if (!empty($formData['notes'])) {
            $notes = htmlspecialchars(trim($formData['notes']));
        }

        $land_document_detection = 0;
        if (!empty($formData['land_document_detection'])) {
            $land_document_detection = intval($formData['land_document_detection']);
        }

        $zone_id = 0;
        if (!empty($formData['zone_id'])) {
            $zone_id = intval($formData['zone_id']);
        }

        $farmer_user_id = 0;
        if (!empty($formData['farmer_user_id'])) {
            $farmer_user_id = intval($formData['farmer_user_id']);
        }

        $is_vendor = 0;
        if (!empty($formData['is_vendor'])) {
            $is_vendor = 1;
        }

        $farmerInfo = $this->userRepository->findUserOfId($farmer_user_id);
        if (empty($farmerInfo)) {
            throw new HttpBadRequestException($this->request, "Nông hộ không tồn tại. Vui lòng kiểm tra lại");
        }

        if ($farmerInfo->getAccountType() !== 'farmer') {
            throw new HttpBadRequestException($this->request, "Người dùng không phải nông hộ. Vui lòng kiểm tra lại");
        }

        // Validate connection between support user and farmer user
        $data_connection = $this->connectionRepository->findConnectionBetweenUsers($this->auth_data['user_id'], $farmer_user_id, 'accepted');
        if (empty($data_connection)) {
            throw new HttpBadRequestException($this->request, "Bạn chưa có thông tin kết nối với nông hộ này. Vui lòng kết nối và thử lại");
        }

        // Create code
        $land_code = $this->landRepository->generateCode();

        $data_update = [
            "plot_code" => $land_code,
            "plot_name" => $plot_name,
            "farmer_user_id" => $farmer_user_id,
            "farmer_name" => $farmer_name,
            "company_id" => $farmerInfo->getCompanyId() ?? 0,
            "company_name" => $company_name,
            "ownership" => $ownership,
            "land_records" => json_encode($land_records),
            "land_document_detection" => $land_document_detection,
            "province_id" => $province_id,
            "country" => $country,
            "coordinate_origin_points" => json_encode($coordinate_origin_points),
            "coordinates" => json_encode($coordinates),
            "land_area" => $land_area,
            "address" => $address,
            "altitude_above_sea_level" => $altitude_above_sea_level,
            "soil" => $soil,
            "status" => $status,
            "maximum_yield" => $maximum_yield,
            "classify" => $classify,
            "area_24" => $area_24,
            "notes" => $notes,
            "eudr_status" => 0,
            "is_approved" => 0,
            "zone_id" => $zone_id,
            "is_vendor" => $is_vendor,
            "created_by" => $this->auth_data['user_id'],
            "created_at" => date("Y-m-d H:i:s", time()),
        ];
        
        // Create land
        $land = $this->landRepository->createLand($data_update);

        $action = 'create';
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
