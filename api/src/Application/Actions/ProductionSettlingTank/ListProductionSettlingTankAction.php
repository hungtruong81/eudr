<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionSettlingTank;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

class ListProductionSettlingTankAction extends ProductionSettlingTankAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'raw_material_tank', 'view'); // production_settling_tank
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $formData = $this->request->getQueryParams();
        $validator = new Validator($this->request);

        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('factory_id', $formData['factory_id'] ?? null, 'integer|min:1');
        $validator->validate('status', $formData['status'] ?? null, 'in:available,in_use,cleaning,blocked,all');
        $validator->validate('search', $formData['search'] ?? null, 'string');

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
            'factory_id' => 'integer',
            'status' => 'string',
            'search' => 'string',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $params = [
            'page' => $cleanData['page'],
            'page_limit' => $cleanData['limit'],
            'user_id' => $this->auth_data['user_id'],
            'factory_id' => $cleanData['factory_id'] ?? null,
            'status' => $cleanData['status'] ?? 'all',
            'search' => $cleanData['search'] ?? '',
        ];

        $data = $this->productionSettlingTankRepository->findAll(
            $params,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        $res_return = ['result' => 'success'];
        $res_return['data'] = $data;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }
}
