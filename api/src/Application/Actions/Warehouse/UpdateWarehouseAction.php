<?php

declare(strict_types=1);

namespace App\Application\Actions\Warehouse;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class UpdateWarehouseAction extends WarehouseAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'warehouse', 'update');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $factoryScope = Utils::resolveScope($permissions, 'factory', 'view');
        if (empty($factoryScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập nhà máy');
        }

        $warehouse_code = addslashes(trim((string)$this->resolveArg('code')));
        $warehouse = $this->warehouseRepository->findWarehouseOfCodeWithPermission(
            $warehouse_code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($warehouse)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy kho');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('warehouse_name', $formData['warehouse_name'] ?? null, 'required|string');
        $validator->validate('factory_id', $formData['factory_id'] ?? null, 'required|integer|min:1');
        $validator->validate('warehouse_type', $formData['warehouse_type'] ?? null, 'required|in:raw_material,intermediate,finished_goods,production_pallet,transit');
        $validator->validate('address', $formData['address'] ?? null, 'string');
        $validator->validate('manager_user_id', $formData['manager_user_id'] ?? null, 'integer|min:1');
        $validator->validate('capacity_pallet', $formData['capacity_pallet'] ?? null, 'required|integer|min:0');
        $validator->validate('max_weight_kg', $formData['max_weight_kg'] ?? null, 'required|numeric|min:0');
        $validator->validate('status', $formData['status'] ?? null, 'required|in:active,maintenance,blocked,inactive');
        $validator->validate('notes', $formData['notes'] ?? null, 'string');

        if ($validator->hasErrors()) {
            $errorMessages = [];
            $errors = $validator->getErrors();
            if (is_array($errors)) {
                foreach ($errors as $fieldErrors) {
                    if (is_array($fieldErrors)) {
                        $errorMessages[] = implode(', ', $fieldErrors);
                    } else {
                        $errorMessages[] = (string)$fieldErrors;
                    }
                }
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errorMessages));
        }

        $cleanData = $validator->sanitize($formData, [
            'warehouse_name' => 'string',
            'factory_id' => 'integer',
            'warehouse_type' => 'string',
            'address' => 'string',
            'manager_user_id' => 'integer',
            'capacity_pallet' => 'integer',
            'max_weight_kg' => 'float',
            'status' => 'string',
            'notes' => 'string',
        ]);

        $factory = $this->factoryRepository->findFactoryOfIdWithPermission(
            (int)$cleanData['factory_id'],
            (int)$this->auth_data['user_id'],
            (string)$factoryScope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($factory)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy nhà máy');
        }

        $updated = $this->warehouseRepository->updateWarehouseWithPermission(
            (int)$warehouse->getId(),
            [
                'warehouse_name' => $cleanData['warehouse_name'],
                'factory_id' => (int)$cleanData['factory_id'],
                'warehouse_type' => $cleanData['warehouse_type'],
                'address' => $cleanData['address'] ?? '',
                'manager_user_id' => (int)($cleanData['manager_user_id'] ?? 0),
                'capacity_pallet' => (int)$cleanData['capacity_pallet'],
                'max_weight_kg' => (float)$cleanData['max_weight_kg'],
                'status' => $cleanData['status'],
                'notes' => $cleanData['notes'] ?? '',
                'updated_at' => date('Y-m-d H:i:s', time()),
                'updated_by' => $this->auth_data['user_id'],
            ],
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        Utils::save_log($this->logger, [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'warehouse',
            'action' => 'update',
            'user_id' => (string)$this->auth_data['user_id'],
            'extra_1' => (string)$updated->getId(),
        ]);

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'warehouse' => $updated->jsonSerialize(),
        ]);
    }
}
