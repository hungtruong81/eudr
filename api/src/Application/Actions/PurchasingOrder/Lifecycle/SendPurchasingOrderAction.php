<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingOrder\Lifecycle;

use App\Application\Actions\PurchasingOrder\PurchasingOrderAction;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class SendPurchasingOrderAction extends \App\Application\Actions\PurchasingOrder\PurchasingOrderAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        //$scope = Utils::resolveScope($permissions, 'purchasing_order', 'send');
        $scope = Utils::resolveScope($permissions, 'purchasing_order', 'update');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $orderCode = addslashes(trim((string)$this->resolveArg('code')));
        $order = $this->purchasingOrderRepository->findOrderOfCodeWithPermission(
            $orderCode,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            (int)($this->auth_data['company_id'] ?? 0)
        );

        if (empty($order)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy phiếu thu mua');
        }

        $orderData = $order->jsonSerialize();
        if (($orderData['status'] ?? 'draft') !== 'draft') {
            throw new HttpBadRequestException($this->request, 'Chỉ được gửi phiếu ở trạng thái draft');
        }

        if (empty($orderData['seller_name'])) {
            throw new HttpBadRequestException($this->request, 'Thiếu thông tin bên bán');
        }

        $items = is_array($orderData['items'] ?? null) ? $orderData['items'] : [];
        if (empty($items)) {
            throw new HttpBadRequestException($this->request, 'Phiếu chưa có dòng hàng hóa để gửi seller');
        }

        $sent = $this->purchasingOrderRepository->sendOrderWithPermission(
            (int)$order->getId(),
            [
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => (int)$this->auth_data['user_id'],
            ],
            (int)$this->auth_data['user_id'],
            (string)$scope,
            (int)($this->auth_data['company_id'] ?? 0)
        );

        if (empty($sent)) {
            throw new HttpBadRequestException($this->request, 'Không thể gửi phiếu thu mua');
        }

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'purchase_order' => $sent->jsonSerialize(),
        ]);
    }
}
