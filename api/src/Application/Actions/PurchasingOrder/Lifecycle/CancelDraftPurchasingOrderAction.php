<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingOrder\Lifecycle;

use App\Application\Actions\PurchasingOrder\PurchasingOrderAction;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class CancelDraftPurchasingOrderAction extends PurchasingOrderAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        //$scope = Utils::resolveScope($permissions, 'purchasing_order', 'cancel');
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
            throw new HttpBadRequestException($this->request, 'Chỉ được hủy phiếu ở trạng thái draft');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);
        $validator->validate('cancel_reason', $formData['cancel_reason'] ?? null, 'required|string|max:255');
        $validator->validate('notes', $formData['notes'] ?? null, 'string|max:1000');

        if ($validator->hasErrors()) {
            $errors = [];
            foreach ((array)$validator->getErrors() as $fieldErrors) {
                $errors[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errors));
        }

        $clean = $validator->sanitize($formData, [
            'cancel_reason' => 'string',
            'notes' => 'string',
        ]);

        $cancelled = $this->purchasingOrderRepository->cancelDraftOrderWithPermission(
            (int)$order->getId(),
            [
                'status' => 'cancelled',
                'cancel_reason' => $clean['cancel_reason'],
                'cancelled_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => (int)$this->auth_data['user_id'],
                'notes' => $clean['notes'] ?? ($order->jsonSerialize()['notes'] ?? null),
            ],
            (int)$this->auth_data['user_id'],
            (string)$scope,
            (int)($this->auth_data['company_id'] ?? 0)
        );

        if (empty($cancelled)) {
            throw new HttpBadRequestException($this->request, 'Không thể hủy phiếu thu mua');
        }

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'purchase_order' => $cancelled->jsonSerialize(),
        ]);
    }
}
