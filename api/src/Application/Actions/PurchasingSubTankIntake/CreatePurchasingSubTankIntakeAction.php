<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingSubTankIntake;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;
use RuntimeException;

final class CreatePurchasingSubTankIntakeAction extends PurchasingSubTankIntakeAction
{
    protected function action(): Response
    {
        $userId = (int)($this->auth_data['user_id'] ?? 0);
        if (!$userId) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }
        $permissions = $this->userRepository->getUserPermissions($userId);
        $scope = Utils::resolveScope($permissions, 'purchasing_sub_tank', 'create');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền ghi nhận tiếp nhận');
        }

        $tank = $this->subTankRepository->findPurchasingSubTankOfCodeWithPermission(
            trim((string)$this->resolveArg('tankCode')),
            $userId,
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );
        if (!$tank) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy bình con');
        }

        $input = $this->getFormData();

        $input['rubber_type'] = $input['rubber_type'] ?? 'latex';
        $input['seller_source_type'] = $input['seller_source_type'] ?? 'system_user';
        $input['received_at'] = $input['received_at'] ?? date('Y-m-d H:i:s');

        $validator = new Validator($this->request);
        $validator->validate('received_weight_kg', $input['received_weight_kg'] ?? null, 'required|numeric|min:0.01');
        $validator->validate('rubber_type', $input['rubber_type'], 'required|in:latex,cup_lump,scrap_rubber,mixed');
        $validator->validate('seller_source_type', $input['seller_source_type'], 'required|in:system_user,vendor');
        $validator->validate('received_at', $input['received_at'], 'required|date');
        $validator->validate('harvested_at', $input['harvested_at'] ?? null, 'date');

        foreach (['purchase_order_id', 'vendor_id', 'farmer_user_id', 'transaction_ticket_id'] as $field) {
            $validator->validate($field, $input[$field] ?? null, 'integer|min:1');
        }
        foreach (['ph_value', 'nh3_percent', 'impurity_percent', 'tsc_percent', 'temperature_c'] as $field) {
            $validator->validate($field, $input[$field] ?? null, 'numeric|min:0');
        }
        if (isset($input['ph_value']) && (float)$input['ph_value'] > 14) {
            throw new HttpBadRequestException($this->request, 'ph_value must be between 0 and 14');
        }
        foreach (['nh3_percent', 'impurity_percent', 'tsc_percent'] as $field) {
            if (isset($input[$field]) && (float)$input[$field] > 100) {
                throw new HttpBadRequestException($this->request, $field . ' must be between 0 and 100');
            }
        }
        if ($input['seller_source_type'] === 'vendor' && empty($input['vendor_id'])) {
            throw new HttpBadRequestException($this->request, 'vendor_id is required for vendor source');
        }

        $sellerAccountType = null;
        if (!empty($input['purchase_order_id'])) {
            $order = $this->purchasingOrderRepository->findOrderOfId((int)$input['purchase_order_id']);
            if ($order === null) {
                throw new HttpNotFoundException($this->request, 'Không tìm thấy phiếu thu mua');
            }
            $sellerAccountType = (string)($order->jsonSerialize()['seller_account_type'] ?? '');
        }
        $isFarmerSource = $input['seller_source_type'] === 'system_user'
            && ($sellerAccountType === null || $sellerAccountType === 'farmer');
        if ($isFarmerSource && empty($input['farmer_user_id'])) {
            throw new HttpBadRequestException(
                $this->request,
                'farmer_user_id là bắt buộc khi tiếp nhận mủ từ Nông Hộ'
            );
        }
        $landAllocations = [];
        if (array_key_exists('land_allocations', $input)) {
            if ($sellerAccountType !== null && $sellerAccountType !== 'farmer') {
                throw new HttpBadRequestException(
                    $this->request,
                    'land_allocations chỉ áp dụng cho phiếu thu mua từ Nông Hộ'
                );
            }
            if (!is_array($input['land_allocations']) || empty($input['land_allocations'])) {
                throw new HttpBadRequestException($this->request, 'land_allocations phải là mảng không rỗng');
            }
            $seenMapIds = [];
            foreach ($input['land_allocations'] as $allocation) {
                $mapId = (int)($allocation['purchase_order_buyer_land_map_id'] ?? 0);
                $allocationWeight = $allocation['received_weight_kg'] ?? null;
                if ($mapId <= 0 || isset($seenMapIds[$mapId]) || !is_numeric($allocationWeight) || (float)$allocationWeight <= 0) {
                    throw new HttpBadRequestException($this->request, 'Mỗi land_allocation cần mapping duy nhất và received_weight_kg lớn hơn 0');
                }
                $seenMapIds[$mapId] = true;
                $landAllocations[] = [
                    'purchase_order_buyer_land_map_id' => $mapId,
                    'received_weight_kg' => (float)$allocationWeight,
                ];
            }
            usort(
                $landAllocations,
                static fn(array $left, array $right): int =>
                $left['purchase_order_buyer_land_map_id'] <=> $right['purchase_order_buyer_land_map_id']
            );
            if (empty($input['purchase_order_id'])) {
                throw new HttpBadRequestException($this->request, 'purchase_order_id là bắt buộc khi có land_allocations');
            }
            if (abs(array_sum(array_column($landAllocations, 'received_weight_kg')) - (float)($input['received_weight_kg'] ?? 0)) > 0.001) {
                throw new HttpBadRequestException($this->request, 'Tổng land_allocations phải bằng received_weight_kg');
            }
        }
        $mappingAllocations = [];
        if (array_key_exists('mapping_allocations', $input)) {
            if (!in_array($sellerAccountType, ['purchaser', 'trader', 'company'], true)) {
                throw new HttpBadRequestException(
                    $this->request,
                    'mapping_allocations chỉ áp dụng cho phiếu từ công ty/đơn vị thu mua'
                );
            }
            if (!is_array($input['mapping_allocations']) || empty($input['mapping_allocations'])) {
                throw new HttpBadRequestException($this->request, 'mapping_allocations phải là mảng không rỗng');
            }
            $seenMapIds = [];
            foreach ($input['mapping_allocations'] as $allocation) {
                $mapId = (int)($allocation['purchase_order_buyer_seller_sub_tank_map_id'] ?? 0);
                $allocationWeight = $allocation['received_weight_kg'] ?? null;
                if ($mapId <= 0 || isset($seenMapIds[$mapId]) || !is_numeric($allocationWeight) || (float)$allocationWeight <= 0) {
                    throw new HttpBadRequestException($this->request, 'Mỗi mapping_allocation cần mapping duy nhất và received_weight_kg lớn hơn 0');
                }
                $seenMapIds[$mapId] = true;
                $mappingAllocations[] = [
                    'purchase_order_buyer_seller_sub_tank_map_id' => $mapId,
                    'received_weight_kg' => (float)$allocationWeight,
                ];
            }
            usort(
                $mappingAllocations,
                static fn(array $left, array $right): int =>
                $left['purchase_order_buyer_seller_sub_tank_map_id']
                    <=> $right['purchase_order_buyer_seller_sub_tank_map_id']
            );
            if (abs(array_sum(array_column($mappingAllocations, 'received_weight_kg')) - (float)($input['received_weight_kg'] ?? 0)) > 0.001) {
                throw new HttpBadRequestException($this->request, 'Tổng mapping_allocations phải bằng received_weight_kg');
            }
        }
        if (in_array($sellerAccountType, ['purchaser', 'trader', 'company'], true) && empty($mappingAllocations)) {
            throw new HttpBadRequestException(
                $this->request,
                'Phiếu công ty/đơn vị thu mua bắt buộc khai báo mapping_allocations khi tiếp nhận'
            );
        }
        if ($validator->hasErrors()) {
            $messages = [];
            foreach ($validator->getErrors() as $fieldErrors) {
                $messages[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
            }
            throw new HttpBadRequestException($this->request, implode('; ', $messages));
        }

        $clean = $validator->sanitize($input, [
            'received_weight_kg' => 'float',
            'rubber_type' => 'string',
            'received_at' => 'string',
            'purchase_order_id' => 'integer',
            'seller_source_type' => 'string',
            'vendor_id' => 'integer',
            'farmer_user_id' => 'integer',
            'transaction_ticket_id' => 'integer',
            'transaction_ticket_code' => 'string',
            'latex_color' => 'string',
            'ph_value' => 'float',
            'nh3_percent' => 'float',
            'impurity_percent' => 'float',
            'tsc_percent' => 'float',
            'temperature_c' => 'float',
            'harvested_at' => 'string',
            'notes' => 'string',
        ]);
        if (!$isFarmerSource) {
            $clean['farmer_user_id'] = 0;
        }
        try {
            $intake = $this->intakeRepository->create(array_merge($clean, [
                'sub_tank_id' => $tank->getId(),
                'company_id' => (int)($this->auth_data['company_id'] ?? 0),
                'purchaser_user_id' => $userId,
                'land_allocations' => $landAllocations,
                'mapping_allocations' => $mappingAllocations,
            ]));
        } catch (RuntimeException $exception) {
            throw new HttpBadRequestException($this->request, $exception->getMessage(), $exception);
        }

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => Utils::generateRandomString(25),
            'purchasing_sub_tank_intake' => $intake,
        ], 201);
    }
}
