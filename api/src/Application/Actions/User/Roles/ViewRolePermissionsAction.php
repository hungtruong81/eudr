<?php

declare(strict_types=1);

namespace App\Application\Actions\User\Roles;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;

class ViewRolePermissionsAction extends RoleAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $user_id = (int)$this->auth_data['user_id'];

        // Lấy role_id từ route argument
        $role_id = (int)($this->resolveArg('role_id') ?? 0);
        if ($role_id <= 0) {
            throw new \Slim\Exception\HttpBadRequestException($this->request, 'role_id không hợp lệ');
        }

        // Kiểm tra role tồn tại
        $role_found = $this->userRepository->findRoleById($role_id);
        if (empty($role_found)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy role');
        }

        // Lấy danh sách permissions của role
        $permissions = $this->userRepository->getRolePermissionsByRoleId($role_id);

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'role',
            'action' => 'view_role_permissions',
            'user_id' => (string)$user_id,
            'extra_1' => "role_id:{$role_id}",
        ];
        Utils::save_log($this->logger, $log);

        $res_return = [
            'result' => 'success',
            'role_id' => $role_id,
            'role_name' => $role_found['name'],
            'role_description' => $role_found['description'] ?? '',
            'permissions' => $permissions,
            'total_permissions' => count($permissions),
            'trace_id' => $trace_id,
        ];

        return $this->respondWithData($res_return, 200);
    }
}
