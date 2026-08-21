<?php

declare(strict_types=1);

namespace App\Application\Actions\Sales\Orders;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

class CreateOrderAction extends OrderAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'sales_order', 'create');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('sale_order_code', $formData['sale_order_code'] ?? null, 'required|string|max:30');
        $validator->validate('delivery_date', $formData['delivery_date'] ?? null, 'required|date');
        $validator->validate('delivery_address', $formData['delivery_address'] ?? null, 'string|max:255');
        $validator->validate('order_source_type', $formData['order_source_type'] ?? null, 'string|in:warehouse,transaction_ticket,product_lot');
        $validator->validate('notes', $formData['notes'] ?? null, 'string|max:500');
        $validator->validate('company_id', $formData['company_id'] ?? null, 'integer');
        $validator->validate('buyer_user_id', $formData['buyer_user_id'] ?? null, 'integer');
        $validator->validate('customer_code', $formData['customer_code'] ?? null, 'string|max:30');

        if ($validator->hasErrors()) {
            $errors = [];
            foreach ((array)$validator->getErrors() as $fieldErrors) {
                $errors[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errors));
        }

        $clean = $validator->sanitize($formData, [
            'sale_order_code' => 'string',
            'customer_code' => 'string',
            'delivery_date' => 'date',
            'delivery_address' => 'string',
            'order_source_type' => 'string',
            'notes' => 'string',
            'company_id' => 'integer',
            'buyer_user_id' => 'integer',
        ]);

        $items = $formData['items'] ?? [];
        if (!is_array($items) || empty($items)) {
            throw new HttpBadRequestException($this->request, 'Danh sách dòng đơn hàng trống');
        }

        $orderSourceType = $clean['order_source_type'] ?? 'warehouse';
        $buyerUserId = (int)($clean['buyer_user_id'] ?? 0);
        $buyerCompanyId = 0;
        $customerCode = $clean['customer_code'] ?? '';
        $customerId = 0;
        $company_id = (int)($clean['company_id'] ?? ($this->auth_data['company_id'] ?? 0));

        // Derive buyer_company_id from buyer_user_id
        if ($buyerUserId > 0) {
            $buyerUser = $this->userRepository->findUserOfId($buyerUserId);
            if (!$buyerUser) {
                throw new HttpBadRequestException($this->request, 'Người mua không tồn tại');
            }
            $buyerCompanyId = $buyerUser->getCompanyId();

            // B2B: Trader-to-Trader — validate connection
            $connections = $this->connectionRepository->findConnectionBetweenUsers(
                (int)$this->auth_data['user_id'],
                $buyerUserId,
                'accepted'
            );
            if ($buyerCompanyId > 0 && $buyerCompanyId !== $company_id && empty($connections)) {
                throw new HttpBadRequestException($this->request, 'Bên bán và bên mua chưa có kết nối hợp lệ');
            }
            if (empty($customerCode)) {
                $customerId = 0;
            }
        }

        if (!empty($customerCode)) {
            $view_customer_scope = Utils::resolveScope($permissions, 'sales_customer', 'view');
            $customer = $this->salesCustomerRepository->findCustomerOfCodeWithPermission(
                $customerCode,
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

        // Check if order code already exists
        $sale_order_code = $clean['sale_order_code'];
        $order_exists = $this->salesOrderRepository->findOrderOfCode($sale_order_code);
        if ($order_exists) {
            $sale_order_code = $this->salesOrderRepository->generateCode();
        }

        $isProductLotOrder = ($orderSourceType === 'product_lot');
        $isTransactionTicketOrder = ($orderSourceType === 'transaction_ticket');

        $preparedItems = [];
        $totalAmount = 0.0;
        $productLotIds = [];

        foreach ($items as $item) {
            $qty = (float)($item['qty_ordered'] ?? 1);
            $price = (float)($item['price'] ?? 0);

            $sourceType = $isTransactionTicketOrder ? 'raw_material' : ($isProductLotOrder ? 'product_lot' : 'finished_product');

            $preparedItem = [
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

            // Validate product lot ownership
            if (!empty($preparedItem['product_lot_id'])) {
                $lot = $this->productLotRepository->findProductLotOfId((int)$preparedItem['product_lot_id']);
                if (!$lot) {
                    throw new HttpBadRequestException($this->request, 'Product lot không tồn tại: ' . $preparedItem['product_lot_id']);
                }
                if ($lot->getStatus() !== 'confirmed') {
                    throw new HttpBadRequestException($this->request, 'Product lot chưa xác nhận hoặc đã xuất: ' . $lot->getCode());
                }
                if ($lot->getOwnerCompanyId() > 0 && $lot->getOwnerCompanyId() !== $company_id) {
                    throw new HttpBadRequestException($this->request, 'Bạn không sở hữu product lot: ' . $lot->getCode());
                }
                $productLotIds[] = (int)$preparedItem['product_lot_id'];
            }

            $preparedItems[] = $preparedItem;
            $totalAmount += $qty * $price;
        }

        $data = [
            'sale_order_code' => $sale_order_code,
            'company_id' => $company_id,
            'customer_id' => $customerId,
            'buyer_company_id' => $buyerCompanyId,
            'buyer_user_id' => $buyerUserId,
            'contract_id' => $clean['contract_id'] ?? 0,
            'quotation_id' => $clean['quotation_id'] ?? 0,
            'order_date' => date('Y-m-d'),
            'order_source_type' => $orderSourceType,
            'payment_terms' => $clean['payment_terms'] ?? null,
            'delivery_date' => $clean['delivery_date'],
            'delivery_address' => $clean['delivery_address'] ?? null,
            'currency' => $clean['currency'] ?? 'VND',
            'status' => $clean['status'] ?? 'pending',
            'total_amount' => $totalAmount,
            'notes' => $clean['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $this->auth_data['user_id'],
        ];
        
        $order = $this->salesOrderRepository->createOrder($data, $preparedItems);
        if (!$order) {
            throw new HttpBadRequestException($this->request, 'Không thể tạo đơn hàng');
        }

        $orderPayload = $order->jsonSerialize();
        $orderItems = $orderPayload['items'] ?? [];
        if (!is_array($orderItems) || empty($orderItems)) {
            throw new HttpBadRequestException($this->request, 'Không thể tạo phiếu xuất tự động: đơn hàng không có dòng dữ liệu');
        }

        $issueItems = [];
        foreach ($orderItems as $orderItem) {
            $issueItems[] = [
                'sale_order_item_id' => (int)($orderItem['sale_order_item_id'] ?? 0),
                'source_type' => (string)($orderItem['source_type'] ?? 'finished_product'),
                'product_id' => (int)($orderItem['product_type_id'] ?? 0),
                'product_lot_id' => isset($orderItem['product_lot_id']) ? (int)$orderItem['product_lot_id'] : null,
                'uom' => (string)($orderItem['uom'] ?? ''),
                'qty_issued' => (float)($orderItem['qty_ordered'] ?? 0),
                'price' => isset($orderItem['price']) ? (float)$orderItem['price'] : null,
                'currency' => isset($orderItem['currency']) ? (string)$orderItem['currency'] : null,
                'notes' => $orderItem['notes'] ?? null,
                'allocations' => [],
            ];
        }

        $issueCode = $this->salesIssueRepository->generateCode();
        $issueData = [
            'issue_code' => $issueCode,
            'sale_order_id' => (int)($orderPayload['sale_order_id'] ?? 0),
            'company_id' => $company_id,
            'warehouse_id' => null,
            'issue_date' => date('Y-m-d H:i:s'),
            'status' => 'draft',
            'document_ref' => (string)($orderPayload['sale_order_code'] ?? ''),
            'shipper' => null,
            'vehicle_no' => null,
            'receiver' => null,
            'reason_code' => 'auto_from_order',
            'notes' => 'Tự động tạo từ đơn hàng ' . (string)($orderPayload['sale_order_code'] ?? ''),
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $this->auth_data['user_id'],
        ];

        $issue = $this->salesIssueRepository->createIssue($issueData, $issueItems);
        if (!$issue) {
            throw new HttpBadRequestException($this->request, 'Đơn hàng đã tạo nhưng không thể tạo phiếu xuất tự động');
        }

        $res = [
            'result' => 'success',
            'order' => $orderPayload,
            'issue' => $issue->jsonSerialize(),
            'trace_id' => $trace_id,
        ];
        return $this->respondWithData($res);
    }
}
