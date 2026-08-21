<?php

declare(strict_types=1);

namespace App\Application\Actions\Connection;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use App\Application\Utility\Validator;
use App\Application\Utility\Utils;


class ConnectionRequestCancelAction extends ConnectionAction
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

        $formData = $this->getFormData();

        $validator = new Validator($this->request);

        $validator->validate('connection_id', $formData['connection_id'] ?? null, 'required|integer');

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
            'connection_id' => 'integer',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $connection_id = $cleanData['connection_id'];

        // Cancel connection request
        $cancelled_successfully = $this->connectionRepository->cancelConnectionRequest($connection_id, (int)$this->auth_data['user_id']);
        if (empty($cancelled_successfully)) {
            $message = $validator->getErrorMessage('connection_module.user_cancel_failed', []);
            throw new HttpBadRequestException($this->request, $message);
        }

        // Add notification to target user
        /*
        $title = "Hủy yêu cầu kết nối mới";
        $message = "Bạn có một yêu cầu hủy kết nối từ " . $this->auth_data['full_name'];
        $this->notificationRepository->createNotification([
            'user_id' => $user_target->getId(),
            'title' => $title,
            'type' => 'connection_cancel',
            'message' => $message,
            'related_id' => $connection_request['connection_id'],
            'related_code' => $connection_request['connection_code'],
            'related_type' => 'connection',
        ]);
        */
        // Log action
        $action = 'request_cancel';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'connection',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$connection_id,
        );

        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
        
    }
}
