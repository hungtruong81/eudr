<?php

declare(strict_types=1);

namespace App\Application\Actions\VehicleTank;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

class ListVehicleTankAction extends VehicleTankAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'vehicle_tank', 'view');

        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $query = $this->request->getQueryParams();
        $validator = new Validator($this->request);

        $validator->validate('page', $query['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $query['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('search', $query['search'] ?? null, 'string');
        $validator->validate(
            'status',
            $query['status'] ?? null,
            'in:idle,loading,in_transit,unloading,cleaning,maintenance,inactive,all'
        );
        $validator->validate('vehicle_id', $query['vehicle_id'] ?? null, 'integer|min:1');

        if ($validator->hasErrors()) {
            throw new HttpBadRequestException($this->request, 'Tham số lọc không hợp lệ');
        }

        $clean = $validator->sanitize($query, [
            'page' => 'integer',
            'limit' => 'integer',
            'search' => 'string',
            'status' => 'string',
            'vehicle_id' => 'integer',
        ]);
        $vehicle_tanks = $this->vehicleTankRepository->findAll(
            [
                'page' => $clean['page'],
                'page_limit' => $clean['limit'],
                'search' => $clean['search'] ?? '',
                'status' => $clean['status'] ?? 'all',
                'vehicle_id' => $clean['vehicle_id'] ?? 0,
            ],
            (int)$this->auth_data['user_id'],
            (string)$scope,
            (int)($this->auth_data['company_id'] ?? 0)
        );

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'data' => $vehicle_tanks,
        ]);
    }
}
