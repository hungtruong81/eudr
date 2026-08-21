<?php

declare(strict_types=1);

namespace App\Application\Actions\Notification;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;


class ListNotificationAction extends NotificationAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        // Check Authentication
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }
        
        $formData = $this->request->getQueryParams();

        $validator = new Validator($this->request);

        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('status', $formData['status'] ?? null, 'required|in:all,unread,read');
        $validator->validate('related_type', $formData['related_type'] ?? null, 'string|in:all,connection,transaction_ticket');

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
            'status' => 'string',
            'related_type' => 'string',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $page = $cleanData['page'];
        $limit = $cleanData['limit'];
        $status = $cleanData['status'];
        $related_type = $cleanData['related_type'] ?? 'all';
        
        $params = [
            "page" => $page,
            "page_limit" => $limit,
            "status" => $status,
            "related_type" => $related_type,
            "user_id" => $this->auth_data['user_id'],
        ];

        $notifications = $this->notificationRepository->findAll($params);

        $res_return = ["result" => "success"];
        $res_return['data'] = $notifications;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }

}
