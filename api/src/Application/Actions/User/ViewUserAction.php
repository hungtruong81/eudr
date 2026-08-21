<?php
declare(strict_types=1);

namespace App\Application\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\User\UserNotFoundException;
use App\Domain\User\UserErrorException;
use App\Application\Utility\Utils;

class ViewUserAction extends UserAction
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
            throw new UserErrorException("Thiếu quyền truy cập", 113);
        }

        $user_code = $this->resolveArg('code');
        $user = $this->userRepository->findUserOfCode($user_code);
        if (empty($user)) {
            throw new UserNotFoundException("Người dùng bạn yêu cầu không tồn tại.", 102);
        }

        $action = 'view';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'user',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$user->getId(),
        );
        Utils::save_log($this->logger,$log);

        $res_return = ["result" => "success"];
        $res_return['data'] = $user->jsonSerialize();
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }
}
