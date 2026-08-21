<?php

declare(strict_types=1);

namespace App\Application\Actions\Authentication;

use Psr\Http\Message\ResponseInterface as Response;
use App\Application\Utility\Utils;

class UserInfoAction extends AuthenticationAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);
        
        $user = $this->userRepository->findUserOfPhone($this->auth->phone);

        // Get user permissions
        $permissions = $this->userRepository->getUserPermissions($user->getId());

        // Get user roles (multi-role support)
        $user_roles = $this->userRepository->getUserRoles($this->auth_data['user_id']);

        // Get primary role (backward compatible)
        $user_role = $this->userRepository->getUserRole($this->auth_data['user_id']);

        $res_return = ["result" => "success"];
        $res_return["user"] = $user->jsonSerialize();
        //$res_return["user"]['user_role'] = $user_role;
        $res_return["user"]['user_roles'] = $user_roles;
        $res_return["user"]['permissions'] = $permissions;
        

        $res_return['trace_id'] = $trace_id;
        return $this->respondWithData($res_return, 200);
    }
}
