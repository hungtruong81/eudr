<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingOrder\Lifecycle;

use App\Application\Actions\PurchasingOrder\PurchasingOrderAction;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class SellerConfirmPurchasingOrderAction extends PurchasingOrderAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        //$scope = Utils::resolveScope($permissions, 'purchasing_order', 'seller_confirm');
        $scope = Utils::resolveScope($permissions, 'purchasing_order', 'update');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền xác nhận phiếu bên bán');
        }

        $orderCode = addslashes(trim((string)$this->resolveArg('code')));
        $order = $this->purchasingOrderRepository->findOrderOfCode($orderCode);
        if (empty($order)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy phiếu thu mua');
        }
        $purchaseOrderId = (int)$order->getId();

        $orderData = $order->jsonSerialize();
        if (($orderData['seller_source_type'] ?? '') === 'vendor') {
            throw new HttpBadRequestException($this->request, 'Phiếu Vendor không yêu cầu bên bán xác nhận');
        }

        if (($orderData['status'] ?? '') !== 'sent_to_seller') {
            throw new HttpBadRequestException($this->request, 'Chỉ xác nhận khi phiếu ở trạng thái sent_to_seller');
        }

        $this->assertSellerAccess($orderData, (string)$scope);

        $formData = $this->getFormData();
        $validator = new Validator($this->request);
        $validator->validate('notes', $formData['notes'] ?? null, 'string|max:255');

        if ($validator->hasErrors()) {
            $errors = [];
            foreach ((array)$validator->getErrors() as $fieldErrors) {
                $errors[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errors));
        }

        $clean = $validator->sanitize($formData, [
            'notes' => 'string',
        ]);

        $confirmed = $this->purchasingOrderRepository->confirmSellerById(
            $purchaseOrderId,
            (int)$this->auth_data['user_id'],
            $clean['notes'] ?? null
        );

        if (empty($confirmed)) {
            throw new HttpBadRequestException($this->request, 'Không thể xác nhận phiếu từ phía bên bán');
        }

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'purchase_order' => $confirmed->jsonSerialize(),
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
