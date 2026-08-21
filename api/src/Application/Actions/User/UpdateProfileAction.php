<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\User\UserErrorException;
use App\Application\Utility\Utils;

class UpdateProfileAction extends UserAction
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
        $user_id = $this->auth_data['user_id'];


        $user = $this->userRepository->findUserOfId($user_id);
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

        if (empty($data_update)) {
            throw new UserErrorException("Dữ liệu cập nhật không hợp lệ", 101);
        }

        $user = $this->userRepository->updateUser($user_id, $data_update);

        $secret_jwt = $this->settings->get('authentication_private_key');
        $access_token = Utils::generateTokenAuth($user->jsonSerialize(), $secret_jwt);

        $action = 'update';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'user',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => 'profile',
        );
        Utils::save_log($this->logger,$log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['access_token'] = $access_token;
        // $res_return['user'] = $user->jsonSerialize();

        return $this->respondWithData($res_return);
    }
}
