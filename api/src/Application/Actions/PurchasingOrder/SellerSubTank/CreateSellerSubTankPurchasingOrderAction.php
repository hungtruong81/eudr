<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingOrder\SellerSubTank;

use App\Application\Actions\PurchasingOrder\PurchasingOrderAction;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class CreateSellerSubTankPurchasingOrderAction extends PurchasingOrderAction
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
            throw new HttpForbiddenException($this->request, 'Thiếu quyền khai báo bình bên bán');
        }

        $orderCode = addslashes(trim((string)$this->resolveArg('code')));
        $order = $this->purchasingOrderRepository->findOrderOfCode($orderCode);
        if (empty($order)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy phiếu thu mua');
        }
        $purchaseOrderId = (int)$order->getId();

        $orderData = $order->jsonSerialize();
        if (($orderData['status'] ?? '') !== 'sent_to_seller') {
            throw new HttpBadRequestException(
                $this->request,
                'Chỉ khai báo bình khi phiếu ở trạng thái sent_to_seller'
            );
        }

        $this->assertSellerAccess($orderData, (string)$scope);

        if (!in_array((string)($orderData['seller_account_type'] ?? ''), ['purchaser', 'trader', 'company'], true)) {
            throw new HttpBadRequestException($this->request, 'Chỉ bên bán là đơn vị Thu Mua/Công ty mới khai báo bình bên bán');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);
        $validator->validate('sub_tank_id', $formData['sub_tank_id'] ?? null, 'required|integer|min:1');
        $validator->validate('purchase_order_item_id', $formData['purchase_order_item_id'] ?? null, 'integer|min:1');
        $validator->validate('filled_weight_kg', $formData['filled_weight_kg'] ?? null, 'required|numeric|min:0');
        $validator->validate('estimated_tsc_percent', $formData['estimated_tsc_percent'] ?? null, 'numeric|min:0');
        $validator->validate('estimated_drc_percent', $formData['estimated_drc_percent'] ?? null, 'numeric|min:0');
        $validator->validate('sealed_at', $formData['sealed_at'] ?? null, 'date');
        $validator->validate('notes', $formData['notes'] ?? null, 'string|max:255');

        if ($validator->hasErrors()) {
            $errors = [];
            foreach ((array)$validator->getErrors() as $fieldErrors) {
                $errors[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errors));
        }

        $clean = $validator->sanitize($formData, [
            'sub_tank_id' => 'integer',
            'purchase_order_item_id' => 'integer',
            'filled_weight_kg' => 'float',
            'estimated_tsc_percent' => 'float',
            'estimated_drc_percent' => 'float',
            'sealed_at' => 'date',
            'notes' => 'string',
        ]);

        $subTank = $this->purchasingSubTankRepository->findPurchasingSubTankOfId((int)$clean['sub_tank_id']);
        if (empty($subTank)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy bình con bên bán');
        }
        $subTankData = $subTank->jsonSerialize();
        $sellerCompanyId = (int)($orderData['seller_company_id'] ?? 0);
        if ($sellerCompanyId <= 0 || (int)($subTankData['company_id'] ?? 0) !== $sellerCompanyId) {
            throw new HttpForbiddenException($this->request, 'Bình con không thuộc công ty bên bán');
        }

        $created = $this->purchasingOrderRepository->createSellerSubTankByOrderId(
            $purchaseOrderId,
            [
                'purchase_order_id' => $purchaseOrderId,
                'sub_tank_id' => (int)$clean['sub_tank_id'],
                'seller_company_id' => $sellerCompanyId,
                'declared_by' => (int)$this->auth_data['user_id'],
                'purchase_order_item_id' => $clean['purchase_order_item_id'] ?? null,
                'filled_weight_kg' => (float)($clean['filled_weight_kg'] ?? 0),
                'estimated_tsc_percent' => $clean['estimated_tsc_percent'] ?? null,
                'estimated_drc_percent' => $clean['estimated_drc_percent'] ?? null,
                'sealed_at' => $clean['sealed_at'] ?? null,
                'sealed_by' => (int)$this->auth_data['user_id'],
                'status' => 'declared',
                'notes' => $clean['notes'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => (int)$this->auth_data['user_id'],
            ]
        );

        if (empty($created)) {
            throw new HttpBadRequestException($this->request, 'Không thể thêm bình bên bán vào phiếu');
        }

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'seller_sub_tank' => $created,
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
