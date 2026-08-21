<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionCuttingMachine;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

class ListProductionCuttingMachineAction extends ProductionCuttingMachineAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'production_cutting_machine', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $formData = $this->request->getQueryParams();
        $validator = new Validator($this->request);

        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('search', $formData['search'] ?? null, 'string');
        $validator->validate('cutting_machine_code', $formData['cutting_machine_code'] ?? null, 'string');
        $validator->validate('cutting_machine_name', $formData['cutting_machine_name'] ?? null, 'string');
        $validator->validate('status', $formData['status'] ?? null, 'string|in:available,in_use,maintenance,all');
        $validator->validate('factory_id', $formData['factory_id'] ?? null, 'integer|min:1');

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

        $sanitizeRules = [
            'page' => 'integer',
            'limit' => 'integer',
            'search' => 'string',
            'cutting_machine_code' => 'string',
            'cutting_machine_name' => 'string',
            'status' => 'string',
            'factory_id' => 'integer',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $params = [
            'page' => $cleanData['page'],
            'page_limit' => $cleanData['limit'],
            'user_id' => $this->auth_data['user_id'],
            'search' => $cleanData['search'] ?? '',
            'cutting_machine_code' => $cleanData['cutting_machine_code'] ?? '',
            'cutting_machine_name' => $cleanData['cutting_machine_name'] ?? '',
            'status' => $cleanData['status'] ?? 'all',
            'factory_id' => $cleanData['factory_id'] ?? 0,
        ];

        $production_cutting_machines = $this->productionCuttingMachineRepository->findAll(
            $params,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        $res_return = ['result' => 'success'];
        $res_return['data'] = $production_cutting_machines;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }
}
