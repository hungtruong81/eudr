<?php

declare(strict_types=1);

namespace App\Application\Actions\Driver;

use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\Driver\DriverErrorException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Slim\Exception\HttpBadRequestException;


class CreateDriverAction extends DriverAction
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
        $user_has_permission = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'vehicle', 'create');
        if (empty($user_has_permission)) {
            throw new DriverErrorException("Thiếu quyền truy cập", 113);
        }
        */

        $formData = $this->getFormData();



        $validator = new Validator($this->request);

        $validator->validate('driver_name', $formData['driver_name'] ?? null, 'required|string');
        $validator->validate('license_number', $formData['license_number'] ?? null, 'required|string');
        $validator->validate('phone_number', $formData['phone_number'] ?? null, 'required|string');
        $validator->validate('email', $formData['email'] ?? null, 'required|email');
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

        $driver_name = $cleanData['driver_name'];
        $license_number = $cleanData['license_number'];
        $phone_number = $cleanData['phone_number'];
        $email = $cleanData['email'];
        $license_plate = $cleanData['license_plate'];

        // Create code
        $driver_code = $this->driverRepository->generateCode();

        // Data Driver
        $data_update = [
            "driver_code" => $driver_code,
            "driver_name" => $driver_name,
            "license_number" => $license_number,
            "phone_number" => $phone_number,
            "email" => $email,
            "license_plate" => $license_plate,
            "created_at" => date("Y-m-d H:i:s", time()),
            "created_by" => $this->auth_data['user_id'],
        ];

        $driver = $this->driverRepository->createDriver($data_update);

        $action = 'create';
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
