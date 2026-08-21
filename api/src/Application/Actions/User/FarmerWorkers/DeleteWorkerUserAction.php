<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\User\UserErrorException;
use App\Domain\User\UserNotFoundException;
use App\Application\Utility\Utils;

class DeleteWorkerUserAction extends UserAction
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
            throw new UserErrorException("Thiếu thông tin người dùng", 113);
        }

        // Check permission
        $permission_status = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'worker', 'delete');

        if (empty($permission_status)) {
            throw new UserErrorException("Bạn không có quyền xóa người dùng", 113);
        }

        $user_code = addslashes(trim($this->resolveArg('code')));

        $user = $this->userRepository->findWorkerUserOfCodeWithPermission($user_code, $this->auth_data['user_id'], (string)$permission_status);
    
        if (empty($user)) {
            throw new UserNotFoundException("Người dùng không tồn tại", 102);
        }

        if ($user->getId()==$this->auth_data['user_id']) {
            throw new UserErrorException("Không thể xóa chính mình", 101);
        }

        $this->userRepository->deleteUser($user->getId(), $this->auth_data['user_id']);

        $action = 'delete_worker_user';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'user',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$user->getId(),
        );
        
        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }
}
