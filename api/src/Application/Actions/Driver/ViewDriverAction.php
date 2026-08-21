<?php

declare(strict_types=1);

namespace App\Application\Actions\Driver;

use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\Driver\DriverErrorException;
use App\Application\Utility\Utils;

class ViewDriverAction extends DriverAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new DriverErrorException("Thiếu quyền truy cập", 113);
        }

        // Check permission
        /*
        $permission_status = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'vehicle', 'view');
        if (empty($permission_status)) {
            throw new DriverErrorException("Thiếu quyền truy cập", 113);
        }
        */
        $formData = $this->request->getQueryParams();

        $driver_code = trim($this->resolveArg('code'));

        $driver = $this->driverRepository->findDriverOfCode($driver_code);

        if (empty($driver)) {
            throw new DriverErrorException("Không tìm thấy tài xế", 101);
        }

        $action = 'view';
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
        $res_return['data'] = $driver->jsonSerialize();

        return $this->respondWithData($res_return);
    }
}
