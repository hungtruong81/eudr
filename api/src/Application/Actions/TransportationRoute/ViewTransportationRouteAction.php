<?php

declare(strict_types=1);

namespace App\Application\Actions\TransportationRoute;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;


class ViewTransportationRouteAction extends TransportationRouteAction
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

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);

        // Check permission to view transportation routes
        $scope = Utils::resolveScope($permissions, 'transportation_route', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }
    
        $formData = $this->request->getQueryParams();

        $transportation_route_code = addslashes(trim((string)$this->resolveArg('code')));

        $transportation_route = $this->transportationRouteRepository->findTransportationRouteOfCodeWithPermission(
            $transportation_route_code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($transportation_route)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy lộ trình vận chuyển");
        }

        $action = 'view';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'transportation_route',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$transportation_route->getId(),
        );
        
        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['data'] = $transportation_route->jsonSerialize();

        return $this->respondWithData($res_return);
    }
}
