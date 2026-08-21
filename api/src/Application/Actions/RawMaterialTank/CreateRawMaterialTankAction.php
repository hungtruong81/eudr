<?php

declare(strict_types=1);

namespace App\Application\Actions\RawMaterialTank;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;


class CreateRawMaterialTankAction extends RawMaterialTankAction
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

        // Check permission to create raw material tanks
        $tankScope = Utils::resolveScope($permissions, 'raw_material_tank', 'create');
        if (empty($tankScope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        // Check permission to view factory
        $factoryScope = Utils::resolveScope($permissions, 'factory', 'view');
        if (empty($factoryScope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập nhà máy");
        }

        $formData = $this->getFormData();

        $validator = new Validator($this->request);

        $validator->validate('raw_material_tank_name', $formData['raw_material_tank_name'] ?? null, 'required|string');
        $validator->validate('factory_id', $formData['factory_id'] ?? null, 'required|integer');
        $validator->validate('tank_type', $formData['tank_type'] ?? null, 'required|in:latex,scrap_rubber,mixed');
        $validator->validate('capacity', $formData['capacity'] ?? null, 'required|numeric|min:0');
        $validator->validate('location', $formData['location'] ?? null, 'string');
        $validator->validate('notes', $formData['notes'] ?? null, 'string');

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
            'raw_material_tank_name' => 'string',
            'factory_id' => 'integer',
            'tank_type' => 'string',
            'capacity' => 'float',
            'location' => 'string',
            'notes' => 'string',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $raw_material_tank_name = $cleanData['raw_material_tank_name'];
        $factory_id = $cleanData['factory_id'];
        $tank_type = $cleanData['tank_type'];
        $capacity = $cleanData['capacity'];
        $location = $cleanData['location'];
        $notes = $cleanData['notes'];

        // Check factory exists
        $factory = $this->factoryRepository->findFactoryOfIdWithPermission(
            $factory_id,
            (int)$this->auth_data['user_id'],
            (string)$factoryScope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($factory)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy nhà máy");
        }

        // Create code
        $raw_material_tank_code = $this->rawMaterialTankRepository->generateCode();

        // Data Raw Material Tank
        $data_update = [
            "raw_material_tank_name" => $raw_material_tank_name,
            "raw_material_tank_code" => $raw_material_tank_code,
            "company_id" => $this->auth_data['company_id'] ?? 0,
            "factory_id" => $factory_id,
            "tank_type" => $tank_type,
            "capacity" => $capacity,
            "location" => $location ?? '',
            "notes" => $notes ?? '',
            "status" => 'active',
            "created_at" => date("Y-m-d H:i:s", time()),
            "created_by" => $this->auth_data['user_id'],
        ];
        
        $rawMaterialTank = $this->rawMaterialTankRepository->createRawMaterialTank($data_update);

        $action = 'create';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'raw_material_tank',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$rawMaterialTank->getId(),
        );

        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['raw_material_tank'] = $rawMaterialTank->jsonSerialize();

        return $this->respondWithData($res_return);
        
    }
}
