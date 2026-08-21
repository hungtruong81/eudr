<?php

declare(strict_types=1);

namespace App\Application\Actions\Connection;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class ConnectionSearchAction extends ConnectionAction
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

        $validator->validate('phone', $formData['phone'] ?? null, ['required', 'regex:/^0[0-9]{9}$/']);

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
            'phone' => 'string',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $phone = $cleanData['phone'];


        $data_user = $this->connectionRepository->searchUserOfPhone($phone, (int)$this->auth_data['user_id']);

        // If inspector, only search farmer
        if(!empty($this->auth_data['register_type']) && $this->auth_data['register_type'] == 'inspector') {
            if(!empty($data_user) && $data_user['register_type'] != 'farmer') {
                $data_user = [];
            }
        }

        // Enrich: thêm user_roles cho user tìm được
        if (!empty($data_user) && !empty($data_user['user_id'])) {
            $data_user['user_roles'] = $this->userRepository->getUserRoles((int)$data_user['user_id']);
        }

        $action = 'search_user_phone';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'connection',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => '',
        );

        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['data'] = $data_user;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }
}
