<?php

declare(strict_types=1);

namespace App\Application\Actions\Notification;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use App\Application\Utility\Validator;
use App\Application\Utility\Utils;


class MarkAsReadNotificationAction extends NotificationAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        // Check user authentication
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        $formData = $this->getFormData();

        $validator = new Validator($this->request);

        $validator->validate('notification_ids', $formData['notification_ids'] ?? null, 'required|array');

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
        /*
        $sanitizeRules = [
            'notification_ids' => 'array',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);
        */
        
        $notification_ids = $formData['notification_ids'] ?? [];

        if (empty($notification_ids) || !is_array($notification_ids)) {
            throw new HttpBadRequestException($this->request, "notification_ids không hợp lệ");
        }

        // Mark notifications as read
        $this->notificationRepository->markAsRead($this->auth_data['user_id'], $notification_ids);

        $action = 'mark_as_read';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'notification',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => implode(',', $notification_ids),
        );

        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
        
    }
}
