<?php

declare(strict_types=1);

namespace App\Application\Actions\User\CompanyMembers;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;


class CreateMemberAction extends CompanyMemberAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);
        // Check authenticated user
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        // Check permission to create company member
        $scope = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'company_member', 'create');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }
        
        $formData = $this->getFormData();

        $validator = new Validator($this->request);

        $validator->validate('full_name', $formData['full_name'] ?? null, 'required|string|min:2|max:100');
        $validator->validate('email', $formData['email'] ?? null, 'required|email');
        $validator->validate('phone', $formData['phone'] ?? null, ['required', 'regex:/^0[0-9]{9}$/']);
        $validator->validate('password', $formData['password'] ?? null, 'required|string|min:6');

        // Multi-role: accept string or array, with both account types and role names
        $register_type_raw = $formData['register_type'] ?? null;
        if (is_string($register_type_raw)) {
            $register_type_raw = [$register_type_raw];
        }
        $validator->validate('register_type', $register_type_raw, 'required|array|in:farmer,purchaser,trader,inspector,company,transport,factory,sales');

        $validator->validate('company_id', $formData['company_id'] ?? null, 'integer');

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
            'company_id' => 'integer',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $password = $cleanData['password'];
        $full_name = $cleanData['full_name'];
        $email = $cleanData['email'];
        $phone = $cleanData['phone'];
        $company_id = $cleanData['company_id'] ?? 0;

        // Multi-role: normalize input (account types + role names) to valid role names
        $normalizedRoles = Utils::normalizeToRoleNames($register_type_raw);
        if (empty($normalizedRoles)) {
            throw new HttpBadRequestException($this->request, 'Role không hợp lệ');
        }

        // Primary register_type for backward compat (first role's account type)
        $primary_register_type = Utils::mapRoleToAccountType($normalizedRoles[0]);
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

        $salt = rand(100000, 999999);
        $hash_password = md5(md5($password.$salt));

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
            "register_type" => $primary_register_type,
            "created_by" => $this->auth_data['user_id'],
            "created_at" => date("Y-m-d H:i:s", time()),
            "is_approved" => 1,
            "parent_user_id" => $this->auth_data['user_id'],
            "company_id" => $company_id,
        ];

        $user = $this->companyMemberRepository->createMember($data_update);

        // Assign all normalized roles to user (multi-role)
        foreach ($normalizedRoles as $roleName) {
            $this->userRepository->assignRoleToUser($user->getId(), $roleName);
        }

        $action = 'create_company_member';
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
