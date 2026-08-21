<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingOrder\BuyerSubTank;

use App\Application\Actions\PurchasingOrder\PurchasingOrderAction;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class DeleteBuyerSubTankPurchasingOrderAction extends PurchasingOrderAction
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
            throw new HttpForbiddenException($this->request, 'Thiếu quyền xóa bình bên mua');
        }

        $buyerSubTankId = (int)$this->resolveArg('id');
        if ($buyerSubTankId <= 0) {
            throw new HttpBadRequestException($this->request, 'id không hợp lệ');
        }

        $orderCode = addslashes(trim((string)$this->resolveArg('code')));
        $order = $this->purchasingOrderRepository->findOrderOfCode($orderCode);
        if (empty($order)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy phiếu thu mua');
        }
        $purchaseOrderId = (int)$order->getId();

        $orderData = $order->jsonSerialize();
        if (($orderData['status'] ?? '') !== 'seller_confirmed') {
            throw new HttpBadRequestException(
                $this->request,
                'Chỉ xóa bình bên mua khi phiếu ở trạng thái seller_confirmed'
            );
        }

        $this->assertBuyerAccess($orderData, (string)$scope);

        $deleted = $this->purchasingOrderRepository->deleteBuyerSubTankByOrderId(
            $purchaseOrderId,
            $buyerSubTankId,
            (int)$this->auth_data['user_id']
        );
        if (!$deleted) {
            throw new HttpBadRequestException($this->request, 'Không thể xóa bình bên mua');
        }

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'purchase_order_id' => $purchaseOrderId,
            'data' => $this->purchasingOrderRepository->listBuyerSubTanksByOrderId(
                $purchaseOrderId,
                ['page' => 1, 'page_limit' => 100, 'status' => 'all']
            ),
        ]);
    }

    /**
     * @param array<string,mixed> $orderData
     */
    private function assertBuyerAccess(array $orderData, string $scope): void
    {
        if ($scope === 'all') {
            return;
        }

        $authUserId = (int)($this->auth_data['user_id'] ?? 0);
        $authCompanyId = (int)($this->auth_data['company_id'] ?? 0);
        if ((int)($orderData['buyer_user_id'] ?? 0) === $authUserId) {
            return;
        }

        $buyerCompanyId = (int)($orderData['buyer_company_id'] ?? 0);
        $orderCompanyId = (int)($orderData['company_id'] ?? 0);
        if ($scope === 'own' && ($buyerCompanyId === $authCompanyId || $orderCompanyId === $authCompanyId)) {
            return;
        }

        throw new HttpForbiddenException($this->request, 'Bạn không phải bên mua của phiếu này');
    }
}
