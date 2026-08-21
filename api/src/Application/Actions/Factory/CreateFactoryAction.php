<?php

declare(strict_types=1);

namespace App\Application\Actions\Factory;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class CreateFactoryAction extends FactoryAction
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

        // Check permission to create factory
        $scope = Utils::resolveScope($permissions, 'factory', 'create');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $formData = $this->getFormData();

        $validator = new Validator($this->request);

        $validator->validate('factory_name', $formData['factory_name'] ?? null, 'required|string');
        $validator->validate('address', $formData['address'] ?? null, 'string');
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
            'factory_name' => 'string',
            'address' => 'string',
            'notes' => 'string',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $factory_name = $cleanData['factory_name'];
        $address = $cleanData['address'];
        $notes = $cleanData['notes'] ?? "";

        // Create code
        $factory_code = $this->factoryRepository->generateCode();

        // Data Factory
        $data_update = [
            "company_id" => $this->auth_data['company_id'] ?? 0,
            "factory_code" => $factory_code,
            "factory_name" => $factory_name,
            "address" => $address,
            "notes" => $notes,
            "created_at" => date("Y-m-d H:i:s", time()),
            "created_by" => $this->auth_data['user_id'],
        ];

        $factory = $this->factoryRepository->createFactory($data_update);

        $action = 'create';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'factory',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$factory->getId(),
        );

        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['factory'] = $factory->jsonSerialize();

        return $this->respondWithData($res_return);
        
    }
}
