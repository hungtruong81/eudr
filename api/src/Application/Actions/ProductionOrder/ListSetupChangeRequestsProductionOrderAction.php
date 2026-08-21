<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionOrder;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

class ListSetupChangeRequestsProductionOrderAction extends ProductionOrderAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'production_order', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $formData = $this->request->getQueryParams();
        $validator = new Validator($this->request);

        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('approval_status', $formData['approval_status'] ?? null, 'string|in:pending,approved,rejected,all');
        $validator->validate('change_type', $formData['change_type'] ?? null, 'string|in:raw_tank,settling_tank,channel,cutting_machine,roller,hanging,drying,pressing,pallet,all');
        $validator->validate('production_order_code', $formData['production_order_code'] ?? null, 'string|max:50');
        $validator->validate('factory_id', $formData['factory_id'] ?? null, 'integer|min:1');
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

        $cleanData = $validator->sanitize($formData, [
            'page' => 'integer',
            'limit' => 'integer',
            'approval_status' => 'string',
            'change_type' => 'string',
            'production_order_code' => 'string',
            'factory_id' => 'integer',
            'search' => 'string',
        ]);

        $params = [
            'page' => $cleanData['page'],
            'page_limit' => $cleanData['limit'],
            'approval_status' => $cleanData['approval_status'] ?? 'all',
            'change_type' => $cleanData['change_type'] ?? 'all',
            'production_order_code' => $cleanData['production_order_code'] ?? '',
            'factory_id' => $cleanData['factory_id'] ?? 0,
            'search' => $cleanData['search'] ?? '',
        ];

        $changeRequests = $this->productionOrderRepository->findAllSetupChangeRequests(
            $params,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'data' => $changeRequests,
        ]);
    }
}