<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingOrder\Order;

use App\Application\Actions\PurchasingOrder\PurchasingOrderAction;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class UpdatePurchasingOrderAction extends PurchasingOrderAction
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
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $orderCode = addslashes(trim((string)$this->resolveArg('code')));
        $order = $this->purchasingOrderRepository->findOrderOfCodeWithPermission(
            $orderCode,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            (int)($this->auth_data['company_id'] ?? 0)
        );
        if (empty($order)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy phiếu thu mua');
        }

        if ($order->getStatus() !== 'draft') {
            throw new HttpBadRequestException($this->request, 'Chỉ được cập nhật phiếu ở trạng thái draft');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);
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
            'expected_delivery_at' => 'date',
            'currency' => 'string',
            'notes' => 'string',
        ]);

        $updateData = [];
        foreach (['expected_delivery_at', 'currency', 'notes'] as $field) {
            if (array_key_exists($field, $formData)) {
                $updateData[$field] = $clean[$field] ?? null;
            }
        }
        if (empty($updateData)) {
            throw new HttpBadRequestException($this->request, 'Không có thông tin cập nhật');
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');
        $updateData['updated_by'] = (int)$this->auth_data['user_id'];

        $updated = $this->purchasingOrderRepository->updateDraftOrderWithPermission(
            (int)$order->getId(),
            $updateData,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            (int)($this->auth_data['company_id'] ?? 0)
        );
        if (empty($updated)) {
            throw new HttpBadRequestException($this->request, 'Không thể cập nhật phiếu thu mua');
        }

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'purchase_order' => $updated->jsonSerialize(),
        ]);
    }
}