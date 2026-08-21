<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingOrder\Item;

use App\Application\Actions\PurchasingOrder\PurchasingOrderAction;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class DeletePurchasingOrderItemAction extends PurchasingOrderAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'purchasing_order', 'update');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $orderCode = addslashes(trim((string)$this->resolveArg('code')));
        $itemId = (int)$this->resolveArg('item_id');
        if ($itemId <= 0) {
            throw new HttpBadRequestException($this->request, 'item_id không hợp lệ');
        }

        $order = $this->purchasingOrderRepository->findOrderOfCodeWithPermission(
            $orderCode,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            (int)($this->auth_data['company_id'] ?? 0)
        );

        if (empty($order)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy phiếu thu mua');
        }

        if ($order->getStatus() !== 'draft') {
            throw new HttpBadRequestException($this->request, 'Chỉ được xóa dòng hàng khi phiếu ở trạng thái draft');
        }

        $ok = $this->purchasingOrderRepository->deleteOrderItemWithPermission(
            (int)$order->getId(),
            $itemId,
            (int)$this->auth_data['user_id'],
            (int)$this->auth_data['user_id'],
            (string)$scope,
            (int)($this->auth_data['company_id'] ?? 0)
        );

        if (!$ok) {
            throw new HttpBadRequestException($this->request, 'Không thể xóa dòng hàng hóa');
        }

        $freshOrder = $this->purchasingOrderRepository->findOrderOfCodeWithPermission(
            $orderCode,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            (int)($this->auth_data['company_id'] ?? 0)
        );

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'purchase_order' => $freshOrder ? $freshOrder->jsonSerialize() : null,
        ]);
    }
}
