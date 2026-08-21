<?php

declare(strict_types=1);

namespace App\Application\Actions\VehicleTank;

use App\Application\Utility\Utils;
use App\Domain\VehicleTank\VehicleTankException;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class DeleteVehicleTankAction extends VehicleTankAction
{
    protected function action(): Response
    {
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'vehicle_tank', 'delete');

        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $tank = $this->vehicleTankRepository->findByCodeWithPermission(
            trim((string)$this->resolveArg('code')),
            (int)$this->auth_data['user_id'],
            (string)$scope,
            (int)($this->auth_data['company_id'] ?? 0)
        );

        if (!$tank) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy bồn trên xe');
        }

        try {
            $deleted = $this->vehicleTankRepository->deleteWithPermission(
                $tank->getId(),
                (int)$this->auth_data['user_id'],
                (string)$scope,
                (int)($this->auth_data['company_id'] ?? 0)
            );
        } catch (VehicleTankException $e) {
            throw new HttpBadRequestException($this->request, $e->getMessage());
        }

        if (!$deleted) {
            throw new HttpBadRequestException($this->request, 'Không thể xóa bồn trên xe');
        }

        return $this->respondWithData(['result' => 'success']);
    }
}
