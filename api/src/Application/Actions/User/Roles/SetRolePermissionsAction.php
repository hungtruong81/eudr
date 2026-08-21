<?php

declare(strict_types=1);

namespace App\Application\Actions\User\Roles;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class SetRolePermissionsAction extends RoleAction
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

        // Kiểm tra quyền: chỉ admin hoặc user có quyền role.manage mới được phép
        /*
        $scope = $this->userRepository->getCURDPermissionStatus($user_id, 'role', 'update');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Bạn không có quyền quản lý permissions cho role');
        }
        */
        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        // Lấy role_id từ route argument
        $role_id = (int)($this->resolveArg('role_id') ?? 0);
        if ($role_id <= 0) {
            throw new HttpBadRequestException($this->request, 'role_id không hợp lệ');
        }

        $validator->validate('permissions', $formData['permissions'] ?? null, 'required|array');

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
            'permissions' => 'array',
        ];
        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $permission_names = $cleanData['permissions'] ?? [];

        if (!is_array($permission_names)) {
            throw new HttpBadRequestException($this->request, 'permissions phải là một mảng');
        }

        // Sanitize: trim, loại bỏ rỗng, loại bỏ trùng
        $permission_names = array_values(array_unique(array_filter(
            array_map(fn($name) => trim((string)$name), $permission_names),
            fn($name) => !empty($name)
        )));

        // Kiểm tra role tồn tại
        $role = $this->userRepository->findRoleById($role_id);
        if (empty($role)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy role');
        }
        $role_name = $role['name'];

        // Validate các permission names tồn tại trong hệ thống
        $all_permissions = $this->userRepository->getAllPermissions();
        $valid_perm_names = array_column($all_permissions, 'name');

        $invalid_names = array_diff($permission_names, $valid_perm_names);
        if (!empty($invalid_names)) {
            throw new HttpBadRequestException(
                $this->request,
                'Các permission không hợp lệ: ' . implode(', ', $invalid_names)
            );
        }

        // Chuyển tên permission sang ID
        $permission_ids = $this->userRepository->mapPermissionNamesToIds($permission_names);

        // Lấy permissions cũ trước khi cập nhật
        $old_permissions = $this->userRepository->getRolePermissionsByRoleId($role_id);
        $old_perm_names = array_column($old_permissions, 'name');

        // Cập nhật permissions
        $success = $this->userRepository->updateRolePermissions($role_id, $permission_ids);
        if (!$success) {
            throw new HttpBadRequestException($this->request, 'Cập nhật permissions thất bại');
        }

        // Lấy permissions mới sau khi cập nhật
        $new_permissions = $this->userRepository->getRolePermissionsByRoleId($role_id);
        $new_perm_names = array_column($new_permissions, 'name');

        // Tính toán thay đổi
        $added = array_values(array_diff($new_perm_names, $old_perm_names));
        $removed = array_values(array_diff($old_perm_names, $new_perm_names));

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'role',
            'action' => 'set_role_permissions',
            'user_id' => (string)$user_id,
            'extra_1' => "role:{$role_name}|added:" . count($added) . "|removed:" . count($removed),
        ];
        Utils::save_log($this->logger, $log);

        $res_return = [
            'result' => 'success',
            'role_id' => $role_id,
            'role_name' => $role_name,
            'permissions_added' => $added,
            'permissions_removed' => $removed,
            'current_permissions' => array_column($new_permissions, 'name'),
            'current_permissions_detail' => $new_permissions,
            'total_permissions' => count($new_permissions),
            'trace_id' => $trace_id,
        ];

        return $this->respondWithData($res_return, 200);
    }
}
