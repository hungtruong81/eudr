<?php

declare(strict_types=1);

namespace App\Application\Actions\Authentication;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use App\Application\Utility\Validator;
use App\Application\Utility\Utils;
use App\Domain\User\User;

class LoginAction extends AuthenticationAction
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

        $validator = new Validator($this->request);

        $validator->validate('phone', $formData['phone'] ?? null, 'required|string');
        $validator->validate('password', $formData['password'] ?? null, 'required|string');

        if ($validator->hasErrors()) {
            $errorMessages = [];
            $errors = $validator->getErrors();
            if (is_array($errors)) {
                foreach ($errors as $field => $fieldErrors) {
                    if (is_array($fieldErrors)) {
                        $errorMessages[] = implode(', ', $fieldErrors);
                    } else {
                        $errorMessages[] = (string)$fieldErrors;
                    }
                }
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errorMessages));
        }

        // Sanitize and extract data
        $sanitizeRules = [
            'phone' => 'string',
            'password' => 'string',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $identifier = $cleanData['phone'];

        // Detect login by email or phone
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false;

        // Verify user
        if ($isEmail) {
            $this->db->where("email", $identifier);
        } else {
            $this->db->where("phone", $identifier);
        }
        $this->db->where("deleted_by", 0);
        $this->db->where("is_approved", 1);
        $data_user = $this->db->getOne("eudr_users");

        if (!$data_user) {
            $res_return =  [
                "result" => 'fail',
                "error" => [
                    "code" => 'WRONG_PHONE_EMAIL_OR_ACCOUNT_NOT_APPROVED',
                    "description" => "Số điện thoại/email không đúng hoặc tài khoản chưa được phê duyệt",
                ]
            ];
            $res_return['trace_id'] = $trace_id;
            return $this->respondWithData($res_return, 201);
        }

        // Check if account is deactivated by company owner
        if (isset($data_user['is_active']) && $data_user['is_active'] == 0) {
            $res_return = [
                "result" => 'fail',
                "error" => [
                    "code" => 'ACCOUNT_DEACTIVATED',
                    "description" => "Tài khoản của bạn đã bị vô hiệu hóa. Vui lòng liên hệ quản trị viên công ty.",
                ]
            ];
            $res_return['trace_id'] = $trace_id;
            return $this->respondWithData($res_return, 201);
        }

        $hash_password = md5(md5($formData['password'] . $data_user['salt']));
        if ($data_user['password'] != $hash_password) {
            $res_return =  [
                "result" => 'fail',
                "error" => [
                    "code" => 'WRONG_PASSWORD',
                    "description" => "Mật khẩu không đúng",
                ]
            ];
            $res_return['trace_id'] = $trace_id;
            return $this->respondWithData($res_return, 201);
        }

        /*
        if ($this->settings->get('env')!='localhost' && empty($formData['debug'])) {
            $reCAPTCHA_secret_key = $this->settings->get('reCAPTCHA_secret_key');
            if (!$formData['captcha']) {
                throw new HttpBadRequestException($this->request, "WRONG_CAPTCHA");
            } else {
                $valid = Utils::isValid($reCAPTCHA_secret_key, $formData['captcha']);
                if (!$valid) {
                    throw new HttpBadRequestException($this->request, "WRONG_CAPTCHA");
                } else {
                    //Success code here
                }
            }
        }
        */

        $secret_jwt = $this->settings->get('authentication_private_key');

        // Get user permissions
        //$data_user['permissions'] = $this->userRepository->getUserPermissions($data_user['user_id']);
        $data_user['permissions'] = [];

        // Get user roles (multi-role support)
        $data_user['roles'] = $this->userRepository->getUserRoles((int)$data_user['user_id']);

        // Get company code
        $this->db->where("company_id", $data_user['company_id']);
        $company = $this->db->getOne("eudr_companies", "company_code");
        $data_user['company_code'] = $company['company_code'] ?? '';

        $user = new User($data_user['user_id'], $data_user);

        $access_token = Utils::generateTokenAuth($user->jsonSerialize(), $secret_jwt);

        // Backward-compatible role string + multi-role array
        $user_role = $this->userRepository->getUserRole((int)$data_user['user_id']);
        $user_roles = array_map(fn($r) => $r['name'], $data_user['roles']);

        $log = [
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'user',
            "action" => 'login',
            "user_id" => (string)$data_user['user_id'],
            "extra_1" => '',
        ];

        Utils::save_log($this->logger, $log);

        $res_return = [
            "result" => 'success',
            "type" => 'auth',
            "access_token" => $access_token,
            // "user_role" => $user_role,
            // "user_roles" => $user_roles,
        ];

        $res_return['trace_id'] = $trace_id;

        $this->userRepository->updateUserLastLogin($data_user['user_id']);

        return $this->respondWithData($res_return, 200);
    }
}
