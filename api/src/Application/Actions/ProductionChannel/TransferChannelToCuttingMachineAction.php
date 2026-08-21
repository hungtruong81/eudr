<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionChannel;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class TransferChannelToCuttingMachineAction extends ProductionChannelAction
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

        $channelViewScope = Utils::resolveScope($permissions, 'production_channel', 'view');
        if (empty($channelViewScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền xem mương');
        }

        $channelUpdateScope = Utils::resolveScope($permissions, 'production_channel', 'update');
        if (empty($channelUpdateScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền cập nhật mương');
        }

        $cuttingViewScope = Utils::resolveScope($permissions, 'production_cutting_machine', 'view');
        if (empty($cuttingViewScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền xem máy cắt');
        }

        $cuttingCreateScope = Utils::resolveScope($permissions, 'production_cutting_machine', 'create');
        if (empty($cuttingCreateScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền tạo lượt cắt');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('channel_run_id', $formData['channel_run_id'] ?? null, 'required|integer|min:1');
        $validator->validate('cutting_machine_code', $formData['cutting_machine_code'] ?? null, 'required|string|max:30');
        $validator->validate('input_channel_latex_kg', $formData['input_channel_latex_kg'] ?? null, 'required|numeric|min:0.001');
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
            'channel_run_id' => 'integer',
            'cutting_machine_code' => 'string',
            'input_channel_latex_kg' => 'float',
            'notes' => 'string',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);
        $channelRunId = (int)$cleanData['channel_run_id'];
        $cuttingMachineCode = trim((string)$cleanData['cutting_machine_code']);
        $inputChannelLatexKg = (float)$cleanData['input_channel_latex_kg'];
        $notes = trim((string)($cleanData['notes'] ?? ''));

        $channelRun = $this->productionChannelRepository->findChannelRunOfIdWithPermission(
            $channelRunId,
            $authUserId,
            (string)$channelViewScope,
            $authCompanyId
        );
        if (empty($channelRun)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy channel run');
        }

        if (!in_array((string)($channelRun['status'] ?? ''), ['draft', 'in_progress'], true)) {
            throw new HttpBadRequestException($this->request, 'Channel run không ở trạng thái hợp lệ để đưa sang máy cắt');
        }

        $cuttingMachine = $this->productionCuttingMachineRepository->findProductionCuttingMachineOfCodeWithPermission(
            $cuttingMachineCode,
            $authUserId,
            (string)$cuttingViewScope,
            $authCompanyId
        );
        if (empty($cuttingMachine)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy máy cắt');
        }

        $cuttingMachineData = $cuttingMachine->jsonSerialize();
        if ((int)($cuttingMachineData['factory_id'] ?? 0) !== (int)($channelRun['factory_id'] ?? 0)) {
            throw new HttpBadRequestException($this->request, 'Máy cắt và channel run không cùng nhà máy');
        }
        if ((int)($cuttingMachineData['company_id'] ?? 0) !== (int)($channelRun['company_id'] ?? 0)) {
            throw new HttpBadRequestException($this->request, 'Máy cắt và channel run không cùng công ty');
        }
        if ((string)($cuttingMachineData['status'] ?? '') !== 'available') {
            throw new HttpBadRequestException($this->request, 'Máy cắt không ở trạng thái available');
        }

        $persistResult = $this->productionChannelRepository->createCuttingRunFromChannel([
            'channel_run_id' => $channelRunId,
            'cutting_machine_id' => (int)$cuttingMachineData['cutting_machine_id'],
            'input_channel_latex_kg' => $inputChannelLatexKg,
            'notes' => $notes !== '' ? $notes : null,
            'updated_by' => $authUserId,
        ]);

        if (empty($persistResult)) {
            throw new HttpBadRequestException($this->request, 'Tạo cutting run thất bại');
        }

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'production_cutting_run',
            'action' => 'transfer_channel_to_cutting_machine',
            'user_id' => (string)$authUserId,
            'extra_1' => (string)$channelRunId,
            'extra_2' => (string)$persistResult['cutting_run_id'],
            'extra_3' => (string)$cuttingMachineCode,
        ];

        Utils::save_log($this->logger, $log);

        $res_return = ['result' => 'success'];
        $res_return['trace_id'] = $trace_id;
        $res_return['data'] = [
            'channel_run_id' => $channelRunId,
            'cutting_machine_code' => $cuttingMachineCode,
            'cutting_run' => $persistResult,
        ];

        return $this->respondWithData($res_return);
    }
}
