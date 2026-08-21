<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingOrder\Item;

use App\Application\Actions\PurchasingOrder\PurchasingOrderAction;
use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class ListPurchasingOrderItemAction extends PurchasingOrderAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'purchasing_order', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $orderCode = trim((string)$this->resolveArg('code'));
        $order = $this->purchasingOrderRepository->findOrderOfCodeWithPermission(
            $orderCode,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            (int)($this->auth_data['company_id'] ?? 0)
        );

        if (empty($order)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy phiếu thu mua');
        }

        $purchaseOrderId = (int)$order->getId();

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'purchase_order_id' => $purchaseOrderId,
            'records' => $this->purchasingOrderRepository->listOrderItemsByOrderId($purchaseOrderId),
        ]);
    }
}
