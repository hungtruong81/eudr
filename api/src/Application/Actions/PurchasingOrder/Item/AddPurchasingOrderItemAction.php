<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingOrder\Item;

use App\Application\Actions\PurchasingOrder\PurchasingOrderAction;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class AddPurchasingOrderItemAction extends \App\Application\Actions\PurchasingOrder\PurchasingOrderAction
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
            throw new HttpBadRequestException($this->request, 'Chỉ được thêm dòng hàng khi phiếu ở trạng thái draft');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('rubber_type', $formData['rubber_type'] ?? null, 'required|in:latex,cup_lump,scrap_rubber,mixed,other');
        $validator->validate('quality_basis', $formData['quality_basis'] ?? null, 'required|in:kg,tsc,drc,fixed');
        $validator->validate('quality_value', $formData['quality_value'] ?? null, 'numeric|min:0');
        $validator->validate('quantity', $formData['quantity'] ?? null, 'required|numeric|min:0');
        $validator->validate('weight_kg', $formData['weight_kg'] ?? null, 'required|numeric|min:0');
        $validator->validate('unit_price', $formData['unit_price'] ?? null, 'required|numeric|min:0');
        $validator->validate('line_amount', $formData['line_amount'] ?? null, 'numeric|min:0');
        $validator->validate('notes', $formData['notes'] ?? null, 'string|max:255');

        if ($validator->hasErrors()) {
            $errors = [];
            foreach ((array)$validator->getErrors() as $fieldErrors) {
                $errors[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errors));
        }

        $clean = $validator->sanitize($formData, [
            'rubber_type' => 'string',
            'quality_basis' => 'string',
            'quality_value' => 'float',
            'quantity' => 'float',
            'weight_kg' => 'float',
            'unit_price' => 'float',
            'line_amount' => 'float',
            'notes' => 'string',
        ]);

        $quantity = (float)($clean['quantity'] ?? 0);
        $unitPrice = (float)($clean['unit_price'] ?? 0);
        $lineAmount = isset($clean['line_amount']) ? (float)$clean['line_amount'] : ($quantity * $unitPrice);

        $item = $this->purchasingOrderRepository->addOrderItemWithPermission(
            (int)$order->getId(),
            [
                'purchase_order_id' => (int)$order->getId(),
                'rubber_type' => $clean['rubber_type'],
                'quality_basis' => $clean['quality_basis'],
                'quality_value' => $clean['quality_value'] ?? null,
                'quantity' => $quantity,
                'weight_kg' => (float)($clean['weight_kg'] ?? 0),
                'unit_price' => $unitPrice,
                'line_amount' => $lineAmount,
                'notes' => $clean['notes'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => (int)$this->auth_data['user_id'],
            ],
            (int)$this->auth_data['user_id'],
            (string)$scope,
            (int)($this->auth_data['company_id'] ?? 0)
        );

        if (empty($item)) {
            throw new HttpBadRequestException($this->request, 'Không thể thêm dòng hàng hóa');
        }

        $freshOrder = $this->purchasingOrderRepository->findOrderOfCodeWithPermission(
            $orderCode,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            (int)($this->auth_data['company_id'] ?? 0)
        );

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'item' => $item,
            'purchase_order' => $freshOrder ? $freshOrder->jsonSerialize() : null,
        ]);
    }
}
