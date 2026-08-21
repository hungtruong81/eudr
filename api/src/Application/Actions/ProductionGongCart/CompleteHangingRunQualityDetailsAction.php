<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionGongCart;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class CompleteHangingRunQualityDetailsAction extends ProductionGongCartAction
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

        $gongViewScope = Utils::resolveScope($permissions, 'production_gong_cart', 'view');
        if (empty($gongViewScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền xem bước phơi');
        }

        $gongUpdateScope = Utils::resolveScope($permissions, 'production_gong_cart', 'update');
        if (empty($gongUpdateScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền cập nhật bước phơi');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('hanging_run_id', $formData['hanging_run_id'] ?? null, 'required|integer|min:1');
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
            'hanging_run_id' => 'integer',
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

        $hangingRunId = (int)$cleanData['hanging_run_id'];
        $detailsInput = $formData['details'] ?? [];

        if (!is_array($detailsInput) || count($detailsInput) === 0) {
            throw new HttpBadRequestException($this->request, 'Danh sách details không hợp lệ');
        }

        $allowedQualityTypes = Utils::getAllowedQualityTypes();
        $seenQualityTypes = [];
        $normalizedDetails = [];

        foreach ($detailsInput as $idx => $item) {
            if (!is_array($item)) {
                throw new HttpBadRequestException($this->request, 'Detail tại vị trí ' . $idx . ' không hợp lệ');
            }

            $qualityType = trim((string)($item['quality_type'] ?? ''));
            if ($qualityType === '' || !in_array($qualityType, $allowedQualityTypes, true)) {
                throw new HttpBadRequestException($this->request, 'quality_type không hợp lệ tại vị trí ' . $idx);
            }
            if (in_array($qualityType, $seenQualityTypes, true)) {
                throw new HttpBadRequestException($this->request, 'quality_type bị trùng: ' . $qualityType);
            }

            $outputSheetCount = (int)($item['output_sheet_count'] ?? -1);
            if ($outputSheetCount < 0) {
                throw new HttpBadRequestException($this->request, 'output_sheet_count không hợp lệ tại vị trí ' . $idx);
            }

            $seenQualityTypes[] = $qualityType;
            $normalizedDetails[] = [
                'quality_type' => $qualityType,
                'output_sheet_count' => $outputSheetCount,
                'notes' => isset($item['notes']) ? trim((string)$item['notes']) : null,
            ];
        }

        $hangingRun = $this->productionGongCartRepository->findHangingRunOfIdWithPermission(
            $hangingRunId,
            $authUserId,
            (string)$gongViewScope,
            $authCompanyId
        );
        if (empty($hangingRun)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy hanging run');
        }

        $persistResult = $this->productionGongCartRepository->completeHangingRunQualityDetails([
            'hanging_run_id' => $hangingRunId,
            'details' => $normalizedDetails,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'updated_by' => $authUserId,
        ]);

        if (empty($persistResult)) {
            throw new HttpBadRequestException($this->request, 'Ghi nhận sản lượng sau phơi thất bại');
        }

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'production_hanging_run',
            'action' => 'complete_hanging_run_quality_details',
            'user_id' => (string)$authUserId,
            'extra_1' => (string)$hangingRunId,
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
