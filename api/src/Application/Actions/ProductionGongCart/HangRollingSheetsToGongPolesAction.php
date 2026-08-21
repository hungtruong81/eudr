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

class HangRollingSheetsToGongPolesAction extends ProductionGongCartAction
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

        $rollerViewScope = Utils::resolveScope($permissions, 'production_roller', 'view');
        if (empty($rollerViewScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền xem lượt cán');
        }

        $gongViewScope = Utils::resolveScope($permissions, 'production_gong_cart', 'view');
        if (empty($gongViewScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền xem xe gòong');
        }

        $gongUpdateScope = Utils::resolveScope($permissions, 'production_gong_cart', 'update');
        if (empty($gongUpdateScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền xử lý bước phơi');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('rolling_run_id', $formData['rolling_run_id'] ?? null, 'required|integer|min:1');
        $validator->validate('gong_cart_code', $formData['gong_cart_code'] ?? null, 'required|string|max:30');
        $validator->validate('details', $formData['details'] ?? null, 'required|array');
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
            'rolling_run_id' => 'integer',
            'gong_cart_code' => 'string',
            'notes' => 'string',
        ]);

        $rollingRunId = (int)$cleanData['rolling_run_id'];
        $gongCartCode = trim((string)$cleanData['gong_cart_code']);
        $detailsInput = $formData['details'] ?? [];

        if (!is_array($detailsInput) || count($detailsInput) === 0) {
            throw new HttpBadRequestException($this->request, 'Danh sách phân bổ quality không hợp lệ');
        }

        $allowedQualityTypes = Utils::getAllowedQualityTypes();
        $seenQualityTypes = [];
        $seenPoles = [];
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

            $inputSheetCount = (int)($item['input_sheet_count'] ?? -1);
            if ($inputSheetCount < 0) {
                throw new HttpBadRequestException($this->request, 'input_sheet_count không hợp lệ tại vị trí ' . $idx);
            }

            $poleNumbers = $item['pole_numbers'] ?? null;
            if (!is_array($poleNumbers) || count($poleNumbers) === 0) {
                throw new HttpBadRequestException($this->request, 'pole_numbers không hợp lệ tại vị trí ' . $idx);
            }

            $normalizedPoles = [];
            foreach ($poleNumbers as $poleNoRaw) {
                if (!is_numeric($poleNoRaw)) {
                    throw new HttpBadRequestException($this->request, 'Số sào không hợp lệ tại vị trí ' . $idx);
                }
                $poleNo = (int)$poleNoRaw;
                if ($poleNo <= 0) {
                    throw new HttpBadRequestException($this->request, 'Số sào phải lớn hơn 0 tại vị trí ' . $idx);
                }
                if (in_array($poleNo, $seenPoles, true)) {
                    throw new HttpBadRequestException($this->request, 'Một sào chỉ được gán cho 1 quality. Sào bị trùng: ' . $poleNo);
                }
                $seenPoles[] = $poleNo;
                $normalizedPoles[] = $poleNo;
            }

            $seenQualityTypes[] = $qualityType;
            $normalizedDetails[] = [
                'quality_type' => $qualityType,
                'grade_id' => isset($item['grade_id']) ? (int)$item['grade_id'] : 0,
                'input_sheet_count' => $inputSheetCount,
                'pole_numbers' => array_values(array_unique($normalizedPoles)),
                'notes' => isset($item['notes']) ? trim((string)$item['notes']) : null,
            ];
        }

        $rollingRun = $this->productionGongCartRepository->findRollingRunOfIdWithPermission(
            $rollingRunId,
            $authUserId,
            (string)$rollerViewScope,
            $authCompanyId
        );
        if (empty($rollingRun)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy rolling run');
        }

        if ((string)($rollingRun['status'] ?? '') !== 'completed') {
            throw new HttpBadRequestException($this->request, 'Rolling run phải ở trạng thái completed để chuyển sang phơi');
        }

        $gongCart = $this->productionGongCartRepository->findProductionGongCartOfCodeWithPermission(
            $gongCartCode,
            $authUserId,
            (string)$gongViewScope,
            $authCompanyId
        );
        if (empty($gongCart)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy xe gòong');
        }

        $gongData = $gongCart->jsonSerialize();
        if ((int)($gongData['factory_id'] ?? 0) !== (int)($rollingRun['factory_id'] ?? 0)) {
            throw new HttpBadRequestException($this->request, 'Xe gòong và rolling run không cùng nhà máy');
        }

        $persistResult = $this->productionGongCartRepository->assignRollingSheetsToHangingPoles([
            'rolling_run_id' => $rollingRunId,
            'gong_cart_id' => (int)$gongData['gong_cart_id'],
            'details' => $normalizedDetails,
            'notes' => trim((string)($cleanData['notes'] ?? '')) !== '' ? $cleanData['notes'] : null,
            'updated_by' => $authUserId,
        ]);

        if (empty($persistResult)) {
            throw new HttpBadRequestException($this->request, 'Xử lý treo tờ mủ lên sào thất bại');
        }

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'production_hanging_run',
            'action' => 'hang_rolling_sheets_to_gong_poles',
            'user_id' => (string)$authUserId,
            'extra_1' => (string)$rollingRunId,
            'extra_2' => (string)($persistResult['hanging_run_id'] ?? 0),
            'extra_3' => $gongCartCode,
        ];

        Utils::save_log($this->logger, $log);

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'data' => $persistResult,
        ]);
    }
}
