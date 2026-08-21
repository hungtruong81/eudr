<?php

declare(strict_types=1);

namespace App\Application\Actions\Driver;

use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\Driver\DriverErrorException;
use App\Domain\Driver\DriverNotFoundException;
use App\Application\Utility\Utils;

class DeleteDriverAction extends DriverAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);
        
        // Validate API type
        if (empty($this->auth_data['user_id'])) {
            throw new DriverErrorException("Thiếu quyền truy cập", 113);
        }

        // Check permission
        $permission_status = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'vehicle', 'delete');

        if (empty($permission_status)) {
            throw new DriverErrorException("Thiếu quyền truy cập", 113);
        }

        $driver_code = addslashes(trim($this->resolveArg('code')));

        $driver = $this->driverRepository->findDriverOfCodeWithPermission($driver_code, $this->auth_data['user_id'], (string)$permission_status);

        if (empty($driver)) {
            throw new DriverNotFoundException("Không tìm thấy tài xế", 102);
        }

        // Delete driver
        $this->driverRepository->deleteDriver($driver->getId(), $this->auth_data['user_id']);
        
        // Log action
        $action = 'delete';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'driver',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$driver->getId(),
        );
        
        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }
}
