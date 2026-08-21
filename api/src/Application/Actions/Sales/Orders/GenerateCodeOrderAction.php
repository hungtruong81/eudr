<?php

declare(strict_types=1);

namespace App\Application\Actions\Sales\Orders;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpForbiddenException;


class GenerateCodeOrderAction extends OrderAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'sales_order', 'create');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $sale_order_code = $this->salesOrderRepository->generateCode();

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'sales_order',
            'action' => 'generate_code',
            'user_id' => (string)$this->auth_data['user_id'],
            'extra_1' => (string)$sale_order_code,
        ];

        Utils::save_log($this->logger, $log);

        $res = ['result' => 'success', 'trace_id' => $trace_id, 'data' => ['sale_order_code' => $sale_order_code]];
        
        return $this->respondWithData($res);
    }
}
