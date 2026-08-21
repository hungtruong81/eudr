<?php

declare(strict_types=1);

namespace App\Application\Actions\Authentication;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use App\Application\Utility\Utils;
use App\Domain\User\User;

class LoginGoogleAction extends AuthenticationAction
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
        if (empty($formData['token'])) {
            throw new HttpBadRequestException($this->request, "Missing parameters");
        }

        $tokenId = $formData['token'];
        $response = file_get_contents('https://www.googleapis.com/oauth2/v3/tokeninfo?id_token=' . $tokenId);
        $result = json_decode($response, true);

        if (!$result['email_verified']) {
            throw new HttpBadRequestException($this->request, "WRONG_TOKEN");
        }
        $email = $result['email'];

        // Verify user
        $this->db->where("email", $email);
        $this->db->where("active", 1);
        $data_user = $this->db->getOne("w_user");

        if (!$data_user) {
            throw new HttpBadRequestException($this->request, "PERMISSION_DENIED");
        } else {
            $user = new User($data_user['user_id'], $data_user);
        }


        // $secret_jwt = $this->settings->get('authentication_private_key');
        $secret_jwt = $this->settings->get('authentication_private_key');
        $access_token = Utils::generateTokenAuth($user->jsonSerialize(), $secret_jwt);

        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'user',
            "action" => 'login',
            "user_id" => (string)$user->getId(),
            "extra_1" => 'google',
        );
        Utils::save_log($this->logger, $log);

        $res_return =  [
            "access_token" => $access_token,
            "type" => 'auth',
        ];
        $res_return['trace_id'] = $trace_id;
        return $this->respondWithData($res_return, 200);
    }
}
