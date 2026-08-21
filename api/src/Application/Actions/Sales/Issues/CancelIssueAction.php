<?php

declare(strict_types=1);

namespace App\Application\Actions\Sales\Issues;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class CancelIssueAction extends IssueAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'sales_issue', 'cancel');
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

        $issueData = $issue->jsonSerialize();
        if (($issueData['status'] ?? 'draft') !== 'issued') {
            throw new HttpBadRequestException($this->request, 'Chỉ hủy phiếu ở trạng thái issued');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);
        $validator->validate('notes', $formData['notes'] ?? null, 'string|max:500');

        if ($validator->hasErrors()) {
            $errors = [];
            foreach ((array)$validator->getErrors() as $fieldErrors) {
                $errors[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errors));
        }

        $clean = $validator->sanitize($formData, [
            'notes' => 'string',
        ]);

        $data = [
            'notes' => $clean['notes'] ?? ($issueData['notes'] ?? null),
            'updated_by' => $this->auth_data['user_id'],
            'cancelled_at' => date('Y-m-d H:i:s'),
            'cancelled_by' => $this->auth_data['user_id'],
        ];

        $updated = $this->salesIssueRepository->cancelIssueWithPermission(
            (int)$issue->getId(),
            $data,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null,
            $this->auth_data['company_id'] ?? null
        );

        if (!$updated) {
            throw new HttpBadRequestException($this->request, 'Không thể hủy phiếu xuất kho');
        }

        $res = ['result' => 'success', 'issue' => $updated->jsonSerialize(), 'trace_id' => $trace_id];
        return $this->respondWithData($res);
    }
}
