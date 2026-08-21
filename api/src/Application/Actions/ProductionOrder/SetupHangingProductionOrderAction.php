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

class SetupHangingProductionOrderAction extends ProductionOrderAction
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

        $validator->validate('gong_cart_id', $formData['gong_cart_id'] ?? null, 'required|integer|min:1');
        $validator->validate('expected_hanging_hours', $formData['expected_hanging_hours'] ?? null, 'integer|min:0');
        $validator->validate('details', $formData['details'] ?? null, 'required|array');
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
            'gong_cart_id' => 'integer',
            'expected_hanging_hours' => 'integer',
            'started_at' => 'string',
            'ended_at' => 'string',
            'notes' => 'string',
        ]);

        $detailsInput = $formData['details'] ?? [];
        if (!is_array($detailsInput) || count($detailsInput) === 0) {
            throw new HttpBadRequestException($this->request, 'details phải là mảng và không được rỗng');
        }

        $allowedQualityTypes = Utils::getAllowedQualityTypes();
        $seenQualityTypes = [];
        $seenPoles = [];
        $normalizedDetails = [];

        foreach ($detailsInput as $idx => $item) {
            if (!is_array($item)) {
                throw new HttpBadRequestException($this->request, 'details[' . $idx . '] không hợp lệ');
            }

            $qualityType = trim((string)($item['quality_type'] ?? ''));
            if ($qualityType === '' || !in_array($qualityType, $allowedQualityTypes, true)) {
                throw new HttpBadRequestException($this->request, 'quality_type không hợp lệ tại details[' . $idx . ']');
            }
            if (in_array($qualityType, $seenQualityTypes, true)) {
                throw new HttpBadRequestException($this->request, 'quality_type bị trùng: ' . $qualityType);
            }

            $inputSheetCount = filter_var($item['input_sheet_count'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            if ($inputSheetCount === false) {
                throw new HttpBadRequestException($this->request, 'input_sheet_count không hợp lệ tại details[' . $idx . ']');
            }

            $poleNumbersRaw = $item['pole_numbers'] ?? null;
            if (!is_array($poleNumbersRaw) || count($poleNumbersRaw) === 0) {
                throw new HttpBadRequestException($this->request, 'pole_numbers không hợp lệ tại details[' . $idx . ']');
            }

            $normalizedPoleNumbers = [];
            foreach ($poleNumbersRaw as $poleNoRaw) {
                $poleNo = filter_var($poleNoRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($poleNo === false) {
                    throw new HttpBadRequestException($this->request, 'Số sào phải là số nguyên >= 1 tại details[' . $idx . ']');
                }
                if (in_array((int)$poleNo, $seenPoles, true)) {
                    throw new HttpBadRequestException($this->request, 'Một sào chỉ được gán cho 1 quality. Sào bị trùng: ' . (int)$poleNo);
                }
                $seenPoles[] = (int)$poleNo;
                $normalizedPoleNumbers[] = (int)$poleNo;
            }

            $seenQualityTypes[] = $qualityType;
            $normalizedDetails[] = [
                'quality_type' => $qualityType,
                'input_sheet_count' => (int)$inputSheetCount,
                'pole_numbers' => array_values(array_unique($normalizedPoleNumbers)),
                'notes' => isset($item['notes']) ? trim((string)$item['notes']) : null,
            ];
        }

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
            $setup = $this->productionOrderRepository->setupHanging(
                (int)$order->getId(),
                (int)$cleanData['gong_cart_id'],
                isset($cleanData['expected_hanging_hours']) ? (int)$cleanData['expected_hanging_hours'] : null,
                $startedAt,
                $endedAt,
                $normalizedDetails,
                $cleanData['notes'] ?? null,
                (int)($this->auth_data['company_id'] ?? 0),
                (int)$order->getFactoryId(),
                (int)$this->auth_data['user_id']
            );
        } catch (\InvalidArgumentException $e) {
            throw new HttpBadRequestException($this->request, $e->getMessage());
        } catch (\Exception $e) {
            throw new HttpNotFoundException($this->request, $e->getMessage());
        }

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'production_order',
            'action' => 'setup_hanging',
            'user_id' => (string)$this->auth_data['user_id'],
            'extra_1' => (string)$order->getId(),
            'extra_2' => (string)$cleanData['gong_cart_id'],
        ];

        Utils::save_log($this->logger, $log);

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'production_order_id' => $order->getId(),
            'hanging_setup' => $setup,
        ]);
    }
}
