<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingOrder\SellerSubTank;

use App\Application\Actions\PurchasingOrder\PurchasingOrderAction;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class DeleteSellerSubTankPurchasingOrderAction extends PurchasingOrderAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'purchasing_order', 'seller_confirm');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền xóa bình bên bán');
        }

        $sellerSubTankId = (int)$this->resolveArg('id');

        if ($sellerSubTankId <= 0) {
            throw new HttpBadRequestException($this->request, 'id không hợp lệ');
        }

        $orderCode = addslashes(trim((string)$this->resolveArg('code')));
        $order = $this->purchasingOrderRepository->findOrderOfCode($orderCode);
        if (empty($order)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy phiếu thu mua');
        }
        $purchaseOrderId = (int)$order->getId();

        $orderData = $order->jsonSerialize();
        if (($orderData['status'] ?? '') !== 'sent_to_seller') {
            throw new HttpBadRequestException($this->request, 'Chỉ xóa bình khi phiếu ở trạng thái sent_to_seller');
        }

        $this->assertSellerAccess($orderData, (string)$scope);

        $ok = $this->purchasingOrderRepository->deleteSellerSubTankByOrderId(
            $purchaseOrderId,
            $sellerSubTankId,
            (int)$this->auth_data['user_id']
        );

        if (!$ok) {
            throw new HttpBadRequestException($this->request, 'Không thể xóa bình bên bán');
        }

        $freshList = $this->purchasingOrderRepository->listSellerSubTanksByOrderId(
            $purchaseOrderId,
            [
                'page' => 1,
                'page_limit' => 100,
                'status' => 'all',
            ]
        );

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'purchase_order_id' => $purchaseOrderId,
            'data' => $freshList,
        ]);
    }

    /**
     * @param array<string,mixed> $orderData
     */
    private function assertSellerAccess(array $orderData, string $scope): void
    {
        if ($scope === 'all') {
            return;
        }

        $authUserId = (int)($this->auth_data['user_id'] ?? 0);
        $authCompanyId = (int)($this->auth_data['company_id'] ?? 0);

        $sellerSourceType = (string)($orderData['seller_source_type'] ?? '');
        $sellerUserId = (int)($orderData['seller_user_id'] ?? 0);
        $sellerCompanyId = (int)($orderData['seller_company_id'] ?? 0);

        if ($sellerSourceType === 'system_user' && $sellerUserId > 0 && $sellerUserId === $authUserId) {
            return;
        }

        if ($scope === 'own' && $sellerCompanyId > 0 && $sellerCompanyId === $authCompanyId) {
            return;
        }

        throw new HttpForbiddenException($this->request, 'Bạn không phải bên bán của phiếu này');
    }
}
