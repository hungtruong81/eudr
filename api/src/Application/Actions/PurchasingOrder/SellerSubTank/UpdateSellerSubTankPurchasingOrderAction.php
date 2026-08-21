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

class UpdateSellerSubTankPurchasingOrderAction extends PurchasingOrderAction
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
            throw new HttpForbiddenException($this->request, 'Thiếu quyền cập nhật bình bên bán');
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
            throw new HttpBadRequestException(
                $this->request,
                'Chỉ cập nhật bình khi phiếu ở trạng thái sent_to_seller'
            );
        }

        $this->assertSellerAccess($orderData, (string)$scope);

        $formData = $this->getFormData();
        $validator = new Validator($this->request);
        $validator->validate('sub_tank_id', $formData['sub_tank_id'] ?? null, 'integer|min:1');
        $validator->validate('purchase_order_item_id', $formData['purchase_order_item_id'] ?? null, 'integer|min:1');
        $validator->validate('filled_weight_kg', $formData['filled_weight_kg'] ?? null, 'numeric|min:0');
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

        $updateData = [
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => (int)$this->auth_data['user_id'],
        ];

        if (isset($clean['sub_tank_id'])) {
            $subTank = $this->purchasingSubTankRepository->findPurchasingSubTankOfId((int)$clean['sub_tank_id']);
            if (empty($subTank)) {
                throw new HttpNotFoundException($this->request, 'Không tìm thấy bình con bên bán');
            }
            $subTankData = $subTank->jsonSerialize();
            $sellerCompanyId = (int)($orderData['seller_company_id'] ?? 0);
            if ($sellerCompanyId <= 0 || (int)($subTankData['company_id'] ?? 0) !== $sellerCompanyId) {
                throw new HttpForbiddenException($this->request, 'Bình con không thuộc công ty bên bán');
            }
            $updateData['sub_tank_id'] = (int)$clean['sub_tank_id'];
            $updateData['seller_company_id'] = $sellerCompanyId;
            $updateData['declared_by'] = (int)$this->auth_data['user_id'];
        }
        if (isset($clean['purchase_order_item_id'])) {
            $updateData['purchase_order_item_id'] = (int)$clean['purchase_order_item_id'];
        }
        if (isset($clean['filled_weight_kg'])) {
            $updateData['filled_weight_kg'] = (float)$clean['filled_weight_kg'];
        }
        if (isset($clean['estimated_tsc_percent'])) {
            $updateData['estimated_tsc_percent'] = $clean['estimated_tsc_percent'];
        }
        if (isset($clean['estimated_drc_percent'])) {
            $updateData['estimated_drc_percent'] = $clean['estimated_drc_percent'];
        }
        if (isset($clean['sealed_at'])) {
            $updateData['sealed_at'] = $clean['sealed_at'];
            $updateData['sealed_by'] = (int)$this->auth_data['user_id'];
        }
        if (isset($clean['notes'])) {
            $updateData['notes'] = $clean['notes'];
        }

        if (count($updateData) === 2) {
            throw new HttpBadRequestException($this->request, 'Không có dữ liệu cập nhật');
        }

        $updated = $this->purchasingOrderRepository->updateSellerSubTankByOrderId(
            $purchaseOrderId,
            $sellerSubTankId,
            $updateData
        );

        if (empty($updated)) {
            throw new HttpBadRequestException($this->request, 'Không thể cập nhật bình bên bán');
        }

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'seller_sub_tank' => $updated,
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
