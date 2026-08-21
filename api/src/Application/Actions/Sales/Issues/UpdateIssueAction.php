<?php

declare(strict_types=1);

namespace App\Application\Actions\Sales\Issues;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class UpdateIssueAction extends IssueAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'sales_issue', 'update');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $issue_code = addslashes(trim((string)$this->resolveArg('code')));
        $issue = $this->salesIssueRepository->findIssueOfCodeWithPermission(
            $issue_code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null,
            $this->auth_data['company_id'] ?? null
        );

        if (!$issue || !$issue->getId()) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy phiếu xuất kho');
        }

        $current = $issue->jsonSerialize();
        if (($current['status'] ?? 'draft') !== 'draft') {
            throw new HttpBadRequestException($this->request, 'Chỉ cập nhật được phiếu ở trạng thái draft');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('sale_order_id', $formData['sale_order_id'] ?? null, 'required|integer|min:1');
        $validator->validate('warehouse_id', $formData['warehouse_id'] ?? null, 'integer');
        $validator->validate('issue_date', $formData['issue_date'] ?? null, 'date');
        $validator->validate('document_ref', $formData['document_ref'] ?? null, 'string|max:100');
        $validator->validate('shipper', $formData['shipper'] ?? null, 'string|max:150');
        $validator->validate('vehicle_no', $formData['vehicle_no'] ?? null, 'string|max:50');
        $validator->validate('receiver', $formData['receiver'] ?? null, 'string|max:150');
        $validator->validate('reason_code', $formData['reason_code'] ?? null, 'string|max:50');
        $validator->validate('notes', $formData['notes'] ?? null, 'string|max:500');

        if ($validator->hasErrors()) {
            $errors = [];
            foreach ((array)$validator->getErrors() as $fieldErrors) {
                $errors[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errors));
        }

        $clean = $validator->sanitize($formData, [
            'sale_order_id' => 'integer',
            'warehouse_id' => 'integer',
            'issue_date' => 'date',
            'document_ref' => 'string',
            'shipper' => 'string',
            'vehicle_no' => 'string',
            'receiver' => 'string',
            'reason_code' => 'string',
            'notes' => 'string',
        ]);

        $items = $formData['items'] ?? [];
        if (!is_array($items) || empty($items)) {
            throw new HttpBadRequestException($this->request, 'Danh sách dòng xuất kho trống');
        }

        $preparedItems = [];
        foreach ($items as $item) {
            $saleOrderItemId = (int)($item['sale_order_item_id'] ?? 0);
            $productId = (int)($item['product_id'] ?? 0);
            $uom = (string)($item['uom'] ?? '');
            $qty = (float)($item['qty_issued'] ?? 0);
            $sourceType = (string)($item['source_type'] ?? 'finished_product');
            $productLotId = isset($item['product_lot_id']) ? (int)$item['product_lot_id'] : null;

            if ($saleOrderItemId <= 0 || $qty <= 0 || $uom === '') {
                throw new HttpBadRequestException($this->request, 'Dòng xuất kho không hợp lệ (sale_order_item_id, qty_issued, uom)');
            }

            // For product_lot source type, product_lot_id is required; for others, product_id is required
            if ($sourceType === 'product_lot') {
                if (empty($productLotId) || $productLotId <= 0) {
                    throw new HttpBadRequestException($this->request, 'Dòng xuất kho product_lot thiếu product_lot_id');
                }
            } else {
                if ($productId <= 0) {
                    throw new HttpBadRequestException($this->request, 'Dòng xuất kho không hợp lệ (product_id)');
                }
            }

            $allocations = $item['allocations'] ?? [];
            $allocTotal = 0.0;
            if (!empty($allocations)) {
                if (!is_array($allocations)) {
                    throw new HttpBadRequestException($this->request, 'Dữ liệu allocations không hợp lệ');
                }
                foreach ($allocations as $allocation) {
                    $allocQty = (float)($allocation['qty_issued'] ?? 0);
                    if ($allocQty <= 0) {
                        throw new HttpBadRequestException($this->request, 'Số lượng allocation phải lớn hơn 0');
                    }
                    $allocTotal += $allocQty;
                }
                if (abs($allocTotal - $qty) > 0.0001) {
                    throw new HttpBadRequestException($this->request, 'Tổng qty_issued của allocations phải bằng qty_issued của dòng');
                }
            }

            $preparedItems[] = [
                'sale_order_item_id' => $saleOrderItemId,
                'source_type' => $sourceType,
                'product_id' => $productId,
                'product_lot_id' => $productLotId,
                'uom' => $uom,
                'qty_issued' => $qty,
                'price' => isset($item['price']) ? (float)$item['price'] : null,
                'currency' => isset($item['currency']) ? (string)$item['currency'] : null,
                'notes' => $item['notes'] ?? null,
                'allocations' => $this->prepareAllocations($allocations),
            ];
        }

        $data = [
            'sale_order_id' => $clean['sale_order_id'],
            'company_id' => $this->auth_data['company_id'] ?? null,
            'warehouse_id' => $clean['warehouse_id'] ?? null,
            'issue_date' => $clean['issue_date'] ?? ($current['issue_date'] ?? date('Y-m-d H:i:s')),
            'document_ref' => $clean['document_ref'] ?? null,
            'shipper' => $clean['shipper'] ?? null,
            'vehicle_no' => $clean['vehicle_no'] ?? null,
            'receiver' => $clean['receiver'] ?? null,
            'reason_code' => $clean['reason_code'] ?? null,
            'notes' => $clean['notes'] ?? null,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $this->auth_data['user_id'],
        ];

        $updated = $this->salesIssueRepository->updateIssueWithPermission(
            (int)$issue->getId(),
            $data,
            $preparedItems,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null,
            $this->auth_data['company_id'] ?? null
        );

        if (!$updated) {
            throw new HttpBadRequestException($this->request, 'Không thể cập nhật phiếu xuất kho');
        }

        $res = ['result' => 'success', 'issue' => $updated->jsonSerialize(), 'trace_id' => $trace_id];
        return $this->respondWithData($res);
    }

    private function prepareAllocations(array $allocations): array
    {
        $rows = [];
        foreach ($allocations as $allocation) {
            $rows[] = [
                'product_tank_id' => isset($allocation['product_tank_id']) ? (int)$allocation['product_tank_id'] : null,
                'raw_material_tank_id' => isset($allocation['raw_material_tank_id']) ? (int)$allocation['raw_material_tank_id'] : null,
                'transaction_ticket_id' => isset($allocation['transaction_ticket_id']) ? (int)$allocation['transaction_ticket_id'] : null,
                'lot_id' => isset($allocation['lot_id']) ? (int)$allocation['lot_id'] : null,
                'qty_issued' => (float)($allocation['qty_issued'] ?? 0),
                'weight_issued' => isset($allocation['weight_issued']) ? (float)$allocation['weight_issued'] : null,
                'notes' => $allocation['notes'] ?? null,
            ];
        }
        return $rows;
    }
}
