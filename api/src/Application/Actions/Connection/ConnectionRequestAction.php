<?php

declare(strict_types=1);

namespace App\Application\Actions\Connection;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Validator;
use App\Application\Utility\Utils;


class ConnectionRequestAction extends ConnectionAction
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

        $validator->validate('target_user_code', $formData['target_user_code'] ?? null, 'required|string');
        $validator->validate('connection_method', $formData['connection_method'] ?? null, 'required|in:phone,qrcode,other');
        $validator->validate('notes', $formData['notes'] ?? null, 'string');

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
            'target_user_code' => 'string',
            'connection_method' => 'string',
            'notes' => 'string'
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $target_user_code = $cleanData['target_user_code'];
        $connection_method = $cleanData['connection_method'];
        $notes = $cleanData['notes'] ?? '';

        // Find target user
        $user_target = $this->userRepository->findUserOfCode($target_user_code);
        if (empty($user_target)) {
            $message = $validator->getErrorMessage('connection_module.user_target_not_found', []);
            throw new HttpNotFoundException($this->request, $message);
        }

        // Cannot connect to self
        if ($user_target->getId() == $this->auth_data['user_id']) {
            $message = $validator->getErrorMessage('connection_module.cannot_connect_self', []);
            throw new HttpBadRequestException($this->request, $message);
        }

        // Check existing connection
        $existing_connection = $this->connectionRepository->findConnectionBetweenUsers($this->auth_data['user_id'], $user_target->getId());
        if (!empty($existing_connection)) {
            $message = $validator->getErrorMessage('connection_module.request_connection_exists', []);
            throw new HttpBadRequestException($this->request, $message);
        }
        
        // Data connection request
        $data_update = [
            "requester_company_id" => $this->auth_data['company_id'] ?? 0,
            "requester_user_id" => $this->auth_data['user_id'],
            "target_company_id" => $user_target->getCompanyId() ?? 0,
            "target_user_id" => $user_target->getId(),
            "connection_method" => $connection_method,
            "status" => "pending",
            "requested_at" => date("Y-m-d H:i:s", time()),
            "created_at" => date("Y-m-d H:i:s", time()),
            "notes" => $notes,
            "updated_at" => NULL,
        ];

        $connection_request = $this->connectionRepository->createConnectionRequest($data_update);
        if (empty($connection_request)) {
            $message = $validator->getErrorMessage('connection_module.request_failed', []);
            throw new HttpBadRequestException($this->request, $message);
        }

        // add notification to target user
        $title = "Yêu cầu kết nối mới";
        $message = "Bạn có một yêu cầu kết nối mới từ " . $this->auth_data['full_name'];
        $this->notificationRepository->createNotification([
            'user_id' => $user_target->getId(),
            'title' => $title,
            'type' => 'connection_request',
            'message' => $message,
            'related_id' => $connection_request['connection_id'],
            'related_code' => $connection_request['connection_code'],
            'related_type' => 'connection',
        ]);

        // Log action
        $action = 'request';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'connection',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$user_target->getId(),
        );

        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
        
    }
}
