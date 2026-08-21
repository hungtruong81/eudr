<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Slim\Exception\HttpBadRequestException;

class UpdateUserPermissionAction extends UserAction
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
            throw new HttpBadRequestException($this->request, "Thiếu thông tin người dùng");
        }

        $permission_status = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'company_members', 'view');
        
        $formData = $this->getFormData();

        // Check permission
        if (empty($this->permission['user.permission.update'])) {
            throw new HttpBadRequestException($this->request, "Thiếu quyền truy cập");
        }

        $user_code = $this->resolveArg('code');

        $user = $this->userRepository->findUserOfCode($user_code);
        if (empty($user)) {
            throw new HttpBadRequestException($this->request, "Người dùng không tồn tại");
        }
        
        $validator = new Validator($this->request);

        $validator->validate('permissions', $formData['permissions'] ?? null, 'required|array');

        $data_permission = $formData['permissions'] ?? [];

        $user = $this->userRepository->updateUserPermission($user->getId(), $data_permission);

        $action = 'update_user_permission';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'user',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$user->getId(),
        );

        Utils::save_log($this->logger, $log);

        // Get updated user permissions
        $permissions = $this->userRepository->getUserPermissions($user->getId());
        
        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['user'] = $user->jsonSerialize();
        $res_return['user']['permissions'] = $permissions;

        return $this->respondWithData($res_return);
    }
}
