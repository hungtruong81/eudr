<?php

declare(strict_types=1);

namespace App\Application\Actions\Driver;

use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\Driver\DriverErrorException;
use App\Application\Utility\Utils;
use Slim\Exception\HttpBadRequestException;
use App\Application\Utility\Validator;


class UpdateDriverAction extends DriverAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new DriverErrorException("Thiếu quyền truy cập", 113);
        }

        // Check permission
        /*
        $permission_status = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'vehicle', 'update');

        if (empty($permission_status)) {
            throw new DriverErrorException("Thiếu quyền truy cập", 113);
        }
        */

        $driver_code = $this->resolveArg('code');

        $driver = $this->driverRepository->findDriverOfCode($driver_code);
        if (empty($driver)) {
            throw new DriverErrorException("Không tìm thấy tài xế", 101);
        }

        $formData = $this->getFormData();

        $validator = new Validator($this->request);

        $validator->validate('driver_name', $formData['driver_name'] ?? null, 'required|string');
        $validator->validate('license_number', $formData['license_number'] ?? null, 'required|string');
        $validator->validate('vehicle_type', $formData['vehicle_type'] ?? null, 'required|string');
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
            'driver_name' => 'string',
            'license_number' => 'string',
            'vehicle_type' => 'string',
            'manufacture_year' => 'integer',
            'license_plate' => 'string',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $driver_name = $cleanData['driver_name'];
        $license_number = $cleanData['license_number'];
        $vehicle_type = $cleanData['vehicle_type'];
        $manufacture_year = $cleanData['manufacture_year'];
        $license_plate = $cleanData['license_plate'];

        $data_update = [
            'driver_name' => $driver_name,
            'brand' => $brand,
            'type' => $type,
            'manufacture_year' => $manufacture_year,
            'license_plate' => $license_plate,
            'updated_at' => date("Y-m-d H:i:s", time()),
            'updated_by' => $this->auth_data['user_id'],
        ];

        $driver = $this->driverRepository->updateDriver($driver->getId(), $data_update);

        $action = 'update';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'driver',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$driver->getId(),
        );

        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['driver'] = $driver->jsonSerialize();

        return $this->respondWithData($res_return);
        
    }
}
