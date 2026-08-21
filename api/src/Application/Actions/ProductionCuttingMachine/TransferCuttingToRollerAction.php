<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionCuttingMachine;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class TransferCuttingToRollerAction extends ProductionCuttingMachineAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $authUserId = (int)$this->auth_data['user_id'];
        $authCompanyId = (int)($this->auth_data['company_id'] ?? 0);

        $permissions = $this->userRepository->getUserPermissions($authUserId);

        $cuttingViewScope = Utils::resolveScope($permissions, 'production_cutting_machine', 'view');
        if (empty($cuttingViewScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền xem lượt cắt');
        }

        $cuttingUpdateScope = Utils::resolveScope($permissions, 'production_cutting_machine', 'update');
        if (empty($cuttingUpdateScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền cập nhật lượt cắt');
        }

        $rollerViewScope = Utils::resolveScope($permissions, 'production_roller', 'view');
        if (empty($rollerViewScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền xem máy cán');
        }

        $rollerCreateScope = Utils::resolveScope($permissions, 'production_roller', 'create');
        if (empty($rollerCreateScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền tạo lượt cán');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('cutting_run_id', $formData['cutting_run_id'] ?? null, 'required|integer|min:1');
        $validator->validate('roller_code', $formData['roller_code'] ?? null, 'required|string|max:30');
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

        $sanitizeRules = [
            'cutting_run_id' => 'integer',
            'roller_code' => 'string',
            'notes' => 'string',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);
        $cuttingRunId = (int)$cleanData['cutting_run_id'];
        $rollerCode = trim((string)$cleanData['roller_code']);
        $notes = trim((string)($cleanData['notes'] ?? ''));

        $cuttingRun = $this->productionCuttingMachineRepository->findCuttingRunOfIdWithPermission(
            $cuttingRunId,
            $authUserId,
            (string)$cuttingViewScope,
            $authCompanyId
        );
        if (empty($cuttingRun)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy cutting run');
        }

        if (!in_array((string)($cuttingRun['status'] ?? ''), ['draft', 'in_progress', 'completed'], true)) {
            throw new HttpBadRequestException($this->request, 'Cutting run không ở trạng thái hợp lệ để chuyển sang cán');
        }

        $roller = $this->productionRollerRepository->findProductionRollerOfCodeWithPermission(
            $rollerCode,
            $authUserId,
            (string)$rollerViewScope,
            $authCompanyId
        );
        if (empty($roller)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy máy cán');
        }

        $rollerData = $roller->jsonSerialize();
        if ((int)($rollerData['factory_id'] ?? 0) !== (int)($cuttingRun['factory_id'] ?? 0)) {
            throw new HttpBadRequestException($this->request, 'Máy cán và cutting run không cùng nhà máy');
        }
        if ((int)($rollerData['company_id'] ?? 0) !== (int)($cuttingRun['company_id'] ?? 0)) {
            throw new HttpBadRequestException($this->request, 'Máy cán và cutting run không cùng công ty');
        }
        if ((string)($rollerData['status'] ?? '') !== 'available') {
            throw new HttpBadRequestException($this->request, 'Máy cán không ở trạng thái available');
        }

        $persistResult = $this->productionCuttingMachineRepository->createRollingRunFromCutting([
            'cutting_run_id' => $cuttingRunId,
            'roller_id' => (int)$rollerData['roller_id'],
            'notes' => $notes !== '' ? $notes : null,
            'updated_by' => $authUserId,
        ]);

        if (empty($persistResult)) {
            throw new HttpBadRequestException($this->request, 'Tạo rolling run thất bại');
        }

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'production_rolling_run',
            'action' => 'transfer_cutting_to_roller',
            'user_id' => (string)$authUserId,
            'extra_1' => (string)$cuttingRunId,
            'extra_2' => (string)$persistResult['rolling_run_id'],
            'extra_3' => (string)$rollerCode,
        ];

        Utils::save_log($this->logger, $log);

        $res_return = ['result' => 'success'];
        $res_return['trace_id'] = $trace_id;
        $res_return['data'] = [
            'cutting_run_id' => $cuttingRunId,
            'roller_code' => $rollerCode,
            'rolling_run' => $persistResult,
        ];

        return $this->respondWithData($res_return);
    }
}
