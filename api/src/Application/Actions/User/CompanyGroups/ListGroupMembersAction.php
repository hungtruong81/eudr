<?php

declare(strict_types=1);

namespace App\Application\Actions\User\CompanyGroups;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class ListGroupMembersAction extends CompanyGroupAction
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

        $scope = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'company_group', 'view');
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
            throw new HttpNotFoundException($this->request, "Nhóm quyền không tồn tại");
        }

        $formData = $this->request->getQueryParams();
        $validator = new Validator($this->request);

        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('search', $formData['search'] ?? null, 'string');

        if ($validator->hasErrors()) {
            $errorMessages = [];
            $errors = $validator->getErrors();
            if (is_array($errors)) {
                foreach ($errors as $fieldErrors) {
                    if (is_array($fieldErrors)) {
                        $errorMessages[] = implode(', ', $fieldErrors);
                    } else {
                        $errorMessages[] = (string)$fieldErrors;
                    }
                }
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errorMessages));
        }

        $sanitizeRules = [
            'page' => 'integer',
            'limit' => 'integer',
            'search' => 'string',
        ];
        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $params = [
            'page' => $cleanData['page'],
            'page_limit' => $cleanData['limit'],
            'search' => $cleanData['search'] ?? '',
        ];

        $members = $this->companyGroupRepository->getGroupMembers((int)$group->getId(), $params);

        $action = 'list_company_group_members';
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
        //$res_return['group'] = $group->jsonSerialize();
        $res_return['data'] = $members;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }
}
