<?php

declare(strict_types=1);

namespace App\Application\Actions\Sales\Issues;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class ViewIssueAction extends IssueAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'sales_issue', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $issue_code = addslashes(trim((string)$this->resolveArg('code')));
        $issue = $this->salesIssueRepository->findIssueOfCodeWithPermission(
            $issue_code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null,
            $this->auth_data['company_id'] ?? null
        );

        if (!$issue || !$issue->getId()) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy phiếu xuất kho');
        }

        $res = ['result' => 'success', 'issue' => $issue->jsonSerialize(), 'trace_id' => $trace_id];
        return $this->respondWithData($res);
    }
}
