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

class UpdateCuttingRunQualityOutputsAction extends ProductionCuttingMachineAction
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

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('cutting_run_id', $formData['cutting_run_id'] ?? null, 'required|integer|min:1');
        $validator->validate('outputs', $formData['outputs'] ?? null, 'required|array');
        $validator->validate('started_at', $formData['started_at'] ?? null, 'string');
        $validator->validate('ended_at', $formData['ended_at'] ?? null, 'string');

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
            'started_at' => 'string',
            'ended_at' => 'string',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);
        $cuttingRunId = (int)$cleanData['cutting_run_id'];

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

        $outputsInput = $formData['outputs'] ?? [];
        if (!is_array($outputsInput) || count($outputsInput) === 0) {
            throw new HttpBadRequestException($this->request, 'Danh sách outputs không hợp lệ');
        }

        $allowedQualityTypes = Utils::getAllowedQualityTypes();
        $seenQualityTypes = [];
        $normalizedOutputs = [];

        foreach ($outputsInput as $idx => $item) {
            if (!is_array($item)) {
                throw new HttpBadRequestException($this->request, 'Output tại vị trí ' . $idx . ' không hợp lệ');
            }

            $qualityType = trim((string)($item['quality_type'] ?? ''));
            if ($qualityType === '' || !in_array($qualityType, $allowedQualityTypes, true)) {
                throw new HttpBadRequestException($this->request, 'quality_type không hợp lệ tại vị trí ' . $idx);
            }
            if (in_array($qualityType, $seenQualityTypes, true)) {
                throw new HttpBadRequestException($this->request, 'quality_type bị trùng: ' . $qualityType);
            }

            if (!isset($item['output_sheet_count']) || !is_numeric($item['output_sheet_count']) || (int)$item['output_sheet_count'] < 0) {
                throw new HttpBadRequestException($this->request, 'output_sheet_count không hợp lệ tại vị trí ' . $idx);
            }

            $thicknessMin = isset($item['output_sheet_thickness_min_mm']) ? (float)$item['output_sheet_thickness_min_mm'] : 15.00;
            $thicknessMax = isset($item['output_sheet_thickness_max_mm']) ? (float)$item['output_sheet_thickness_max_mm'] : 20.00;
            if ($thicknessMin <= 0 || $thicknessMax <= 0 || $thicknessMin > $thicknessMax) {
                throw new HttpBadRequestException($this->request, 'Độ dày min/max không hợp lệ tại vị trí ' . $idx);
            }

            $seenQualityTypes[] = $qualityType;
            $normalizedOutputs[] = [
                'quality_type' => $qualityType,
                'grade_id' => isset($item['grade_id']) ? (int)$item['grade_id'] : 0,
                'output_sheet_count' => (int)$item['output_sheet_count'],
                'output_sheet_thickness_min_mm' => $thicknessMin,
                'output_sheet_thickness_max_mm' => $thicknessMax,
                'notes' => isset($item['notes']) ? trim((string)$item['notes']) : null,
            ];
        }

        $cuttingRun = $this->productionCuttingMachineRepository->findCuttingRunOfIdWithPermission(
            $cuttingRunId,
            $authUserId,
            (string)$cuttingViewScope,
            $authCompanyId
        );
        if (empty($cuttingRun)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy cutting run');
        }

        $persistResult = $this->productionCuttingMachineRepository->updateCuttingRunQualityOutputs([
            'cutting_run_id' => $cuttingRunId,
            'outputs' => $normalizedOutputs,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'updated_by' => $authUserId,
        ]);

        if (empty($persistResult)) {
            throw new HttpBadRequestException($this->request, 'Cập nhật quality outputs thất bại');
        }

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'production_cutting_quality_output',
            'action' => 'update_cutting_quality_outputs',
            'user_id' => (string)$authUserId,
            'extra_1' => (string)$cuttingRunId,
            'extra_2' => (string)count($normalizedOutputs),
            'extra_3' => (string)($persistResult['status'] ?? ''),
        ];

        Utils::save_log($this->logger, $log);

        $res_return = ['result' => 'success'];
        $res_return['trace_id'] = $trace_id;
        $res_return['data'] = $persistResult;

        return $this->respondWithData($res_return);
    }
}
