<?php

declare(strict_types=1);

namespace App\Application\Actions\Sales\Orders;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class DeleteOrderAction extends OrderAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'sales_order', 'delete');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $sale_order_code = addslashes(trim((string)$this->resolveArg('code')));
        $order = $this->salesOrderRepository->findOrderOfCodeWithPermission(
            $sale_order_code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null,
            $this->auth_data['company_id'] ?? null
        );

        if (!$order || !$order->getId()) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy đơn hàng');
        }

        $orderData = $order->jsonSerialize();
        // if (($orderData['status'] ?? 'draft') !== 'draft') {
        //     throw new HttpBadRequestException($this->request, 'Chỉ xóa đơn hàng ở trạng thái draft');
        // }

        $data = [
            'deleted_by' => $this->auth_data['user_id'],
            'deleted_at' => date('Y-m-d H:i:s'),
        ];

        $ok = $this->salesOrderRepository->deleteOrderWithPermission(
            (int)$order->getId(),
            $data,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null,
            $this->auth_data['company_id'] ?? null
        );

        if (!$ok) {
            throw new HttpBadRequestException($this->request, 'Không thể xóa đơn hàng');
        }

        $action = 'delete';
        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'sales_order',
            'action' => $action,
            'user_id' => (string)$this->auth_data['user_id'],
            'extra_1' => (string)$order->getId(),
        ];
        Utils::save_log($this->logger, $log);

        $res = ['result' => 'success', 'trace_id' => $trace_id];
        return $this->respondWithData($res);
    }
}