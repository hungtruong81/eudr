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

class SetupPressingProductionOrderAction extends ProductionOrderAction
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
        $qualities = $formData['qualities'] ?? null;
        if (!is_array($qualities) || empty($qualities)) {
            throw new HttpBadRequestException($this->request, 'Trường qualities là bắt buộc và phải là mảng có ít nhất 1 phần tử');
        }

        $setups = [];
        $processedGradeIds = [];

        try {
            foreach ($qualities as $index => $qualityItem) {
                if (!is_array($qualityItem)) {
                    throw new HttpBadRequestException($this->request, "qualities[$index] phải là object");
                }

                $validator = new Validator($this->request);
                $validator->validate('grade_id', $qualityItem['grade_id'] ?? null, 'required|integer|min:1');
                $validator->validate('product_type_id', $qualityItem['product_type_id'] ?? null, 'required|integer|min:0');
                $validator->validate('planned_sheet_quantity', $qualityItem['planned_sheet_quantity'] ?? null, 'required|integer|min:0');
                $validator->validate('started_at', $qualityItem['started_at'] ?? null, 'string');
                $validator->validate('ended_at', $qualityItem['ended_at'] ?? null, 'string');
                $validator->validate('notes', $qualityItem['notes'] ?? null, 'string');

                if ($validator->hasErrors()) {
                    $errorMessages = [];
                    foreach ($validator->getErrors() as $fieldErrors) {
                        $errorMessages[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
                    }
                    throw new HttpBadRequestException($this->request, 'Lỗi qualities[' . $index . ']: ' . implode('; ', $errorMessages));
                }

                $cleanItem = $validator->sanitize($qualityItem, [
                    'grade_id' => 'integer',
                    'product_type_id' => 'integer',
                    'planned_sheet_quantity' => 'integer',
                    'started_at' => 'string',
                    'ended_at' => 'string',
                    'notes' => 'string',
                ]);

                $startedAt = !empty($cleanItem['started_at']) ? trim((string)$cleanItem['started_at']) : null;
                $endedAt = !empty($cleanItem['ended_at']) ? trim((string)$cleanItem['ended_at']) : null;
                if ($startedAt !== null && !Utils::isValidDateTime($startedAt)) {
                    throw new HttpBadRequestException($this->request, 'Lỗi qualities[' . $index . ']: started_at không đúng định dạng Y-m-d H:i:s');
                }
                if ($endedAt !== null && !Utils::isValidDateTime($endedAt)) {
                    throw new HttpBadRequestException($this->request, 'Lỗi qualities[' . $index . ']: ended_at không đúng định dạng Y-m-d H:i:s');
                }
                if ($startedAt !== null && $endedAt !== null && strtotime($startedAt) > strtotime($endedAt)) {
                    throw new HttpBadRequestException($this->request, 'Lỗi qualities[' . $index . ']: started_at phải nhỏ hơn hoặc bằng ended_at');
                }

                $gradeId = (int)$cleanItem['grade_id'];
                if (isset($processedGradeIds[$gradeId])) {
                    throw new HttpBadRequestException($this->request, 'Trùng grade_id trong payload: ' . $gradeId);
                }
                $processedGradeIds[$gradeId] = true;

                $setups = $this->productionOrderRepository->setupPressing(
                    (int)$order->getId(),
                    $gradeId,
                    (int)$cleanItem['product_type_id'],
                    (int)$cleanItem['planned_sheet_quantity'],
                    $startedAt,
                    $endedAt,
                    $cleanItem['notes'] ?? null,
                    (int)($this->auth_data['company_id'] ?? 0),
                    (int)$order->getFactoryId(),
                    (int)$this->auth_data['user_id']
                );
            }
        } catch (\Exception $e) {
            if ($e instanceof HttpBadRequestException) {
                throw $e;
            }
            throw new HttpNotFoundException($this->request, $e->getMessage());
        }

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'production_order',
            'action' => 'setup_pressing',
            'user_id' => (string)$this->auth_data['user_id'],
            'extra_1' => (string)$order->getId(),
            'extra_2' => implode(',', array_keys($processedGradeIds)),
        ];

        Utils::save_log($this->logger, $log);

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'production_order_id' => $order->getId(),
            'pressing_setups_by_quality' => $setups,
        ]);
    }
}
