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

class ApproveSetupChangeRequestProductionOrderAction extends ProductionOrderAction
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
            throw new HttpForbiddenException($this->request, 'Thiếu quyền duyệt yêu cầu thay đổi thiết lập lệnh sản xuất');
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

        $changeRequestId = (int)$this->resolveArg('change_request_id');
        if ($changeRequestId <= 0) {
            throw new HttpBadRequestException($this->request, 'ID yêu cầu thay đổi không hợp lệ');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);
        $validator->validate('approval_notes', $formData['approval_notes'] ?? null, 'string');
        $validator->validate('steps', $formData['steps'] ?? null, 'array');

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
            'approval_notes' => 'string',
        ]);

        $stepTimeOverrides = null;
        if (array_key_exists('steps', $formData)) {
            if (!is_array($formData['steps']) || count($formData['steps']) === 0) {
                throw new HttpBadRequestException($this->request, 'steps phải là mảng và không được rỗng');
            }

            $allowedSteps = ['raw_tank', 'settling_tank', 'channel', 'cutting_machine', 'roller', 'hanging', 'drying', 'pressing', 'pallet'];
            $seenSteps = [];
            $normalizedSteps = [];

            foreach ($formData['steps'] as $idx => $stepItem) {
                if (!is_array($stepItem)) {
                    throw new HttpBadRequestException($this->request, 'steps[' . $idx . '] không hợp lệ');
                }

                $step = trim((string)($stepItem['step'] ?? ''));
                if ($step === '' || !in_array($step, $allowedSteps, true)) {
                    throw new HttpBadRequestException($this->request, 'step không hợp lệ tại steps[' . $idx . ']');
                }
                if (in_array($step, $seenSteps, true)) {
                    throw new HttpBadRequestException($this->request, 'step bị trùng trong steps: ' . $step);
                }

                $startedAt = !empty($stepItem['started_at']) ? trim((string)$stepItem['started_at']) : null;
                $endedAt = !empty($stepItem['ended_at']) ? trim((string)$stepItem['ended_at']) : null;
                if ($startedAt === null && $endedAt === null) {
                    throw new HttpBadRequestException($this->request, 'steps[' . $idx . '] phải có started_at hoặc ended_at');
                }
                if ($startedAt !== null && !Utils::isValidDateTime($startedAt)) {
                    throw new HttpBadRequestException($this->request, 'started_at không đúng định dạng Y-m-d H:i:s tại steps[' . $idx . ']');
                }
                if ($endedAt !== null && !Utils::isValidDateTime($endedAt)) {
                    throw new HttpBadRequestException($this->request, 'ended_at không đúng định dạng Y-m-d H:i:s tại steps[' . $idx . ']');
                }
                if ($startedAt !== null && $endedAt !== null && strtotime($startedAt) > strtotime($endedAt)) {
                    throw new HttpBadRequestException($this->request, 'started_at phải nhỏ hơn hoặc bằng ended_at tại steps[' . $idx . ']');
                }

                $normalizedStep = ['step' => $step];
                if ($startedAt !== null) {
                    $normalizedStep['started_at'] = $startedAt;
                }
                if ($endedAt !== null) {
                    $normalizedStep['ended_at'] = $endedAt;
                }

                $seenSteps[] = $step;
                $normalizedSteps[] = $normalizedStep;
            }

            $stepTimeOverrides = $normalizedSteps;
        }

        try {
            $changeRequest = $this->productionOrderRepository->approveSetupChangeRequest(
                $changeRequestId,
                (int)$this->auth_data['user_id'],
                $cleanData['approval_notes'] ?? null,
                $stepTimeOverrides
            );

            // Verify change request belongs to this order
            if (!isset($changeRequest['production_order_id']) || (int)$changeRequest['production_order_id'] !== (int)$order->getId()) {
                throw new HttpNotFoundException($this->request, 'Yêu cầu thay đổi không thuộc về lệnh sản xuất này');
            }
        } catch (\Exception $e) {
            if ($e instanceof HttpNotFoundException) {
                throw $e;
            }
            throw new HttpNotFoundException($this->request, $e->getMessage());
        }

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'production_order',
            'action' => 'approve_setup_change_request',
            'user_id' => (string)$this->auth_data['user_id'],
            'extra_1' => (string)$order->getId(),
            'extra_2' => (string)$changeRequestId,
        ];
        Utils::save_log($this->logger, $log);

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'production_order_id' => $order->getId(),
            'change_request' => $changeRequest,
        ]);
    }
}
