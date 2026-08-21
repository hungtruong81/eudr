<?php

declare(strict_types=1);

namespace App\Application\Actions\User\CompanyMembers;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;

class DeleteMemberAction extends CompanyMemberAction
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

        // Check permission to delete company member
        $scope = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'company_member', 'delete');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $user_code = addslashes(trim((string)$this->resolveArg('code')));

        $user = $this->companyMemberRepository->findMemberOfCodeWithPermission(
            $user_code,
            (int)$this->auth_data['user_id'],
            (string)$scope
        );
    
        if (empty($user)) {
            throw new HttpNotFoundException($this->request, "Người dùng không tồn tại");
        }

        if ($user->getId() === $this->auth_data['user_id']) {
            throw new HttpBadRequestException($this->request, "Không thể xóa chính mình");
        }

        $this->companyMemberRepository->deleteMemberWithPermission(
            $user->getId(),
            (int)$this->auth_data['user_id'],
            (string)$scope
        );

        $action = 'delete_company_member';
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

        return $this->respondWithData($res_return);
    }
}
