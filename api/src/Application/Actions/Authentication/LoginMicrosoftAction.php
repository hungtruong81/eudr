<?php

declare(strict_types=1);

namespace App\Application\Actions\Authentication;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use App\Application\Utility\Utils;

class LoginMicrosoftAction extends AuthenticationAction
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
        // $formData = $this->request->getQueryParams();
        if (empty($formData['token'])) {
            throw new HttpBadRequestException($this->request, "Missing parameters");
        }

        $access_token = $formData['token'];
        $providedState = $formData['state'] ?? '';

        $email = '';
        try {

            // create curl user info with access_token https://graph.microsoft.com/v1.0/me
            $client = new \GuzzleHttp\Client();
            $res = $client->request('GET', 'https://graph.microsoft.com/v1.0/me', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type' => 'application/json'
                ]
            ]);

            $user = json_decode($res->getBody()->getContents(), true);

            $email = $user['mail'] ?? '';
            if (empty($email)) {
                $email = $user['userPrincipalName'] ?? '';
            }
            $name = $user['displayName'] ?? '';
            $avatar = $user['photo'] ?? '';
        } catch (\Exception $e) {
            throw new HttpBadRequestException($this->request, $e->getMessage());
        }


        // Verify user
        $this->db->where("email", $email);
        $this->db->where("active", 1);
        $data_user = $this->db->getOne("w_user");

        if (!$data_user) {
            throw new HttpBadRequestException($this->request, "PERMISSION_DENIED");
        } else {
            $data_update = [
                'full_name' => $name,
                'first_name' => $name,
                'avatar' => $avatar,
            ];
            $user = $this->userRepository->updateUser($data_user['user_id'], $data_update);
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
            "extra_1" => 'microsoft',
        );
        Utils::save_log($this->logger, $log);

        $res_return =  [
            "access_token" => $access_token,
            "type" => 'auth',
            "result" => 'success',
        ];
        $res_return['trace_id'] = $trace_id;
        return $this->respondWithData($res_return, 200);
    }
}
