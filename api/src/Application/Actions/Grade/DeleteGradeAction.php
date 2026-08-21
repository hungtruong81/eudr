<?php

declare(strict_types=1);

namespace App\Application\Actions\Grade;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;

class DeleteGradeAction extends GradeAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        // Check authentication
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);

        // Check permission to delete grade
        $scope = Utils::resolveScope($permissions, 'grade', 'delete');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $grade_code = addslashes(trim((string)$this->resolveArg('code')));

        $grade = $this->gradeRepository->findGradeOfCodeWithPermission(
            $grade_code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        if (empty($grade)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy grade');
        }

        $this->gradeRepository->deleteGradeWithPermission(
            $grade->getId(),
            (int)$this->auth_data['user_id'],
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        $action = 'delete';
        $log = array(
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'grade',
            'action' => $action,
            'user_id' => (string)$this->auth_data['user_id'],
            'extra_1' => (string)$grade->getId(),
        );

        Utils::save_log($this->logger, $log);

        $res_return = ['result' => 'success'];
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }
}
