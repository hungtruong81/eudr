<?php

declare(strict_types=1);

namespace App\Application\Actions\Notification;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;

class ViewNotificationAction extends NotificationAction
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

        $notification_code = addslashes(trim($this->resolveArg('code')));

        $notification = null;

        if (empty($notification)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy thông báo");
        }

        $action = 'view';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'driver',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$notification->getId(),
        );
        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['data'] = $notification->jsonSerialize();

        return $this->respondWithData($res_return);
    }
}
