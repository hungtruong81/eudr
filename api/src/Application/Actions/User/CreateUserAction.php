<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\User\UserErrorException;
use App\Application\Utility\Utils;

class CreateUserAction extends UserAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new UserErrorException("Thiếu thông tin người dùng", 113);
        }

        $formData = $this->getFormData();

        // For admin
        if (empty($this->permission->admin)) {
            throw new UserErrorException("Bạn không có quyền tạo người dùng", 113);
        }

        // Validate data fields
        $required_fields = ["first_name","email","permission"];
        foreach ($required_fields as $field) {
            if (empty($formData[$field])) {
                throw new UserErrorException("Thiếu trường dữ liệu ".$field, 101);
            }
        }

        $first_name = htmlspecialchars(trim($formData['first_name']??""));
        $last_name = htmlspecialchars(trim($formData['last_name']??""));
        $avatar = htmlspecialchars(trim($formData['avatar']??""));
        $email = htmlspecialchars(trim($formData['email']??""));
        $tmp = explode("@",$email);
        if (count($tmp) != 2) {
            throw new UserErrorException("Email không hợp lệ", 101);
        }
        $username = $tmp[0];
        $username = htmlspecialchars(trim($username));

        $password = (trim($formData['password']??(string)rand(100000,999999)));
        $permission = $formData['permission']??[];
        $salt = rand(100, 999);
        $hash_password = md5(md5($password.$salt));
        $user = $this->userRepository->findUserOfUsername($username);
        if (!empty($user)) {
            throw new UserErrorException("Tên người dùng đã tồn tại", 101);
        }

        $user_code = $this->userRepository->generateCode();

        // Data User
        $data_update = [
            "user_code" => $user_code,
            "username" => $username,
            "salt" => $salt,
            "password" => $hash_password,
            "first_name" => $first_name,
            "last_name" => $last_name,
            "avatar" => $avatar,
            "email" => $email,
            "permission" => json_encode($permission),
        ];

        $user = $this->userRepository->createUser($data_update);

        $action = 'create';
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
