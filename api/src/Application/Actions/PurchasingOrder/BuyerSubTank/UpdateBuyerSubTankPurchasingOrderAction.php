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

class UpdateBuyerSubTankPurchasingOrderAction extends PurchasingOrderAction
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
            throw new HttpForbiddenException($this->request, 'Thiếu quyền cập nhật bình bên mua');
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
            throw new HttpBadRequestException($this->request, 'Chỉ cập nhật bình bên mua khi phiếu ở trạng thái seller_confirmed');
        }

        $this->assertBuyerAccess($orderData, (string)$scope);

        $formData = $this->getFormData();
        $validator = new Validator($this->request);
        $validator->validate('sub_tank_id', $formData['sub_tank_id'] ?? null, 'integer|min:1');
        $validator->validate('purchase_order_item_id', $formData['purchase_order_item_id'] ?? null, 'integer|min:1');
        $validator->validate('planned_receive_weight_kg', $formData['planned_receive_weight_kg'] ?? null, 'numeric|min:0');
        if (array_key_exists('actual_receive_weight_kg', $formData) || array_key_exists('received_at', $formData) || array_key_exists('status', $formData)) {
            throw new HttpBadRequestException($this->request, 'actual_receive_weight_kg, received_at và status được cập nhật bởi nghiệp vụ tiếp nhận');
        }
        $validator->validate('notes', $formData['notes'] ?? null, 'string|max:255');

        $mappings = null;
        if (array_key_exists('mappings', $formData)) {
            if (!is_array($formData['mappings'])) {
                throw new HttpBadRequestException($this->request, 'mappings phải là một mảng');
            }
            $mappings = [];
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

        $landMappings = null;
        if (array_key_exists('land_mappings', $formData)) {
            if (!is_array($formData['land_mappings'])) {
                throw new HttpBadRequestException($this->request, 'land_mappings phải là một mảng');
            }
            $landMappings = [];
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
        $usesLandMappings = in_array($sellerAccountType, ['farmer', 'vendor'], true);
        if ($usesLandMappings && $mappings !== null && !empty($mappings)) {
            throw new HttpBadRequestException($this->request, 'Phiếu Nông Hộ/Vendor không được mapping bình bên bán');
        }
        if (in_array($sellerAccountType, ['purchaser', 'trader', 'company'], true) && $landMappings !== null && !empty($landMappings)) {
            throw new HttpBadRequestException($this->request, 'Phiếu công ty/đơn vị thu mua không được mapping vườn Nông Hộ');
        }
        if (isset($formData['planned_receive_weight_kg'])) {
            $activeMappings = $usesLandMappings ? $landMappings : $mappings;
            if ($activeMappings === null && $sellerAccountType !== 'vendor') {
                throw new HttpBadRequestException($this->request, 'Khi đổi planned_receive_weight_kg phải gửi lại danh sách mapping tương ứng');
            }
            if ($activeMappings !== null && ($sellerAccountType !== 'vendor' || !empty($activeMappings))) {
                $weightField = $usesLandMappings
                    ? 'planned_receive_weight_kg'
                    : 'planned_transfer_weight_kg';
                if (abs((float)$formData['planned_receive_weight_kg'] - array_sum(array_column($activeMappings, $weightField))) > 0.001) {
                    throw new HttpBadRequestException($this->request, 'Tổng khối lượng mapping phải bằng planned_receive_weight_kg của bình bên mua');
                }
            }
        }
        if (isset($formData['purchase_order_item_id'])) {
            $purchaseOrderItemId = (int)$formData['purchase_order_item_id'];
            $orderItemIds = array_column(
                $this->purchasingOrderRepository->listOrderItemsByOrderId($purchaseOrderId),
                'purchase_order_item_id'
            );
            if (!in_array($purchaseOrderItemId, $orderItemIds, true)) {
                throw new HttpBadRequestException($this->request, 'purchase_order_item_id không thuộc phiếu thu mua');
            }
            if ($sellerAccountType === 'farmer' && $landMappings === null) {
                throw new HttpBadRequestException($this->request, 'Khi đổi purchase_order_item_id phải gửi lại land_mappings');
            }
        }

        $clean = $validator->sanitize($formData, [
            'sub_tank_id' => 'integer',
            'purchase_order_item_id' => 'integer',
            'planned_receive_weight_kg' => 'float',
            'notes' => 'string',
        ]);

        $updateData = [
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => (int)$this->auth_data['user_id'],
        ];

        if (isset($clean['sub_tank_id'])) {
            $subTank = $this->purchasingSubTankRepository->findPurchasingSubTankOfId((int)$clean['sub_tank_id']);
            if (empty($subTank)) {
                throw new HttpNotFoundException($this->request, 'Không tìm thấy bình con bên mua');
            }
            $subTankData = $subTank->jsonSerialize();
            $buyerCompanyId = (int)($orderData['buyer_company_id'] ?? $orderData['company_id'] ?? 0);
            if ($buyerCompanyId <= 0 || (int)($subTankData['company_id'] ?? 0) !== $buyerCompanyId) {
                throw new HttpForbiddenException($this->request, 'Bình con không thuộc công ty bên mua');
            }
            $updateData['sub_tank_id'] = (int)$clean['sub_tank_id'];
            $updateData['buyer_company_id'] = $buyerCompanyId;
            $updateData['assigned_by'] = (int)$this->auth_data['user_id'];
        }
        if ($mappings !== null) {
            $updateData['mappings'] = $mappings;
        }
        if ($landMappings !== null) {
            $updateData['land_mappings'] = $landMappings;
        }
        if (isset($clean['purchase_order_item_id'])) {
            $updateData['purchase_order_item_id'] = (int)$clean['purchase_order_item_id'];
        }
        if (isset($clean['planned_receive_weight_kg'])) {
            $updateData['planned_receive_weight_kg'] = (float)$clean['planned_receive_weight_kg'];
        }
        if (isset($clean['notes'])) {
            $updateData['notes'] = $clean['notes'];
        }

        if (count($updateData) === 2) {
            throw new HttpBadRequestException($this->request, 'Không có dữ liệu cập nhật');
        }

        $updated = $this->purchasingOrderRepository->updateBuyerSubTankByOrderId(
            $purchaseOrderId,
            $buyerSubTankId,
            $updateData
        );

        if (empty($updated)) {
            throw new HttpBadRequestException($this->request, 'Không thể cập nhật bình bên mua');
        }

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'buyer_sub_tank' => $updated,
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
