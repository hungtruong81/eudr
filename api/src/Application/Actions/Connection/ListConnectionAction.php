<?php

declare(strict_types=1);

namespace App\Application\Actions\Connection;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class ListConnectionAction extends ConnectionAction
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
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        $formData = $this->request->getQueryParams();

        $validator = new Validator($this->request);

        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('type', $formData['type'] ?? null, 'required|in:all,received,sent');
        $validator->validate('status', $formData['status'] ?? null, 'required|in:all,pending,cancelled,accepted,rejected,blocked');
        $validator->validate('account_type', $formData['account_type'] ?? null, 'in:farmer,purchaser,trader,company,inspector,transport,factory,sales');

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
            'type' => 'string',
            'status' => 'string',
            'account_type' => 'string'
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $page = $cleanData['page'];
        $limit = $cleanData['limit'];
        $type = $cleanData['type'];
        $status = $cleanData['status'];
        $account_type = $cleanData['account_type'] ?? null;
        
        $params = [
            "page" => $page,
            "page_limit" => $limit,
            "type" => $type,
            "status" => $status,
            "user_id" => $this->auth_data['user_id'],
            "account_type" => $account_type
        ];
        
        $connections = $this->connectionRepository->findAll($params);

        // Enrich: thêm user_roles cho mỗi record (roles của user đối tác)
        if (!empty($connections['records'])) {
            $currentUserId = (int)$this->auth_data['user_id'];
            // Thu thập user_id đối tác từ mỗi connection
            $partnerUserIds = [];
            foreach ($connections['records'] as $record) {
                $requesterId = (int)($record['requester_user_id'] ?? 0);
                $targetId = (int)($record['target_user_id'] ?? 0);
                $partnerId = ($requesterId === $currentUserId) ? $targetId : $requesterId;
                if ($partnerId > 0) {
                    $partnerUserIds[$partnerId] = true;
                }
            }

            // Batch lấy roles cho tất cả user đối tác
            $rolesMap = [];
            foreach (array_keys($partnerUserIds) as $uid) {
                $rolesMap[$uid] = $this->userRepository->getUserRoles($uid);
            }

            // Gắn user_roles vào mỗi record
            foreach ($connections['records'] as &$record) {
                $requesterId = (int)($record['requester_user_id'] ?? 0);
                $targetId = (int)($record['target_user_id'] ?? 0);
                $partnerId = ($requesterId === $currentUserId) ? $targetId : $requesterId;
                $record['user_roles'] = $rolesMap[$partnerId] ?? [];
            }
            unset($record);
        }

        $res_return = ["result" => "success"];
        $res_return['data'] = $connections;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }

}
