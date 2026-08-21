<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionOrder;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;

class GenerateCodeProductionOrderAction extends ProductionOrderAction
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

        // Load permissions once and resolve create scope
        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);

        // Require create permission to generate code
        $scope = Utils::resolveScope($permissions, 'production_order', 'create');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $production_order_code = $this->productionOrderRepository->generateCode();

        $action = 'generate_code';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'production_order',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$production_order_code,
        );
        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['data'] = ['production_order_code' => $production_order_code];

        return $this->respondWithData($res_return);
    }
}
