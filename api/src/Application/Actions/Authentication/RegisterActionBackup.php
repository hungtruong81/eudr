<?php

declare(strict_types=1);

namespace App\Application\Actions\Authentication;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use App\Application\Utility\Utils;

class RegisterActionBackup extends AuthenticationAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // $displayErrorDetails = $this->settings->get('displayErrorDetails');
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        $formData = $this->getFormData();

        // Validate data fields
        $required_fields = ['full_name', 'email', 'phone', 'password', 'register_type'];

        $missing_fields = Utils::validFields($required_fields, $formData);

        if (!empty($missing_fields)) {
            throw new HttpBadRequestException($this->request, "Thiếu trường dữ liệu " . implode(", ", $missing_fields));
        }

        $password = "";
        if (!empty($formData['password'])) {
            $password = htmlspecialchars(trim($formData['password']));
        }

        $full_name = "";
        if (!empty($formData['full_name'])) {
            $full_name = htmlspecialchars(trim($formData['full_name']));
        }

        $email = "";
        if (!empty($formData['email'])) {
            $email = htmlspecialchars(trim($formData['email']));
        }

        $phone = "";
        if (!empty($formData['phone'])) {
            $phone = htmlspecialchars(trim($formData['phone']));
        }

        $register_type = "";
        if (!empty($formData['register_type'])) {
            $register_type = htmlspecialchars(trim($formData['register_type']));
        }

        if (!empty($password) && strlen($password) < 6) {
            throw new HttpBadRequestException($this->request, "Mật khẩu phải có ít nhất 6 ký tự");
        }

        /*
        if (!Utils::validatePassword($password)) {
            throw new HttpBadRequestException($this->request, "Mật khẩu phải có ít nhất 1 ký tự viết hoa, 1 ký tự viết thường, 1 ký tự số và 1 ký tự đặc biệt.");
        }
        */

        // Check duplicate user email
        $this->db->where("email", $email);
        $this->db->where("deleted_by", 0);
        $data_user = $this->db->getOne("eudr_users");
        if ($data_user) {
            throw new HttpBadRequestException($this->request, "Địa chỉ Email đã tồn tại trong hệ thống");
        }

        // Check duplicate user phone
        $this->db->where("phone", $phone);
        $this->db->where("deleted_by", 0);
        $data_user = $this->db->getOne("eudr_users");
        if ($data_user) {
            throw new HttpBadRequestException($this->request, "Số điện thoại đã tồn tại trong hệ thống");
        }

        $salt = rand(100000, 999999);
        $hash_password = md5(md5($password . $salt));

        $default = "";
        $size = 100;
        $gravatar_url = "http://www.gravatar.com/avatar/" . md5(strtolower(trim($email))) . "?d=" . urlencode($default) . "&s=" . $size;

        $user_code = $this->userRepository->generateCode();

        $data_update = [
            "user_code" => $user_code,
            "full_name" => $full_name,
            "email" => $email,
            "phone" => $phone,
            "register_type" => $register_type,
            "avatar" => $gravatar_url,
            "salt" => $salt,
            "password" => $hash_password,
        ];

        $user = $this->userRepository->createUser($data_update);

        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'user',
            "action" => 'register',
            "user_id" => (string)$user->getId(),
            "extra_1" => '',
        );

        Utils::save_log($this->logger, $log);

        $res_return =  [
            "result" => 'success',
        ];

        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return, 200);
    }
}
