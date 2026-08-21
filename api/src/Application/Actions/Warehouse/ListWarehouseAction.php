<?php

declare(strict_types=1);

namespace App\Application\Actions\Warehouse;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

class ListWarehouseAction extends WarehouseAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'warehouse', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $formData = $this->request->getQueryParams();
        $validator = new Validator($this->request);
        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('factory_id', $formData['factory_id'] ?? null, 'integer|min:1');
        $validator->validate('warehouse_type', $formData['warehouse_type'] ?? null, 'in:raw_material,intermediate,finished_goods,production_pallet,transit,all');
        $validator->validate('status', $formData['status'] ?? null, 'in:active,maintenance,blocked,inactive,all');
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
            'factory_id' => 'integer',
            'warehouse_type' => 'string',
            'status' => 'string',
            'search' => 'string',
        ]);

        $warehouses = $this->warehouseRepository->findAll(
            [
                'page' => $cleanData['page'],
                'page_limit' => $cleanData['limit'],
                'factory_id' => $cleanData['factory_id'] ?? null,
                'warehouse_type' => $cleanData['warehouse_type'] ?? 'all',
                'status' => $cleanData['status'] ?? 'all',
                'search' => $cleanData['search'] ?? '',
            ],
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'data' => $warehouses,
        ]);
    }
}
