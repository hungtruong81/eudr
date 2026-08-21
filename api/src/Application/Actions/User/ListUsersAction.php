<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\User\UserErrorException;
use App\Application\Utility\Utils;

class ListUsersAction extends UserAction
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

        // Check permission
        $permission_status = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'user', 'view');
        
        if (empty($permission_status)) {
            throw new UserErrorException("Bạn không có quyền xem danh sách người dùng", 113);
        }

        $formData = $this->request->getQueryParams();

        $page = 1;
        if(!empty($formData['page'])) {
            $page = intval($formData['page']);
            if ($page < 1) {
                $page = 1;
            }

        }

        $limit = 10;
        if(!empty($formData['limit'])) {
            $limit = intval($formData['limit']);
            if ($limit < 1 || $limit > 100) {
                $limit = 10;
            }

        }

        $is_approved = -1;
        if (isset($formData['is_approved'])) {
            $is_approved = intval($formData['is_approved']);
        }
        
        $search = "";
        if (!empty($formData['search'])) {
            $search = htmlspecialchars(trim($formData['search']));
        }

        $register_type = "";
        if (!empty($formData['register_type'])) {
            $register_type = htmlspecialchars(trim($formData['register_type']));
            // Multi-role: accept all valid account types and role names
            $allowedTypes = ['farmer', 'worker', 'purchaser', 'trader', 'company', 'inspector', 'transport', 'factory', 'sales'];
            if (!in_array($register_type, $allowedTypes)) {
                throw new UserErrorException("Invalid register_type", 101);
            }
        }
        
        $params = [
            "page" => $page,
            "page_limit" => $limit,
            "is_approved" => $is_approved,
            "register_type" => $register_type,
            "search" => $search,
            "permission_status" => $permission_status,
            "user_id" => $this->auth_data['user_id'],
        ];

        $data = $this->userRepository->findAll($params);

        $action = 'list';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'user',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => '',
        );

        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['data'] = $data;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }
}
