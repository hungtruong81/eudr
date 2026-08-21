<?php

declare(strict_types=1);

namespace App\Application\Actions\User\CompanyMembers;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class ListMemberAction extends CompanyMemberAction
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

        // Check permission to view company members
        $scope = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'company_member', 'view');
        
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }
        
        $formData = $this->request->getQueryParams();

        $validator = new Validator($this->request);

        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('search', $formData['search'] ?? null, 'string');
        $validator->validate('status', $formData['status'] ?? null, 'string|in:active,inactive,all');
        $validator->validate('company_id', $formData['company_id'] ?? null, 'integer');
        $validator->validate('register_type', $formData['register_type'] ?? null, 'string|in:farmer,purchaser,trader,company,inspector,transport,factory,sales,all');

        if ($validator->hasErrors()) {
            $errorMessages = [];
            $errors = $validator->getErrors();
            if (is_array($errors)) {
                foreach ($errors as $field => $fieldErrors) {
                    if (is_array($fieldErrors)) {
                        $errorMessages[] = implode(', ', $fieldErrors);
                    } else {
                        $errorMessages[] = (string)$fieldErrors;
                    }
                }
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errorMessages));
        }

        // Sanitize and extract data
        $sanitizeRules = [
            'page' => 'integer',
            'limit' => 'integer',
            'search' => 'string',
            'status' => 'string',
            'company_id' => 'integer',
            'register_type' => 'string',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $page = $cleanData['page'];
        $limit = $cleanData['limit'];
        $search = $cleanData['search'] ?? null;
        $status = $cleanData['status'] ?? 'all';
        $company_id_param = $cleanData['company_id'] ?? 0;
        $register_type = $cleanData['register_type'] ?? 'all';

        $params = [
            "page" => $page,
            "page_limit" => $limit,
            "search" => $search,
            "status" => $status,
            "user_id" => $this->auth_data['user_id'],
            "company_id_param" => $company_id_param,
            "register_type" => $register_type,
            //"permission_status" => $permission_status,
        ];

        $data_company_members = $this->companyMemberRepository->findAllMembers(
            $params,
            (int)$this->auth_data['user_id'],
            (string)$scope
        );

        // Enrich: thêm user_roles cho mỗi member
        if (!empty($data_company_members['records'])) {
            $memberUserIds = [];
            foreach ($data_company_members['records'] as $record) {
                $uid = (int)$record->getId();
                if ($uid > 0) {
                    $memberUserIds[$uid] = true;
                }
            }

            $rolesMap = [];
            foreach (array_keys($memberUserIds) as $uid) {
                $rolesMap[$uid] = $this->userRepository->getUserRoles($uid);
            }

            $enrichedRecords = [];
            foreach ($data_company_members['records'] as $record) {
                $uid = (int)$record->getId();
                $item = $record->jsonSerialize();
                $item['user_roles'] = $rolesMap[$uid] ?? [];
                $enrichedRecords[] = $item;
            }
            $data_company_members['records'] = $enrichedRecords;
        }
        
        $action = 'list_company_members';
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
        $res_return['data'] = $data_company_members;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }
}
