<?php

declare(strict_types=1);

namespace App\Application\Actions\TransportationRoute;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;


class CancelTransportationRouteAction extends TransportationRouteAction
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

        // Check permission to update transportation routes
        $scope = Utils::resolveScope($permissions, 'transportation_route', 'update');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $transportation_route_code = addslashes(trim((string)$this->resolveArg('code')));

        $transportation_route = $this->transportationRouteRepository->findTransportationRouteOfCodeWithPermission(
            $transportation_route_code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($transportation_route)) {
            throw new HttpBadRequestException($this->request, "Không tìm thấy lộ trình vận chuyển");
        }

        $formData = $this->getFormData();

        $validator = new Validator($this->request);

        // Data Transportation Route
        $data_update = [
            "status" => 'cancelled',
            "updated_at" => date("Y-m-d H:i:s", time()),
            "updated_by" => $this->auth_data['user_id'],
        ];

        $transportationRoute = $this->transportationRouteRepository->updateTransportationRouteWithPermission(
            $transportation_route->getId(),
            $data_update,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        $action = 'cancelled';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'transportation_route',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$transportationRoute->getId(),
        );

        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['transportation_route'] = $transportationRoute->jsonSerialize();

        return $this->respondWithData($res_return);
        
    }
}
