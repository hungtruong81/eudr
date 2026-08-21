<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\User\UserErrorException;
use App\Application\Utility\Utils;

class UpdateUserAction extends UserAction
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

        $formData = $this->getFormData();

        // For admin
        if (empty($this->permission->admin)) {
            throw new UserErrorException("Thiếu quyền truy cập", 113);
        }

        // Validate data fields
        /* if (empty($formData['user_id'])) {
            throw new UserErrorException("Missing user_id", 101);
        } */
        $user_code = $this->resolveArg('code');

        $user = $this->userRepository->findUserOfCode($user_code);
        if (empty($user)) {
            throw new UserErrorException("Người dùng không tồn tại", 101);
        }

        $data_update = [];
        if (!empty($formData['first_name'])) {
            $data_update['first_name'] = htmlspecialchars(trim($formData['first_name']));
        }
        if (!empty($formData['last_name'])) {
            $data_update['last_name'] = htmlspecialchars(trim($formData['last_name']));
        }
        if (!empty($formData['avatar'])) {
            $data_update['avatar'] = htmlspecialchars(trim($formData['avatar']));
        }
        if (isset($formData['active'])) {
            $data_update['active'] = $formData['active']==1?1:0;
        }

        
        if (!empty($formData['permission'])) {
            $data_update['permission'] = json_encode($formData['permission']);
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

        $user = $this->userRepository->updateUser($user->getId(), $data_update);

        // $secret_jwt = $this->settings->get('authentication_private_key');
        // $access_token = Utils::generateTokenAuth($user->jsonSerialize(), $secret_jwt);

        $action = 'update';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'user',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$user->getId(),
        );
        Utils::save_log($this->logger,$log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['user'] = $user->jsonSerialize();

        return $this->respondWithData($res_return);
    }
}
