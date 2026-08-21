<?php

declare(strict_types=1);

namespace App\Application\Actions\Authentication;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use App\Application\Utility\Utils;

class ExternalCreateTokenAction extends AuthenticationAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // $displayErrorDetails = $this->settings->get('displayErrorDetails');
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        $formData = $this->getFormData();
        // empty($formData['captcha']) ||
        if (empty($formData['service_code'])) {
            throw new HttpBadRequestException($this->request, "Missing parameters");
        }
        if (empty($this->auth_data['permission']['admin'])) {
            $res_return =  [
                "result" => 'fail',
                "error" => [
                    "code" => 'PERMISSION_DENIED',
                    "description" => "Permission denied",
                ]
            ];
            $res_return['trace_id'] = $trace_id;
            return $this->respondWithData($res_return, 201);
        }


        $this->db->where("service_code", $formData["service_code"]);
        $this->db->where("is_active", 1);
        $auth_data = $this->db->getOne("w_service");
        if (empty($auth_data)) {
            $res_return =  [
                "result" => 'fail',
                "error" => [
                    "code" => 'SERVICE_NOT_FOUND',
                    "description" => "Service not found",
                ]
            ];
            $res_return['trace_id'] = $trace_id;
            return $this->respondWithData($res_return, 201);
        }
        // Get secret key by product_code ($data_jwt) in database/cache to verify this access token
        $secret_jwt = $auth_data["secret_key"];

        $token_data = [
            'type' => "app",
            'service_name' => $auth_data["name"],
            'service_code' => $auth_data["service_code"],
        ];

        $access_token = Utils::generateExternalTokenAuth($token_data, $secret_jwt);

        $res_return =  [
            "access_token" => $access_token,
            "type" => 'app',
            "result" => 'success',
        ];
        $res_return['trace_id'] = $trace_id;
        return $this->respondWithData($res_return, 200);
    }
}
