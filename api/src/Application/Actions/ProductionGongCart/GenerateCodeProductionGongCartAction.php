<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionGongCart;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

class GenerateCodeProductionGongCartAction extends ProductionGongCartAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'production_gong_cart', 'create');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $gong_cart_code = $this->productionGongCartRepository->generateCode();

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'production_gong_cart',
            'action' => 'generate_code',
            'user_id' => (string)$this->auth_data['user_id'],
            'extra_1' => (string)$gong_cart_code,
        ];
        Utils::save_log($this->logger, $log);

        $res_return = ['result' => 'success'];
        $res_return['trace_id'] = $trace_id;
        $res_return['data'] = ['gong_cart_code' => $gong_cart_code];

        return $this->respondWithData($res_return);
    }
}
