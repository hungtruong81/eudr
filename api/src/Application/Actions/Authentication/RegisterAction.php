<?php

declare(strict_types=1);

namespace App\Application\Actions\Authentication;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class RegisterAction extends AuthenticationAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        $formData = $this->getFormData();

        $validator = new Validator($this->request);

        $validator->validate('otp_request_id', $formData['otp_request_id'] ?? null, 'required|integer');
        $validator->validate('purpose', $formData['purpose'] ?? null, 'required|in:register,other');
        $validator->validate('full_name', $formData['full_name'] ?? null, 'required|string|min:2|max:100');
        $validator->validate('email', $formData['email'] ?? null, 'required|email');
        $validator->validate('phone', $formData['phone'] ?? null, ['required', 'regex:/^0[0-9]{9}$/']);
        $validator->validate('password', $formData['password'] ?? null, 'required|string|min:6');
        $validator->validate('register_type', $formData['register_type'] ?? null, 'required|array|in:farmer,purchaser,transport,factory,sales');
        $validator->validate('company_code', $formData['company_code'] ?? null, 'string|max:30');
        $validator->validate('company_name', $formData['company_name'] ?? null, 'string|max:255');

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
            'otp_request_id' => 'integer',
            'purpose' => 'string',
            'full_name' => 'string',
            'email' => 'email',
            'phone' => 'string',
            'password' => 'string',
            'register_type' => 'array',
            'company_code' => 'string',
            'company_name' => 'string',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);
        
        $password = $cleanData['password'];
        $full_name = $cleanData['full_name'];
        $email = $cleanData['email'];
        $phone = $cleanData['phone'];
        $register_types = $cleanData['register_type'];
        if (!is_array($register_types)) {
            $register_types = [$register_types];
        }
        $register_types = array_values(array_filter(array_unique($register_types)));

        $primary_register_type = $register_types[0] ?? '';
        $otp_request_id = $cleanData['otp_request_id'];
        $purpose = $cleanData['purpose'];
        $company_code = $cleanData['company_code'];
        $company_name = $cleanData['company_name'] ?? null;
        
        // Verify OTP
        $otp = $this->userRepository->findOtpRequest($otp_request_id, $phone, $purpose);
        
        if(empty($otp) || empty($otp['is_verified'])) {
            $message = $validator->getErrorMessage('invalid_otp', []);
            throw new HttpBadRequestException($this->request, $message);
        }
        
        // Check duplicate user email
        $data_user = $this->userRepository->findUserOfEmail($email);
        if ($data_user) {
            $message = $validator->getErrorMessage('duplicate_email', []);
            throw new HttpBadRequestException($this->request, $message);
        }

        // Check duplicate user phone
        $data_user = $this->userRepository->findUserOfPhone($phone);
        if ($data_user) {
            $message = $validator->getErrorMessage('duplicate_phone', []);
            throw new HttpBadRequestException($this->request, $message);
        }

        // Generate company code if not provided
        if (empty($company_code)) {
            $company_code = $this->companyRepository->generateCode();
            // Create new company
            $data_company = [
                "company_code" => $company_code,
                "company_name" => $company_name ?? "Unknown - ".$full_name,
                "created_by" => 0,
                "created_at" => date("Y-m-d H:i:s"),
                "generate_default" => 1,
            ];
            $company = $this->companyRepository->createCompany($data_company);
            if (empty($company)) {
                throw new HttpBadRequestException($this->request, "Tạo công ty thất bại");
            }
        }

        // Validate company info
        $data_company = $this->companyRepository->findCompanyOfCode($company_code);
        if (empty($data_company)) {
            throw new HttpNotFoundException($this->request, "Công ty không tồn tại trong hệ thống");
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
            "register_type" => $primary_register_type,
            "avatar" => $gravatar_url,
            "salt" => $salt,
            "password" => $hash_password,
            "is_approved" => 1,
            "company_id" => $data_company->getId(),
        ];

        $user = $this->userRepository->createUser($data_update);
        
        $data_user = $user->jsonSerialize();

        // Assign roles to user
        foreach ($register_types as $role_name) {
            $this->userRepository->assignRoleToUser($user->getId(), $role_name);
        }

        $secret_jwt = $this->settings->get('authentication_private_key');

        // Get user permissions
        //$data_user['permissions'] = $this->userRepository->getUserPermissions($data_user['user_id']);
        $data_user['permissions'] = [];

        $access_token = Utils::generateTokenAuth($data_user, $secret_jwt);

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
            "type" => 'auth',
            "access_token" => $access_token,
        ];

        $res_return['trace_id'] = $trace_id;
        
        $user = $this->userRepository->updateUserLastLogin($data_user['user_id']);

        return $this->respondWithData($res_return, 200);
    }
}
