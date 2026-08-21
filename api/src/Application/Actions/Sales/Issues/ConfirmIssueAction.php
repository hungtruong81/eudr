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

class ConfirmIssueAction extends IssueAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'sales_issue', 'issue');
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

        $issueData = $issue->jsonSerialize();
        if (($issueData['status'] ?? 'draft') !== 'draft') {
            throw new HttpBadRequestException($this->request, 'Chỉ duyệt phiếu ở trạng thái draft');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);
        $validator->validate('notes', $formData['notes'] ?? null, 'string|max:500');

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

        $order = $this->salesOrderRepository->findOrderOfIdWithPermission(
            (int)$issueData['sale_order_id'],
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null,
            $this->auth_data['company_id'] ?? null
        );
        if (!$order || !$order->getId()) {
            throw new HttpBadRequestException($this->request, 'Không tìm thấy đơn hàng liên quan');
        }
        $orderMap = [];
        foreach (($order->jsonSerialize()['items'] ?? []) as $orderItem) {
            $orderMap[(int)$orderItem['sale_order_item_id']] = (float)($orderItem['qty_ordered'] ?? 0);
        }

        $issueItems = $issueData['items'] ?? [];
        $saleOrderItemIds = array_values(array_unique(array_map(static fn($row) => (int)($row['sale_order_item_id'] ?? 0), $issueItems)));
        $issuedTotals = $this->salesIssueRepository->getIssuedTotalsForOrderItems($saleOrderItemIds, (int)$issue->getId());

        foreach ($issueItems as $item) {
            $itemId = (int)($item['sale_order_item_id'] ?? 0);
            $qtyIssued = (float)($item['qty_issued'] ?? 0);
            if (empty($orderMap[$itemId])) {
                throw new HttpBadRequestException($this->request, 'Dòng xuất kho không khớp đơn hàng');
            }
            $alreadyIssued = $issuedTotals[$itemId] ?? 0.0;
            $allowed = $orderMap[$itemId] - $alreadyIssued;
            if ($qtyIssued - $allowed > 0.0001) {
                throw new HttpBadRequestException($this->request, 'Số lượng xuất vượt quá số lượng đặt còn lại');
            }

            $allocations = $item['allocations'] ?? [];
            if (!empty($allocations)) {
                $allocTotal = 0.0;
                foreach ($allocations as $allocation) {
                    $allocTotal += (float)($allocation['qty_issued'] ?? 0);
                }
                if (abs($allocTotal - $qtyIssued) > 0.0001) {
                    throw new HttpBadRequestException($this->request, 'Tổng qty_issued của allocations phải bằng qty_issued của dòng');
                }
            }
        }

        $data = [
            'notes' => $clean['notes'] ?? ($issueData['notes'] ?? null),
            'updated_by' => $this->auth_data['user_id'],
        ];

        $updated = $this->salesIssueRepository->confirmIssueWithPermission(
            (int)$issue->getId(),
            $data,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null,
            $this->auth_data['company_id'] ?? null
        );

        if (!$updated) {
            throw new HttpBadRequestException($this->request, 'Không thể duyệt phiếu xuất kho');
        }

        $res = ['result' => 'success', 'issue' => $updated->jsonSerialize(), 'trace_id' => $trace_id];
        return $this->respondWithData($res);
    }
}
