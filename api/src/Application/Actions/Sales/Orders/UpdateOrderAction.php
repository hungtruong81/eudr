<?php

declare(strict_types=1);

namespace App\Application\Actions\Sales\Orders;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class UpdateOrderAction extends OrderAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'sales_order', 'update');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $sale_order_code = addslashes(trim((string)$this->resolveArg('code')));
        $order = $this->salesOrderRepository->findOrderOfCodeWithPermission(
            $sale_order_code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null,
            $this->auth_data['company_id'] ?? null
        );

        if (!$order || !$order->getId()) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy đơn hàng');
        }

        if ($order->getStatus() === 'closed' || $order->getStatus() === 'cancelled') {
            throw new HttpBadRequestException($this->request, 'Không thể cập nhật đơn hàng đã đóng hoặc hủy');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('delivery_date', $formData['delivery_date'] ?? null, 'required|date');
        $validator->validate('delivery_address', $formData['delivery_address'] ?? null, 'string|max:255');
        $validator->validate('order_source_type', $formData['order_source_type'] ?? null, 'string|in:warehouse,transaction_ticket,product_lot');
        $validator->validate('notes', $formData['notes'] ?? null, 'string|max:500');
        $validator->validate('company_id', $formData['company_id'] ?? null, 'integer');
        $validator->validate('buyer_user_id', $formData['buyer_user_id'] ?? null, 'integer');
        $validator->validate('customer_code', $formData['customer_code'] ?? null, 'string|max:30');
        // $validator->validate('contract_id', $formData['contract_id'] ?? null, 'integer');
        // $validator->validate('quotation_id', $formData['quotation_id'] ?? null, 'integer');
        // $validator->validate('payment_terms', $formData['payment_terms'] ?? null, 'string|max:255');
        // $validator->validate('currency', $formData['currency'] ?? null, 'string|max:10');
        // $validator->validate('status', $formData['status'] ?? null, 'string|in:draft,pending,approved,allocated,shipping,closed,cancelled');

        if ($validator->hasErrors()) {
            $errors = [];
            foreach ((array)$validator->getErrors() as $fieldErrors) {
                $errors[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errors));
        }

        $clean = $validator->sanitize($formData, [
            'customer_code' => 'string',
            'delivery_date' => 'date',
            'delivery_address' => 'string',
            'order_source_type' => 'string',
            'notes' => 'string',
            'company_id' => 'integer',
            'buyer_user_id' => 'integer',
        ]);

        $customer_code = $clean['customer_code'] ?? '';
        $buyerUserId = (int)($clean['buyer_user_id'] ?? 0);
        $buyerCompanyId = 0;
        $customerId = 0;

        // Derive buyer_company_id from buyer_user_id
        if ($buyerUserId > 0) {
            $buyerUser = $this->userRepository->findUserOfId($buyerUserId);
            if (!$buyerUser) {
                throw new HttpBadRequestException($this->request, 'Người mua không tồn tại');
            }
            $buyerCompanyId = $buyerUser->getCompanyId();
        }

        if (!empty($customer_code)) {
            $view_customer_scope = Utils::resolveScope($permissions, 'sales_customer', 'view');
            $customer = $this->salesCustomerRepository->findCustomerOfCodeWithPermission(
                $customer_code,
                (int)$this->auth_data['user_id'],
                (string)$view_customer_scope
            );
            if (empty($customer)) {
                throw new HttpBadRequestException($this->request, 'Khách hàng không tồn tại');
            }
            $customerId = $customer->getId();
        }

        if ($customerId === 0 && $buyerCompanyId === 0) {
            throw new HttpBadRequestException($this->request, 'Phải chỉ định khách hàng (customer_code) hoặc người mua (buyer_user_id)');
        }

        $items = $formData['items'] ?? [];
        if (!is_array($items) || empty($items)) {
            throw new HttpBadRequestException($this->request, 'Danh sách dòng đơn hàng trống');
        }

        $orderSourceType = $clean['order_source_type'] ?? ($order->jsonSerialize()['order_source_type'] ?? 'warehouse');
        $isTransactionTicketOrder = ($orderSourceType === 'transaction_ticket');
        $isProductLotOrder = ($orderSourceType === 'product_lot');

        $preparedItems = [];
        $totalAmount = 0.0;
        foreach ($items as $item) {
            $qty = (float)($item['qty_ordered'] ?? 0);
            $price = (float)($item['price'] ?? 0);

            $sourceType = $isTransactionTicketOrder ? 'raw_material' : ($isProductLotOrder ? 'product_lot' : 'finished_product');
            $preparedItems[] = [
                'source_type' => (string)($item['source_type'] ?? $sourceType),
                'product_tank_id' => isset($item['product_tank_id']) ? (int)$item['product_tank_id'] : null,
                'product_type_id' => isset($item['product_type_id']) ? (int)$item['product_type_id'] : null,
                'product_lot_id' => isset($item['product_lot_id']) ? (int)$item['product_lot_id'] : null,
                'transaction_ticket_id' => isset($item['transaction_ticket_id']) ? (int)$item['transaction_ticket_id'] : null,
                'raw_material_tank_id' => isset($item['raw_material_tank_id']) ? (int)$item['raw_material_tank_id'] : null,
                'rubber_type' => isset($item['rubber_type']) ? (string)$item['rubber_type'] : null,
                'quality_grade' => isset($item['quality_grade']) ? (float)$item['quality_grade'] : null,
                'uom' => (string)($item['uom'] ?? ''),
                'qty_ordered' => $qty,
                'qty_allocated' => (float)($item['qty_allocated'] ?? 0),
                'qty_shipped' => (float)($item['qty_shipped'] ?? 0),
                'price' => $price,
                'discount_rate' => (float)($item['discount_rate'] ?? 0),
                'surcharge' => (float)($item['surcharge'] ?? 0),
                'currency' => (string)($item['currency'] ?? ($clean['currency'] ?? 'VND')),
                'notes' => $item['notes'] ?? null,
            ];
            $totalAmount += $qty * $price;
        }

        $data = [
            'customer_id' => $customerId,
            'buyer_company_id' => $buyerCompanyId,
            'buyer_user_id' => $buyerUserId,
            'contract_id' => $clean['contract_id'] ?? 0,
            'quotation_id' => $clean['quotation_id'] ?? 0,
            //'order_date' => $clean['order_date'],
            'order_source_type' => $orderSourceType,
            'delivery_date' => $clean['delivery_date'],
            'payment_terms' => $clean['payment_terms'] ?? null,
            'delivery_address' => $clean['delivery_address'] ?? null,
            'currency' => $clean['currency'] ?? 'VND',
            //'status' => $clean['status'] ?? 'draft',
            'total_amount' => $totalAmount,
            'notes' => $clean['notes'] ?? null,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $this->auth_data['user_id'],
        ];

        $updated = $this->salesOrderRepository->updateOrderWithPermission(
            (int)$order->getId(),
            $data,
            $preparedItems,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null,
            $this->auth_data['company_id'] ?? null
        );

        if (!$updated) {
            throw new HttpBadRequestException($this->request, 'Không thể cập nhật đơn hàng');
        }

        $res = ['result' => 'success', 'order' => $updated->jsonSerialize(), 'trace_id' => $trace_id];
        return $this->respondWithData($res);
    }
}
