<?php

declare(strict_types=1);

namespace App\Application\Actions\User\Roles;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use App\Application\Utility\Utils;

class ListRoleAction extends RoleAction
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

        $user_id = (int)$this->auth_data['user_id'];

        // Lấy tất cả roles trong hệ thống
        $all_roles = $this->userRepository->getAllRoles();

        // Lấy danh sách roles của user hiện tại
        $user_roles = $this->userRepository->getUserRoles($user_id);

        $log = [
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'role',
            "action" => 'list',
            "user_id" => (string)$user_id,
            "extra_1" => '',
        ];

        Utils::save_log($this->logger, $log);

        $res_return = [
            "result" => "success",
            "data" => [
                "all_roles" => $all_roles,
                "user_roles" => $user_roles,
            ],
            "trace_id" => $trace_id,
        ];

        return $this->respondWithData($res_return);
    }
}
