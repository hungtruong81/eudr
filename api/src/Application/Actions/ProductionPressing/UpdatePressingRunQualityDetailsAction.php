<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionPressing;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class UpdatePressingRunQualityDetailsAction extends ProductionPressingAction
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

        $pressingViewScope = Utils::resolveScope($permissions, 'production_pallet', 'view');
        if (empty($pressingViewScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền xem lượt ép bành');
        }

        $pressingUpdateScope = Utils::resolveScope($permissions, 'production_pallet', 'update');
        if (empty($pressingUpdateScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền cập nhật lượt ép bành');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('pressing_run_id', $formData['pressing_run_id'] ?? null, 'required|integer|min:1');
        $validator->validate('details', $formData['details'] ?? null, 'required|array');
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

        $cleanData = $validator->sanitize($formData, [
            'pressing_run_id' => 'integer',
            'started_at' => 'string',
            'ended_at' => 'string',
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

        $pressingRunId = (int)$cleanData['pressing_run_id'];
        $detailsInput = $formData['details'] ?? [];

        if (!is_array($detailsInput) || count($detailsInput) === 0) {
            throw new HttpBadRequestException($this->request, 'Danh sách details không hợp lệ');
        }

        $seenGradeIds = [];
        $normalizedDetails = [];

        foreach ($detailsInput as $idx => $item) {
            if (!is_array($item)) {
                throw new HttpBadRequestException($this->request, 'Detail tại vị trí ' . $idx . ' không hợp lệ');
            }

            $gradeId = (int)($item['grade_id'] ?? 0);
            if ($gradeId <= 0) {
                throw new HttpBadRequestException($this->request, 'grade_id không hợp lệ tại vị trí ' . $idx);
            }
            if (in_array($gradeId, $seenGradeIds, true)) {
                throw new HttpBadRequestException($this->request, 'grade_id bị trùng: ' . $gradeId);
            }

            $productTypeId = (int)($item['product_type_id'] ?? 0);
            if ($productTypeId < 0) {
                throw new HttpBadRequestException($this->request, 'product_type_id không hợp lệ tại vị trí ' . $idx);
            }

            $qualifiedSheetCount = (int)($item['qualified_sheet_count'] ?? -1);
            if ($qualifiedSheetCount < 0) {
                throw new HttpBadRequestException($this->request, 'qualified_sheet_count không hợp lệ tại vị trí ' . $idx);
            }

            $rejectedSheetCount = (int)($item['rejected_sheet_count'] ?? -1);
            if ($rejectedSheetCount < 0) {
                throw new HttpBadRequestException($this->request, 'rejected_sheet_count không hợp lệ tại vị trí ' . $idx);
            }

            $outputBaleCount = (int)($item['output_bale_count'] ?? -1);
            if ($outputBaleCount < 0) {
                throw new HttpBadRequestException($this->request, 'output_bale_count không hợp lệ tại vị trí ' . $idx);
            }

            $seenGradeIds[] = $gradeId;
            $normalizedDetails[] = [
                'grade_id' => $gradeId,
                'product_type_id' => $productTypeId,
                'qualified_sheet_count' => $qualifiedSheetCount,
                'rejected_sheet_count' => $rejectedSheetCount,
                'output_bale_count' => $outputBaleCount,
                'notes' => isset($item['notes']) ? trim((string)$item['notes']) : null,
            ];
        }

        $pressingRun = $this->productionPressingRepository->findPressingRunOfIdWithPermission(
            $pressingRunId,
            $authUserId,
            (string)$pressingViewScope,
            $authCompanyId
        );
        if (empty($pressingRun)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy pressing run');
        }

        $persistResult = $this->productionPressingRepository->updatePressingRunQualityDetails([
            'pressing_run_id' => $pressingRunId,
            'details' => $normalizedDetails,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'updated_by' => $authUserId,
        ]);

        if (empty($persistResult)) {
            throw new HttpBadRequestException($this->request, 'Cập nhật pressing quality details thất bại');
        }

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'production_pressing_quality_detail',
            'action' => 'update_pressing_quality_details',
            'user_id' => (string)$authUserId,
            'extra_1' => (string)$pressingRunId,
            'extra_2' => (string)count($normalizedDetails),
            'extra_3' => (string)($persistResult['status'] ?? ''),
        ];

        Utils::save_log($this->logger, $log);

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'data' => $persistResult,
        ]);
    }
}
