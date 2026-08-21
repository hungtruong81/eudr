<?php

declare(strict_types=1);

namespace App\Application\Actions\Connection;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use App\Application\Utility\Validator;
use App\Application\Utility\Utils;


class ConnectionRequestRespondAction extends ConnectionAction
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
        $validator->validate('action', $formData['action'] ?? null, 'required|in:accept,reject');
        $validator->validate('rejection_reason', $formData['rejection_reason'] ?? null, 'string|max:255');

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
            'action' => 'string',
            'rejection_reason' => 'string'
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $connection_id = $cleanData['connection_id'];
        $action = $cleanData['action'];
        $rejection_reason = $cleanData['rejection_reason'] ?? '';
        $data_update = [
            "rejection_reason" => $rejection_reason,
            "action" => $action
        ];

        // Respond to connection request
        $responded_successfully = $this->connectionRepository->respondConnectionRequest($connection_id, (int)$this->auth_data['user_id'], $data_update);
        if (empty($responded_successfully)) {
            $message = $validator->getErrorMessage('connection_module.user_respond_failed', []);
            throw new HttpBadRequestException($this->request, $message);
        }

        $action = 'request_respond';
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
