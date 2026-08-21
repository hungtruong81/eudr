<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingOrder\Order;

use App\Application\Actions\PurchasingOrder\PurchasingOrderAction;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

class CreatePurchasingOrderAction extends PurchasingOrderAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'purchasing_order', 'create');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('purchase_order_code', $formData['purchase_order_code'] ?? null, 'string|max:30');
        $validator->validate('seller_source_type', $formData['seller_source_type'] ?? null, 'required|in:system_user,vendor');
        $validator->validate('seller_user_id', $formData['seller_user_id'] ?? null, 'integer|min:1');
        $validator->validate('seller_vendor_id', $formData['seller_vendor_id'] ?? null, 'integer|min:1');
        $validator->validate('seller_company_id', $formData['seller_company_id'] ?? null, 'integer');
        $validator->validate('seller_name', $formData['seller_name'] ?? null, 'string|max:255');
        $validator->validate('seller_phone', $formData['seller_phone'] ?? null, 'string|max:20');
        $validator->validate('seller_address', $formData['seller_address'] ?? null, 'string|max:255');
        $validator->validate('seller_account_type', $formData['seller_account_type'] ?? null, 'in:farmer,purchaser,trader,company,vendor');
        $validator->validate('expected_delivery_at', $formData['expected_delivery_at'] ?? null, 'date');
        $validator->validate('currency', $formData['currency'] ?? null, 'string|max:10');
        $validator->validate('notes', $formData['notes'] ?? null, 'string|max:1000');

        if ($validator->hasErrors()) {
            $errors = [];
            foreach ((array)$validator->getErrors() as $fieldErrors) {
                $errors[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errors));
        }

        $clean = $validator->sanitize($formData, [
            'purchase_order_code' => 'string',
            'seller_source_type' => 'string',
            'seller_user_id' => 'integer',
            'seller_vendor_id' => 'integer',
            'seller_company_id' => 'integer',
            'seller_name' => 'string',
            'seller_phone' => 'string',
            'seller_address' => 'string',
            'seller_account_type' => 'string',
            'expected_delivery_at' => 'date',
            'currency' => 'string',
            'notes' => 'string',
        ]);

        $buyerUser = $this->userRepository->findUserOfId((int)$this->auth_data['user_id']);
        if (empty($buyerUser)) {
            throw new HttpBadRequestException($this->request, 'Không tìm thấy thông tin người mua');
        }

        $sellerSourceType = (string)($clean['seller_source_type'] ?? 'system_user');
        $sellerUserId = isset($clean['seller_user_id']) ? (int)$clean['seller_user_id'] : null;
        $sellerVendorId = isset($clean['seller_vendor_id']) ? (int)$clean['seller_vendor_id'] : null;
        $sellerName = (string)($clean['seller_name'] ?? '');
        $sellerPhone = isset($clean['seller_phone']) ? (string)$clean['seller_phone'] : null;
        $sellerAddress = isset($clean['seller_address']) ? (string)$clean['seller_address'] : null;

        if ($sellerSourceType === 'system_user') {
            if (empty($sellerUserId)) {
                throw new HttpBadRequestException($this->request, 'Thiếu seller_user_id cho nguồn system_user');
            }

            $sellerUser = $this->userRepository->findUserOfId($sellerUserId);
            if (empty($sellerUser)) {
                throw new HttpBadRequestException($this->request, 'Không tìm thấy người bán trong hệ thống');
            }

            $sellerUserData = $sellerUser->jsonSerialize();
            $sellerName = (string)($sellerUserData['full_name'] ?? '');
            $sellerPhone = $sellerUser->getPhone();
        } else {
            if (empty($sellerVendorId)) {
                throw new HttpBadRequestException($this->request, 'Thiếu seller_vendor_id cho nguồn vendor');
            }

            $vendor = $this->vendorRepository->findVendorOfIdWithPermission(
                $sellerVendorId,
                (int)$this->auth_data['user_id'],
                'own',
                (int)($this->auth_data['company_id'] ?? 0)
            );
            if (empty($vendor) || $vendor->getStatus() !== 'active') {
                throw new HttpBadRequestException($this->request, 'Vendor không tồn tại, không hoạt động hoặc không thuộc company hiện tại');
            }

            $sellerName = $vendor->getName();
            $sellerPhone = $vendor->getContactPhone();
            $sellerAddress = $vendor->getAddress();
        }

        $orderCode = trim((string)($clean['purchase_order_code'] ?? ''));
        if ($orderCode === '') {
            $orderCode = $this->purchasingOrderRepository->generateCode();
        } else {
            $exists = $this->purchasingOrderRepository->findOrderOfCode($orderCode);
            if (!empty($exists)) {
                $orderCode = $this->purchasingOrderRepository->generateCode();
            }
        }

        $buyerUserData = $buyerUser->jsonSerialize();

        $created = $this->purchasingOrderRepository->createOrder([
            'purchase_order_code' => $orderCode,
            'company_id' => (int)($this->auth_data['company_id'] ?? 0),
            'buyer_user_id' => (int)$this->auth_data['user_id'],
            'buyer_company_id' => (int)($this->auth_data['company_id'] ?? 0),
            'buyer_name' => (string)($buyerUserData['full_name'] ?? ''),
            'buyer_phone' => $buyerUser->getPhone(),
            'buyer_address' => null,
            'seller_source_type' => $sellerSourceType,
            'seller_user_id' => $sellerUserId,
            'seller_vendor_id' => $sellerVendorId,
            'seller_company_id' => (int)($clean['seller_company_id'] ?? 0),
            'seller_name' => $sellerName,
            'seller_phone' => $sellerPhone,
            'seller_address' => $sellerAddress,
            'seller_account_type' => $clean['seller_account_type'] ?? ($sellerSourceType === 'vendor' ? 'vendor' : 'farmer'),
            'purchase_date' => date('Y-m-d H:i:s'),
            'expected_delivery_at' => $clean['expected_delivery_at'] ?? null,
            'currency' => $clean['currency'] ?? 'VND',
            'total_quantity' => 0,
            'total_weight_kg' => 0,
            'total_estimated_amount' => 0,
            'status' => 'draft',
            'notes' => $clean['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => (int)$this->auth_data['user_id'],
        ]);

        if (empty($created)) {
            throw new HttpBadRequestException($this->request, 'Không thể tạo phiếu thu mua');
        }

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'purchase_order' => $created->jsonSerialize(),
        ]);
    }
}
