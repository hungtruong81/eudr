<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingOrder\BuyerSubTank;

use App\Application\Actions\PurchasingOrder\PurchasingOrderAction;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class CreateBuyerSubTankPurchasingOrderAction extends PurchasingOrderAction
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
            throw new HttpForbiddenException($this->request, 'Thiếu quyền gán bình bên mua');
        }

        $orderCode = addslashes(trim((string)$this->resolveArg('code')));
        $order = $this->purchasingOrderRepository->findOrderOfCode($orderCode);
        if (empty($order)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy phiếu thu mua');
        }
        $purchaseOrderId = (int)$order->getId();

        $orderData = $order->jsonSerialize();
        if (($orderData['status'] ?? '') !== 'seller_confirmed') {
            throw new HttpBadRequestException($this->request, 'Chỉ gán bình bên mua khi phiếu ở trạng thái seller_confirmed');
        }

        $this->assertBuyerAccess($orderData, (string)$scope);

        $formData = $this->getFormData();
        $validator = new Validator($this->request);
        $validator->validate('sub_tank_id', $formData['sub_tank_id'] ?? null, 'required|integer|min:1');
        $validator->validate('purchase_order_item_id', $formData['purchase_order_item_id'] ?? null, 'integer|min:1');
        $validator->validate('planned_receive_weight_kg', $formData['planned_receive_weight_kg'] ?? null, 'required|numeric|min:0');
        if (array_key_exists('actual_receive_weight_kg', $formData) || array_key_exists('received_at', $formData) || array_key_exists('status', $formData)) {
            throw new HttpBadRequestException($this->request, 'actual_receive_weight_kg, received_at và status được cập nhật bởi nghiệp vụ tiếp nhận');
        }
        $validator->validate('notes', $formData['notes'] ?? null, 'string|max:255');

        $mappings = [];
        if (isset($formData['mappings'])) {
            if (!is_array($formData['mappings'])) {
                throw new HttpBadRequestException($this->request, 'mappings phải là một mảng');
            }
            foreach ($formData['mappings'] as $mapping) {
                if (!is_array($mapping) || (int)($mapping['seller_sub_tank_id'] ?? 0) <= 0 || !is_numeric($mapping['planned_transfer_weight_kg'] ?? null) || (float)$mapping['planned_transfer_weight_kg'] < 0) {
                    throw new HttpBadRequestException($this->request, 'Mỗi mapping cần seller_sub_tank_id và planned_transfer_weight_kg hợp lệ');
                }
                if (array_key_exists('actual_transfer_weight_kg', $mapping) || array_key_exists('transferred_at', $mapping)) {
                    throw new HttpBadRequestException($this->request, 'actual_transfer_weight_kg và transferred_at được cập nhật bởi nghiệp vụ chuyển bình');
                }
                $mappings[] = [
                    'purchase_order_seller_sub_tank_id' => (int)$mapping['seller_sub_tank_id'],
                    'planned_transfer_weight_kg' => (float)$mapping['planned_transfer_weight_kg'],
                ];
            }
        }

        $landMappings = [];
        if (isset($formData['land_mappings'])) {
            if (!is_array($formData['land_mappings'])) {
                throw new HttpBadRequestException($this->request, 'land_mappings phải là một mảng');
            }
            foreach ($formData['land_mappings'] as $mapping) {
                if (!is_array($mapping) || (int)($mapping['purchase_order_land_id'] ?? 0) <= 0 || !is_numeric($mapping['planned_receive_weight_kg'] ?? null) || (float)$mapping['planned_receive_weight_kg'] <= 0) {
                    throw new HttpBadRequestException($this->request, 'Mỗi land_mapping cần purchase_order_land_id và planned_receive_weight_kg lớn hơn 0');
                }
                $landMappings[] = [
                    'purchase_order_land_id' => (int)$mapping['purchase_order_land_id'],
                    'planned_receive_weight_kg' => (float)$mapping['planned_receive_weight_kg'],
                    'actual_receive_weight_kg' => 0.0,
                    'received_at' => null,
                ];
            }
        }

        if ($validator->hasErrors()) {
            $errors = [];
            foreach ((array)$validator->getErrors() as $fieldErrors) {
                $errors[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errors));
        }

        $sellerAccountType = (string)($orderData['seller_account_type'] ?? '');
        if ($sellerAccountType === 'farmer' && (!empty($mappings) || empty($landMappings))) {
            throw new HttpBadRequestException($this->request, 'Phiếu Nông Hộ phải có land_mappings và không được có mappings bình bên bán');
        }
        if ($sellerAccountType === 'vendor' && !empty($mappings)) {
            throw new HttpBadRequestException($this->request, 'Phiếu Vendor không được có mappings bình bên bán');
        }
        if (in_array($sellerAccountType, ['purchaser', 'trader', 'company'], true) && (!empty($landMappings) || empty($mappings))) {
            throw new HttpBadRequestException($this->request, 'Phiếu công ty/đơn vị thu mua phải có mappings bình bên bán và không được có land_mappings');
        }
        if ($sellerAccountType !== 'vendor' || !empty($landMappings)) {
            $mappedWeight = $sellerAccountType === 'farmer' || $sellerAccountType === 'vendor'
                ? array_sum(array_column($landMappings, 'planned_receive_weight_kg'))
                : array_sum(array_column($mappings, 'planned_transfer_weight_kg'));
            if (abs((float)($formData['planned_receive_weight_kg'] ?? 0) - $mappedWeight) > 0.001) {
                throw new HttpBadRequestException($this->request, 'Tổng khối lượng mapping phải bằng planned_receive_weight_kg của bình bên mua');
            }
        }

        $purchaseOrderItemId = (int)($formData['purchase_order_item_id'] ?? 0);
        $orderItemIds = array_column(
            $this->purchasingOrderRepository->listOrderItemsByOrderId($purchaseOrderId),
            'purchase_order_item_id'
        );
        if ($purchaseOrderItemId > 0 && !in_array($purchaseOrderItemId, $orderItemIds, true)) {
            throw new HttpBadRequestException($this->request, 'purchase_order_item_id không thuộc phiếu thu mua');
        }
        if (($sellerAccountType === 'farmer' || !empty($landMappings)) && $purchaseOrderItemId <= 0) {
            throw new HttpBadRequestException($this->request, 'purchase_order_item_id là bắt buộc khi có mapping vườn');
        }

        $clean = $validator->sanitize($formData, [
            'sub_tank_id' => 'integer',
            'purchase_order_item_id' => 'integer',
            'planned_receive_weight_kg' => 'float',
            'notes' => 'string',
        ]);

        $subTank = $this->purchasingSubTankRepository->findPurchasingSubTankOfId((int)$clean['sub_tank_id']);
        if (empty($subTank)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy bình con bên mua');
        }
        $subTankData = $subTank->jsonSerialize();
        $buyerCompanyId = (int)($orderData['buyer_company_id'] ?? $orderData['company_id'] ?? 0);
        if ($buyerCompanyId <= 0 || (int)($subTankData['company_id'] ?? 0) !== $buyerCompanyId) {
            throw new HttpForbiddenException($this->request, 'Bình con không thuộc công ty bên mua');
        }

        $created = $this->purchasingOrderRepository->createBuyerSubTankByOrderId(
            $purchaseOrderId,
            [
                'purchase_order_id' => $purchaseOrderId,
                'sub_tank_id' => (int)$clean['sub_tank_id'],
                'buyer_company_id' => $buyerCompanyId,
                'assigned_by' => (int)$this->auth_data['user_id'],
                'mappings' => $mappings,
                'land_mappings' => $landMappings,
                'purchase_order_item_id' => $clean['purchase_order_item_id'] ?? null,
                'planned_receive_weight_kg' => (float)($clean['planned_receive_weight_kg'] ?? 0),
                'actual_receive_weight_kg' => 0.0,
                'received_at' => null,
                'status' => 'assigned',
                'notes' => $clean['notes'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => (int)$this->auth_data['user_id'],
            ]
        );

        if (empty($created)) {
            throw new HttpBadRequestException($this->request, 'Không thể thêm bình bên mua vào phiếu');
        }

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'buyer_sub_tank' => $created,
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
