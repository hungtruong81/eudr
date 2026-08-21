<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionCuttingMachine;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

class ListProductionCuttingRunsAction extends ProductionCuttingMachineAction
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
        $validator->validate('production_order_id', $formData['production_order_id'] ?? null, 'integer|min:1');
        $validator->validate('factory_id', $formData['factory_id'] ?? null, 'integer|min:1');
        $validator->validate('cutting_machine_id', $formData['cutting_machine_id'] ?? null, 'integer|min:1');
        $validator->validate('status', $formData['status'] ?? null, 'string|in:draft,in_progress,completed,cancelled,all');

        if ($validator->hasErrors()) {
            $errorMessages = [];
            $errors = $validator->getErrors();
            if (is_array($errors)) {
                foreach ($errors as $fieldErrors) {
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
            'production_order_id' => 'integer',
            'factory_id' => 'integer',
            'cutting_machine_id' => 'integer',
            'status' => 'string',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $params = [
            'page' => $cleanData['page'],
            'page_limit' => $cleanData['limit'],
            'production_order_id' => $cleanData['production_order_id'] ?? 0,
            'factory_id' => $cleanData['factory_id'] ?? 0,
            'cutting_machine_id' => $cleanData['cutting_machine_id'] ?? 0,
            'status' => $cleanData['status'] ?? 'all',
        ];

        $cuttingRuns = $this->productionCuttingMachineRepository->findAllCuttingRuns(
            $params,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        $res_return = ['result' => 'success'];
        $res_return['trace_id'] = $trace_id;
        $res_return['data'] = $cuttingRuns;

        return $this->respondWithData($res_return);
    }
}
