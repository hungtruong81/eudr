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

class CancelOrderAction extends OrderAction
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

        $current = $order->jsonSerialize();
        // $currentStatus = $current['status'] ?? 'draft';
        // if (!in_array($currentStatus, ['draft', 'approved'], true)) {
        //     throw new HttpBadRequestException($this->request, 'Đơn hàng không ở trạng thái hợp lệ để hủy');
        // }

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

        $data = [
            'status' => 'cancelled',
            'notes' => $clean['notes'] ?? ($current['notes'] ?? null),
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $this->auth_data['user_id'],
        ];

        $updated = $this->salesOrderRepository->updateOrderWithPermission(
            (int)$order->getId(),
            $data,
            $current['items'] ?? [],
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null,
            $this->auth_data['company_id'] ?? null
        );

        if (!$updated) {
            throw new HttpBadRequestException($this->request, 'Không thể hủy đơn hàng');
        }

        $res = ['result' => 'success', 'order' => $updated->jsonSerialize(), 'trace_id' => $trace_id];
        return $this->respondWithData($res);
    }
}