<?php

declare(strict_types=1);

namespace App\Application\Actions\User\CompanyGroups;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;

class DeleteGroupAction extends CompanyGroupAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        // Check permission to delete company groups
        $scope = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'company_group', 'delete');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $company_group_code = addslashes(trim((string)$this->resolveArg('code')));

        $group = $this->companyGroupRepository->findGroupOfCodeWithPermission(
            $company_group_code,
            (int)$this->auth_data['user_id'],
            (string)$scope
        );

        if (empty($group)) {
            throw new HttpNotFoundException($this->request, "Nhóm người dùng không tồn tại hoặc bạn không có quyền xóa nhóm này");
        }

        $this->companyGroupRepository->deleteGroupWithPermission(
            (int)$group->getId(),
            (int)$this->auth_data['user_id'],
            (string)$scope
        );

        $action = 'delete_company_group';
        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'company_group',
            'action' => $action,
            'user_id' => (string)$this->auth_data['user_id'],
            'extra_1' => (string)$group->getId(),
        ];

        Utils::save_log($this->logger, $log);

        $res_return = ['result' => 'success'];
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }
}
