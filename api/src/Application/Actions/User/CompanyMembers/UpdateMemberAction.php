<?php

declare(strict_types=1);

namespace App\Application\Actions\User\CompanyMembers;

use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\User\UserErrorException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Validator;
use App\Application\Utility\Utils;

class UpdateMemberAction extends CompanyMemberAction
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

        // Check permission to update company members
        $scope = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'company_member', 'update');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $user_code = addslashes(trim((string)$this->resolveArg('code')));

        $user = $this->companyMemberRepository->findMemberOfCodeWithPermission($user_code, $this->auth_data['user_id'], (string)$scope);
        if (empty($user)) {
            throw new HttpNotFoundException($this->request, "Người dùng không tồn tại");
        }


        
        $formData = $this->getFormData();

        $validator = new Validator($this->request);

        $validator->validate('full_name', $formData['full_name'] ?? null, 'required|string|min:2|max:100');
        $validator->validate('register_type', $formData['register_type'] ?? null, 'required|array|in:farmer,purchaser,transport,factory,sales');
        $validator->validate('add_roles', $formData['add_roles'] ?? null, 'array|in:farmer,purchaser,transport,factory,sales');
        $validator->validate('remove_roles', $formData['remove_roles'] ?? null, 'array|in:farmer,purchaser,transport,factory,sales');
        $validator->validate('password', $formData['password'] ?? null, 'string|min:6');

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
            'full_name' => 'string',
            'password' => 'string',
            'register_type' => 'array',
            'add_roles' => 'array',
            'remove_roles' => 'array',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);


        $add_roles = $cleanData['add_roles'] ?? [];
        $remove_roles = $cleanData['remove_roles'] ?? [];

        if (!is_array($add_roles)) {
            $add_roles = [];
        }
        if (!is_array($remove_roles)) {
            $remove_roles = [];
        }

        $add_roles = array_values(array_filter(array_unique($add_roles)));
        $remove_roles = array_values(array_filter(array_unique($remove_roles)));

        if (empty($add_roles) && empty($remove_roles)) {
            throw new HttpBadRequestException($this->request, 'Vui lòng chọn ít nhất một role để thêm hoặc xóa');
        }

        $password = $cleanData['password'] ?? "";
        $full_name = $cleanData['full_name'] ?? "";

        $data_update = [];
        if (!empty($full_name)) {
            $data_update['full_name'] = $full_name;
        }

        if (!empty($password)) {
            $salt = $user->getSalt();
            $password = (trim($password));
            $hash_password = md5(md5($password.$salt));
            $data_update['password'] = $hash_password;
        }

        if (empty($data_update)) {
            throw new UserErrorException("Dữ liệu cập nhật không hợp lệ", 101);
        }

        $data_update["updated_at"] = date("Y-m-d H:i:s", time());
        $data_update["updated_by"] = $this->auth_data['user_id'];
        
        $user = $this->companyMemberRepository->updateMemberWithPermission(
            $user->getId(),
            $data_update,
            (int)$this->auth_data['user_id'],
            (string)$scope
        );

        $user_id = $user->getId();
        // Lấy danh sách roles hiện tại
        $current_roles = $this->userRepository->getUserRoles($user_id);
        $current_role_names = array_column($current_roles, 'name');

        // Thêm roles mới
        $roles_added = [];
        foreach ($add_roles as $role_name) {
            if (!in_array($role_name, $current_role_names)) {
                $this->userRepository->assignRoleToUser($user_id, $role_name);
                $roles_added[] = $role_name;
            }
        }

        // Xóa roles
        $roles_removed = [];
        foreach ($remove_roles as $role_name) {
            if (in_array($role_name, $current_role_names)) {
                $this->userRepository->removeRoleFromUser($user_id, $role_name);
                $roles_removed[] = $role_name;
            }
        }

        // Đảm bảo user luôn còn ít nhất 1 role
        $updated_roles = $this->userRepository->getUserRoles($user_id);
        if (empty($updated_roles)) {
            // Rollback: gán lại role đầu tiên đã xóa
            if (!empty($roles_removed)) {
                $this->userRepository->assignRoleToUser($user_id, $roles_removed[0]);
                $updated_roles = $this->userRepository->getUserRoles($user_id);
            }
        }


        $action = 'update_company_member';
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
