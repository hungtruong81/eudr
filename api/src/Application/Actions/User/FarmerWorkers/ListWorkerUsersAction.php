<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\User\UserErrorException;
use App\Application\Utility\Utils;

class ListWorkerUsersAction extends UserAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        $formData = $this->request->getQueryParams();
        $params = ["page_limit" => 10];
        if (isset($formData["active"])) {
            $params["active"] = $formData["active"]?1:0;
        }

        if (empty($this->auth_data['user_id'])) {
            throw new UserErrorException("Thiếu thông tin người dùng", 113);
        }

        // Check permission
        $permission_status = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'worker', 'view');

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

        $search = "";
        if (!empty($formData['search'])) {
            $search = htmlspecialchars(trim($formData['search']));
        }

        $params = [
            "page" => $page,
            "page_limit" => $limit,
            "search" => $search,
            "permission_status" => $permission_status,
            "user_id" => $this->auth_data['user_id'],
        ];
        
        $data_worker_users = $this->userRepository->findAllWorkerUsers($params);
        
        $action = 'list_worker_users';
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
        $res_return['data'] = $data_worker_users;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }
}
