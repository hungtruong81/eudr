<?php

declare(strict_types=1);

namespace App\Application\Actions\Vehicle;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;


class UpdateVehicleAction extends VehicleAction
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

        // Load permissions once and resolve scope
        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);

        // Check permission to update vehicles
        $scope = Utils::resolveScope($permissions, 'vehicle', 'update');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }
        
        $vehicle_code = addslashes(trim((string)$this->resolveArg('code')));

        $vehicle = $this->vehicleRepository->findVehicleOfCodeWithPermission(
            $vehicle_code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($vehicle)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy phương tiện");
        }

        $formData = $this->getFormData();

        $validator = new Validator($this->request);

        $validator->validate('vehicle_name', $formData['vehicle_name'] ?? null, 'required|string');
        $validator->validate('brand', $formData['brand'] ?? null, 'required|string');
        $validator->validate('type', $formData['type'] ?? null, 'required|string');
        $validator->validate('manufacture_year', $formData['manufacture_year'] ?? null, 'required|integer');
        $validator->validate('license_plate', $formData['license_plate'] ?? null, 'required|string');

        if ($validator->hasErrors()) {
            $errorMessages = [];
            $errors = $validator->getErrors();
            if (is_array($errors)) {
                foreach ($errors as $field => $fieldErrors) {
                    if (is_array($fieldErrors)) {
                        $errorMessages[] = implode(', ', $fieldErrors);
                    } else {
                        $errorMessages[] = (string)$fieldErrors;
                    }
                }
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errorMessages));
        }

        // Sanitize and extract data
        $sanitizeRules = [
            'vehicle_name' => 'string',
            'brand' => 'string',
            'type' => 'string',
            'manufacture_year' => 'integer',
            'license_plate' => 'string',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $vehicle_name = $cleanData['vehicle_name'];
        $brand = $cleanData['brand'];
        $type = $cleanData['type'];
        $manufacture_year = $cleanData['manufacture_year'];
        $license_plate = $cleanData['license_plate'];

        $data_update = [
            'vehicle_name' => $vehicle_name,
            'brand' => $brand,
            'type' => $type,
            'manufacture_year' => $manufacture_year,
            'license_plate' => $license_plate,
            'updated_at' => date("Y-m-d H:i:s", time()),
            'updated_by' => $this->auth_data['user_id'],
        ];

        $vehicle = $this->vehicleRepository->updateVehicleWithPermission(
            $vehicle->getId(),
            $data_update,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        $action = 'update';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'vehicle',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$vehicle->getId(),
        );

        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['vehicle'] = $vehicle->jsonSerialize();

        return $this->respondWithData($res_return);
        
    }
}
