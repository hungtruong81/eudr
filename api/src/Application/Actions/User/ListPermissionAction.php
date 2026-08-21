<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;
use App\Application\Utility\Utils;
use Slim\Exception\HttpUnauthorizedException;


class ListPermissionAction extends UserAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        // Check permission
        // if (empty($this->permission['user.permission.list'])) {
        //     throw new UserErrorException("Bạn không có quyền xem danh sách quyền", 113);
        // }
        
        $permissions = $this->userRepository->getAllPermissions();
        
        $action = 'list';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'permission',
            "action" => $action,
            "user_id" => (string)($this->auth_data['user_id'] ?? ''),
            "extra_1" => '',
        );

        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['data'] = $permissions;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }
}
