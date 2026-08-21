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

class SetupRawTankProductionOrderAction extends ProductionOrderAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);

        $orderScope = Utils::resolveScope($permissions, 'production_order', 'update');
        if (empty($orderScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền cấu hình lệnh sản xuất');
        }

        $code = addslashes(trim((string)$this->resolveArg('code')));

        $order = $this->productionOrderRepository->findProductionOrderOfCodeWithPermission(
            $code,
            (int)$this->auth_data['user_id'],
            (string)$orderScope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($order)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy lệnh sản xuất');
        }

        if (!in_array($order->getStatus(), ['draft', 'approved'], true)) {
            throw new HttpBadRequestException($this->request, 'Lệnh sản xuất không ở trạng thái cho phép cấu hình (draft hoặc approved)');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('raw_tank_id', $formData['raw_tank_id'] ?? null, 'required|integer|min:1');
        $validator->validate('planned_volume_kg', $formData['planned_volume_kg'] ?? null, 'required|numeric|min:0');
        $validator->validate('actual_volume_kg', $formData['actual_volume_kg'] ?? null, 'numeric|min:0');
        $validator->validate('started_at', $formData['started_at'] ?? null, 'string');
        $validator->validate('ended_at', $formData['ended_at'] ?? null, 'string');
        $validator->validate('notes', $formData['notes'] ?? null, 'string');

        if ($validator->hasErrors()) {
            $errorMessages = [];
            foreach ($validator->getErrors() as $fieldErrors) {
                $errorMessages[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errorMessages));
        }

        $cleanData = $validator->sanitize($formData, [
            'raw_tank_id'       => 'integer',
            'planned_volume_kg' => 'float',
            'actual_volume_kg'  => 'float',
            'started_at'        => 'string',
            'ended_at'          => 'string',
            'notes'             => 'string',
        ]);

        $startedAt = !empty($cleanData['started_at']) ? trim((string)$cleanData['started_at']) : null;
        $endedAt = !empty($cleanData['ended_at']) ? trim((string)$cleanData['ended_at']) : null;
        if ($startedAt !== null && !Utils::isValidDateTime($startedAt)) {
            throw new HttpBadRequestException($this->request, 'started_at không đúng định dạng Y-m-d H:i:s');
        }
        if ($endedAt !== null && !Utils::isValidDateTime($endedAt)) {
            throw new HttpBadRequestException($this->request, 'ended_at không đúng định dạng Y-m-d H:i:s');
        }
        if ($startedAt !== null && $endedAt !== null && strtotime($startedAt) > strtotime($endedAt)) {
            throw new HttpBadRequestException($this->request, 'started_at phải nhỏ hơn hoặc bằng ended_at');
        }

        try {
            $setups = $this->productionOrderRepository->setupRawTank(
                (int)$order->getId(),
                (int)$cleanData['raw_tank_id'],
                (float)$cleanData['planned_volume_kg'],
                isset($cleanData['actual_volume_kg']) ? (float)$cleanData['actual_volume_kg'] : null,
                $startedAt,
                $endedAt,
                $cleanData['notes'] ?? null,
                (int)($this->auth_data['company_id'] ?? 0),
                (int)$order->getFactoryId(),
                (int)$this->auth_data['user_id']
            );
        } catch (\Exception $e) {
            throw new HttpNotFoundException($this->request, $e->getMessage());
        }

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id'     => $trace_id,
            'log_type'     => 'production_order',
            'action'       => 'setup_raw_tank',
            'user_id'      => (string)$this->auth_data['user_id'],
            'extra_1'      => (string)$order->getId(),
            'extra_2'      => (string)$cleanData['raw_tank_id'],
        ];
        
        Utils::save_log($this->logger, $log);

        return $this->respondWithData([
            'result'             => 'success',
            'trace_id'           => $trace_id,
            'production_order_id' => $order->getId(),
            'raw_tank_setups'    => $setups,
        ]);
    }
}
