<?php

declare(strict_types=1);

namespace App\Application\Actions\Vehicle;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;

class DeleteVehicleAction extends VehicleAction
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

        // Load permissions once and resolve scopes
        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);

        // Check permission to delete vehicles
        $deleteScope = Utils::resolveScope($permissions, 'vehicle', 'delete');
        if (empty($deleteScope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $vehicle_code = addslashes(trim((string)$this->resolveArg('code')));

        $vehicle = $this->vehicleRepository->findVehicleOfCodeWithPermission(
            $vehicle_code,
            (int)$this->auth_data['user_id'],
            (string)$deleteScope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($vehicle)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy phương tiện");
        }

        // Delete vehicle
        $this->vehicleRepository->deleteVehicleWithPermission(
            $vehicle->getId(),
            (int)$this->auth_data['user_id'],
            (string)$deleteScope,
            $this->auth_data['company_id'] ?? null
        );
        
        $action = 'delete';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'vehicle',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$vehicle->getId(),
        );
        
        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }
}
