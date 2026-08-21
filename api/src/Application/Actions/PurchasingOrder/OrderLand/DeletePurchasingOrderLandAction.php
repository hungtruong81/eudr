<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingOrder\OrderLand;

use App\Application\Actions\PurchasingOrder\PurchasingOrderAction;
use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class DeletePurchasingOrderLandAction extends PurchasingOrderAction
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
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập vườn nguồn');
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
        $orderData = $order->jsonSerialize();
        $status = (string)($orderData['status'] ?? '');
        $sellerSourceType = (string)($orderData['seller_source_type'] ?? '');
        $canChangeLands = in_array($status, ['draft', 'sent_to_seller'], true)
            || ($sellerSourceType === 'vendor' && $status === 'seller_confirmed');
        if (!$canChangeLands) {
            throw new HttpBadRequestException(
                $this->request,
                'Chỉ được thay đổi vườn nguồn trước khi bên mua xác nhận lại phiếu'
            );
        }

        $purchaseOrderLandId = (int)$this->resolveArg('id');

        if ($purchaseOrderLandId <= 0) {
            throw new HttpBadRequestException($this->request, 'id không hợp lệ');
        }

        $deleted = $this->purchasingOrderRepository->deleteOrderLandByOrderId(
            $purchaseOrderId,
            $purchaseOrderLandId,
            (int)$this->auth_data['user_id']
        );

        if (!$deleted) {
            throw new HttpBadRequestException($this->request, 'Không thể xóa vườn nguồn');
        }

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'purchase_order_id' => $purchaseOrderId,
            'records' => $this->purchasingOrderRepository->listOrderLandsByOrderId($purchaseOrderId),
        ]);
    }
}
