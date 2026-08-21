<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionOrder;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class ViewExecutionProductionOrderAction extends ProductionOrderAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);

        $scope = Utils::resolveScope($permissions, 'production_order', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Không có quyền truy cập lệnh sản xuất');
        }

        $production_order_code = addslashes(trim((string)$this->resolveArg('code')));

        $production_order = $this->productionOrderRepository->findProductionOrderOfCodeWithPermission(
            $production_order_code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($production_order)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy lệnh sản xuất');
        }

        $query = $this->request->getQueryParams();
        $validator = new Validator($this->request);
        $statusRule = 'string|in:draft,in_progress,completed,cancelled,all';

        $validator->validate('status', $query['status'] ?? null, $statusRule);
        $validator->validate('channel_status', $query['channel_status'] ?? null, $statusRule);
        $validator->validate('settling_tank_status', $query['settling_tank_status'] ?? null, $statusRule);
        $validator->validate('cutting_status', $query['cutting_status'] ?? null, $statusRule);
        $validator->validate('rolling_status', $query['rolling_status'] ?? null, $statusRule);
        $validator->validate('hanging_status', $query['hanging_status'] ?? null, $statusRule);
        $validator->validate('drying_status', $query['drying_status'] ?? null, $statusRule);
        $validator->validate('pressing_status', $query['pressing_status'] ?? null, $statusRule);
        $validator->validate('pallet_status', $query['pallet_status'] ?? null, $statusRule);

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

        $cleanQuery = $validator->sanitize($query, [
            'status' => 'string',
            'channel_status' => 'string',
            'settling_tank_status' => 'string',
            'cutting_status' => 'string',
            'rolling_status' => 'string',
            'hanging_status' => 'string',
            'drying_status' => 'string',
            'pressing_status' => 'string',
            'pallet_status' => 'string',
        ]);

        $defaultStatus = $cleanQuery['status'] ?? 'all';
        $filters = [
            'channel_status' => $cleanQuery['channel_status'] ?? $defaultStatus,
            'settling_tank_status' => $cleanQuery['settling_tank_status'] ?? $defaultStatus,
            'cutting_status' => $cleanQuery['cutting_status'] ?? $defaultStatus,
            'rolling_status' => $cleanQuery['rolling_status'] ?? $defaultStatus,
            'hanging_status' => $cleanQuery['hanging_status'] ?? $defaultStatus,
            'drying_status' => $cleanQuery['drying_status'] ?? $defaultStatus,
            'pressing_status' => $cleanQuery['pressing_status'] ?? $defaultStatus,
            'pallet_status' => $cleanQuery['pallet_status'] ?? $defaultStatus,
        ];

        $executionData = call_user_func([$this->productionOrderRepository, 'getExecutionDataOfProductionOrder'], (int)$production_order->getId(), $filters);

        Utils::save_log($this->logger, [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'production_order',
            'action' => 'view_execution',
            'user_id' => (string)$this->auth_data['user_id'],
            'extra_1' => (string)$production_order->getId(),
        ]);

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'production_order' => $production_order->jsonSerialize(),
            'execution' => $executionData,
        ]);
    }
}
