<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;

class DeactivateUserAction extends UserAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        // Validate authenticated user
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        // Check permission
        //$user_has_permission = $this->userRepository->userHasPermission($this->auth_data['user_id'], 'user.deactivate');
        $scope = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'company_member', 'update');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Bạn không có quyền vô hiệu hóa/kích hoạt tài khoản người dùng");
        }

        $user_code = addslashes(trim($this->resolveArg('code')));

        $user = $this->userRepository->findUserOfCode($user_code);
        if (empty($user)) {
            throw new HttpNotFoundException($this->request, "Người dùng bạn yêu cầu không tồn tại.");
        }

        // Cannot deactivate yourself
        if ($user->getId() == $this->auth_data['user_id']) {
            throw new HttpBadRequestException($this->request, "Không thể vô hiệu hóa chính mình");
        }

        // Only allow deactivating users within the same company
        if ($user->getCompanyId() != $this->auth_data['company_id']) {
            throw new HttpForbiddenException($this->request, "Bạn chỉ có thể vô hiệu hóa thành viên trong cùng công ty");
        }

        // Toggle is_active: if currently active -> deactivate, if inactive -> activate
        $new_status = $user->getIsActive() ? 0 : 1;

        $data_update = [
            'updated_by' => $this->auth_data['user_id'],
            'updated_at' => date('Y-m-d H:i:s'),
            'is_active' => $new_status,
        ];

        $user = $this->userRepository->updateUser($user->getId(), $data_update);

        $action = $new_status ? 'activate_user' : 'deactivate_user';
        $log = [
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'user',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$user->getId(),
        ];

        Utils::save_log($this->logger, $log);

        $res_return = [
            "result" => "success",
        ];

        $res_return['data'] = $user->jsonSerialize();
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }
}
