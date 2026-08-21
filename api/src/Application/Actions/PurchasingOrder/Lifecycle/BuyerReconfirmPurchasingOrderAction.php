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

class BuyerReconfirmPurchasingOrderAction extends PurchasingOrderAction
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
            throw new HttpForbiddenException($this->request, 'Thiếu quyền xác nhận lại phiếu bên mua');
        }

        $orderCode = addslashes(trim((string)$this->resolveArg('code')));
        $order = $this->purchasingOrderRepository->findOrderOfCode($orderCode);
        if (empty($order)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy phiếu thu mua');
        }
        $purchaseOrderId = (int)$order->getId();

        $orderData = $order->jsonSerialize();
        if (($orderData['status'] ?? '') !== 'seller_confirmed') {
            throw new HttpBadRequestException($this->request, 'Chỉ xác nhận lại khi phiếu ở trạng thái seller_confirmed');
        }

        $this->assertBuyerAccess($orderData, (string)$scope);

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

        $reconfirmed = $this->purchasingOrderRepository->reconfirmBuyerById(
            $purchaseOrderId,
            (int)$this->auth_data['user_id'],
            $clean['notes'] ?? null
        );

        if (empty($reconfirmed)) {
            throw new HttpBadRequestException($this->request, 'Không thể xác nhận lại phiếu từ phía bên mua');
        }

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'purchase_order' => $reconfirmed->jsonSerialize(),
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

        $buyerUserId = (int)($orderData['buyer_user_id'] ?? 0);
        $buyerCompanyId = (int)($orderData['buyer_company_id'] ?? 0);
        $orderCompanyId = (int)($orderData['company_id'] ?? 0);

        if ($buyerUserId > 0 && $buyerUserId === $authUserId) {
            return;
        }

        if ($scope === 'own' && ($buyerCompanyId > 0 && $buyerCompanyId === $authCompanyId || $orderCompanyId > 0 && $orderCompanyId === $authCompanyId)) {
            return;
        }

        throw new HttpForbiddenException($this->request, 'Bạn không phải bên mua của phiếu này');
    }
}
