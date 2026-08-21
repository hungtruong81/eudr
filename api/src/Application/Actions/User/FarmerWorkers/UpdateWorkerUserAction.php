<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\User\UserNotFoundException;
use App\Domain\User\UserErrorException;
use App\Application\Utility\Utils;

class UpdateWorkerUserAction extends UserAction
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
        $permission_status = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'worker', 'update');

        if (empty($permission_status)) {
            throw new UserErrorException("Thiếu quyền truy cập", 113);
        }

        $user_code = addslashes(trim($this->resolveArg('code')));

        $user = $this->userRepository->findWorkerUserOfCodeWithPermission($user_code, $this->auth_data['user_id'], (string)$permission_status);
    
        if (empty($user)) {
            throw new UserNotFoundException("Người dùng không tồn tại", 102);
        }

        $formData = $this->getFormData();

        $data_update = [];
        if (!empty($formData['full_name'])) {
            $data_update['full_name'] = htmlspecialchars(trim($formData['full_name']));
        }

        if (!empty($formData['password'])) {
            $salt = $user->getSalt();
            $password = (trim($formData['password']));
            $hash_password = md5(md5($password.$salt));
            $data_update['password'] = $hash_password;
        }

        if (empty($data_update)) {
            throw new UserErrorException("Dữ liệu cập nhật không hợp lệ", 101);
        }

        $data_update["updated_at"] = date("Y-m-d H:i:s", time());
        $data_update["updated_by"] = $this->auth_data['user_id'];
        
        $user = $this->userRepository->updateUser($user->getId(), $data_update);

        $action = 'update_worker_user';
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
        $res_return['user'] = $user->jsonSerialize();

        return $this->respondWithData($res_return);
    }
}
