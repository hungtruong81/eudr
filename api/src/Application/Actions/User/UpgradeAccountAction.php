<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class UpgradeAccountAction extends UserAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $user_id = (int)$this->auth_data['user_id'];

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('add_roles', $formData['add_roles'] ?? null, 'array|in:farmer,purchaser,transport,factory,sales');
        $validator->validate('remove_roles', $formData['remove_roles'] ?? null, 'array|in:farmer,purchaser,transport,factory,sales');

        if ($validator->hasErrors()) {
            $errorMessages = [];
            $errors = $validator->getErrors();
            if (is_array($errors)) {
                foreach ($errors as $fieldErrors) {
                    if (is_array($fieldErrors)) {
                        $errorMessages[] = implode(', ', $fieldErrors);
                    } else {
                        $errorMessages[] = (string)$fieldErrors;
                    }
                }
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errorMessages));
        }

        $sanitizeRules = [
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

        $user = $this->userRepository->findUserOfId($user_id);
        if (empty($user)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy người dùng');
        }

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

        // Lấy permissions mới sau khi cập nhật roles
        $permissions = $this->userRepository->getUserPermissions($user_id);

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'user',
            'action' => 'upgrade_account',
            'user_id' => (string)$user_id,
            'extra_1' => 'added:' . implode(',', $roles_added) . '|removed:' . implode(',', $roles_removed),
        ];
        Utils::save_log($this->logger, $log);

        $res_return = [
            'result' => 'success',
            'roles_added' => $roles_added,
            'roles_removed' => $roles_removed,
            'current_roles' => $updated_roles,
            'permissions' => $permissions,
            'trace_id' => $trace_id,
        ];

        return $this->respondWithData($res_return, 200);
    }
}
