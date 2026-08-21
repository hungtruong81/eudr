<?php

declare(strict_types=1);

namespace App\Application\Actions\Notification;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use App\Application\Utility\Utils;


class ListRelatedTypeAction extends NotificationAction
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
        
        $formData = $this->request->getQueryParams();

        $related_types = $this->notificationRepository->getRelatedTypes([]);

        $res_return = ["result" => "success"];
        $res_return['data'] = $related_types;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }

}
