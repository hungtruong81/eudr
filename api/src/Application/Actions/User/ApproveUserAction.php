<?php
declare(strict_types=1);

namespace App\Application\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\User\UserNotFoundException;
use App\Domain\User\UserErrorException;
use App\Application\Utility\Utils;

class ApproveUserAction extends UserAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        // Check permission
        $user_has_permission = $this->userRepository->userHasPermission($this->auth_data['user_id'], 'user.approve');
        if (empty($user_has_permission)) {
            throw new UserErrorException("Bạn không có quyền phê duyệt người dùng", 113);
        }

        $user_code = $this->resolveArg('code');
        
        $user = $this->userRepository->findUserOfCode($user_code);
        
        if (empty($user)) {
            throw new UserNotFoundException("Người dùng bạn yêu cầu không tồn tại.", 102);
        }

        $data_update = [
            'updated_by' => $this->auth_data['user_id'],
            'updated_at' => date('Y-m-d H:i:s'),
            'is_approved' => 1,
        ];

        $user = $this->userRepository->updateUser($user->getId(), $data_update);

        // Assign role to user
        $this->userRepository->assignRoleToUser($user->getId(), 'farmer');

        $action = 'approve_user';
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
        $res_return['data'] = $user->jsonSerialize();
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }
}
