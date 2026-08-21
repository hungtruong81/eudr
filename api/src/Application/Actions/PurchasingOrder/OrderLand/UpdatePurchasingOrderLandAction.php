<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingOrder\OrderLand;

use App\Application\Actions\PurchasingOrder\PurchasingOrderAction;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class UpdatePurchasingOrderLandAction extends PurchasingOrderAction
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

        if ($this->purchasingOrderRepository->findOrderLandById(
            $purchaseOrderId,
            $purchaseOrderLandId
        ) === null) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy vườn nguồn trên phiếu');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('purchase_order_item_id', $formData['purchase_order_item_id'] ?? null, 'required|integer|min:1');
        $validator->validate('plot_id', $formData['plot_id'] ?? null, 'required|integer|min:1');
        $validator->validate('harvest_weight_kg', $formData['harvest_weight_kg'] ?? null, 'required|numeric|min:0');
        $validator->validate('purchased_weight_kg', $formData['purchased_weight_kg'] ?? null, 'required|numeric|min:0');
        $validator->validate('notes', $formData['notes'] ?? null, 'string|max:255');

        if ($validator->hasErrors()) {
            $errors = [];
            foreach ((array)$validator->getErrors() as $fieldErrors) {
                $errors[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errors));
        }

        $clean = $validator->sanitize($formData, [
            'purchase_order_item_id' => 'integer',
            'plot_id' => 'integer',
            'harvest_weight_kg' => 'float',
            'purchased_weight_kg' => 'float',
            'notes' => 'string',
        ]);

        if (!$this->purchasingOrderRepository->orderItemBelongsToOrder(
            $purchaseOrderId,
            (int)$clean['purchase_order_item_id']
        )) {
            throw new HttpBadRequestException($this->request, 'Dòng hàng không thuộc phiếu thu mua');
        }

        $land = $this->landRepository->findLandOfId((int)$clean['plot_id']);
        if (empty($land)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy vườn');
        }

        $landData = $land->jsonSerialize();
        $farmerUserId = null;
        $vendorId = null;

        if ($sellerSourceType === 'system_user') {
            $farmerUserId = (int)($orderData['seller_user_id'] ?? 0);
            if ($farmerUserId <= 0 || (int)($landData['farmer_user_id'] ?? 0) !== $farmerUserId) {
                throw new HttpBadRequestException($this->request, 'Vườn không thuộc nông hộ bán trên phiếu');
            }
        } elseif ($sellerSourceType === 'vendor') {
            $vendorId = (int)($orderData['seller_vendor_id'] ?? 0);
            if ($vendorId <= 0 || !$this->vendorLandRepository->activeRelationExists(
                $vendorId,
                (int)$clean['plot_id']
            )) {
                throw new HttpBadRequestException(
                    $this->request,
                    'Vườn chưa được vendor khai báo hoặc quan hệ không còn hoạt động'
                );
            }
        } else {
            throw new HttpBadRequestException($this->request, 'Nguồn bên bán không hợp lệ');
        }

        $record = $this->purchasingOrderRepository->updateOrderLandByOrderId(
            $purchaseOrderId,
            $purchaseOrderLandId,
            [
                'purchase_order_item_id' => (int)$clean['purchase_order_item_id'],
                'plot_id' => (int)$clean['plot_id'],
                'seller_source_type' => $sellerSourceType,
                'farmer_user_id' => $farmerUserId,
                'vendor_id' => $vendorId,
                'land_code' => $landData['plot_code'] ?? null,
                'land_name' => $landData['plot_name'] ?? null,
                'farmer_name' => $landData['farmer_name'] ?? null,
                'land_area' => isset($landData['land_area']) ? (float)$landData['land_area'] : null,
                'harvest_weight_kg' => (float)$clean['harvest_weight_kg'],
                'purchased_weight_kg' => (float)$clean['purchased_weight_kg'],
                'notes' => $clean['notes'] ?? null,
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => (int)$this->auth_data['user_id'],
            ]
        );

        if ($record === null) {
            throw new HttpBadRequestException($this->request, 'Không thể cập nhật vườn nguồn');
        }

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'land' => $record,
        ]);
    }
}
