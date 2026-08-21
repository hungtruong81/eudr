<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\User\UserErrorException;
use Slim\Exception\HttpBadRequestException;
use App\Application\Utility\Utils;

class CreateWorkerUserAction extends UserAction
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

        // Check permission
        //$user_has_permission = $this->userRepository->userHasPermission($this->auth_data['user_id'], 'worker.create');
        $user_has_permission = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'worker', 'create');
        if (empty($user_has_permission)) {
            throw new UserErrorException("Bạn không có quyền tạo người dùng", 113);
        }

        // Validate data fields
        $required_fields = ["full_name", "email", "phone", "password"];
        $missing_fields = Utils::validFields($required_fields, $formData);
        if (!empty($missing_fields)) {
            throw new HttpBadRequestException($this->request, "Thiếu trường dữ liệu ".implode(", ", $missing_fields));
        }

        $full_name = htmlspecialchars(trim($formData['full_name'] ?? ""));
        $email = htmlspecialchars(trim($formData['email'] ?? ""));
        $phone = htmlspecialchars(trim($formData['phone'] ?? ""));

        $password = (trim($formData['password'] ?? (string)rand(100000,999999)));

        if (!empty($password) && strlen($password) < 6) {
            throw new HttpBadRequestException($this->request, "Mật khẩu phải có ít nhất 6 ký tự");
        }

        $salt = rand(100000, 999999);
        $hash_password = md5(md5($password.$salt));

        $user = $this->userRepository->findUserOfEmail($email);
        if (!empty($user)) {
            //throw new UserErrorException("Duplicate email", 101);
            throw new HttpBadRequestException($this->request, "Email đã tồn tại trong hệ thống");
        }

        $user = $this->userRepository->findUserOfPhone($phone);
        if (!empty($user)) {
            //throw new UserErrorException("Duplicate phone number", 101);
            throw new HttpBadRequestException($this->request, "Số điện thoại đã tồn tại trong hệ thống");
        }

        $default = "";
        $size = 100;
        $gravatar_url = "http://www.gravatar.com/avatar/" . md5(strtolower(trim($email))) . "?d=" . urlencode($default) . "&s=" . $size;

        $user_code = $this->userRepository->generateCode();

        // Data User
        $data_update = [
            "user_code" => $user_code,
            "salt" => $salt,
            "password" => $hash_password,
            "full_name" => $full_name,
            "email" => $email,
            "phone" => $phone,
            "avatar" => $gravatar_url,
            "register_type" => "worker",
            "created_by" => $this->auth_data['user_id'],
            "created_at" => date("Y-m-d H:i:s", time()),
            "is_approved" => 1,
            "parent_user_id" => $this->auth_data['user_id'],
        ];

        $user = $this->userRepository->createUser($data_update);

        // Set user permissions
        $this->userRepository->assignRoleToUser($user->getId(), 'worker');

        $action = 'create_worker_user';
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
