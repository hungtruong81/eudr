<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionOven;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class TransferHangingToDryingAction extends ProductionOvenAction
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

        $ovenViewScope = Utils::resolveScope($permissions, 'production_oven', 'view');
        if (empty($ovenViewScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền xem lò sấy');
        }

        $ovenCreateScope = Utils::resolveScope($permissions, 'production_oven', 'create');
        if (empty($ovenCreateScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền tạo lượt sấy');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('hanging_run_id', $formData['hanging_run_id'] ?? null, 'required|integer|min:1');
        $validator->validate('oven_code', $formData['oven_code'] ?? null, 'required|string|max:30');
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
            'hanging_run_id' => 'integer',
            'oven_code' => 'string',
            'notes' => 'string',
        ]);

        $hangingRunId = (int)$cleanData['hanging_run_id'];
        $ovenCode = trim((string)$cleanData['oven_code']);
        $notes = trim((string)($cleanData['notes'] ?? ''));

        $hangingRun = $this->productionOvenRepository->findHangingRunOfIdWithPermission(
            $hangingRunId,
            $authUserId,
            (string)$gongViewScope,
            $authCompanyId
        );
        if (empty($hangingRun)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy hanging run');
        }

        if ((string)($hangingRun['status'] ?? '') !== 'completed') {
            throw new HttpBadRequestException($this->request, 'Hanging run phải ở trạng thái completed để chuyển sang sấy');
        }

        $oven = $this->productionOvenRepository->findProductionOvenOfCodeWithPermission(
            $ovenCode,
            $authUserId,
            (string)$ovenViewScope,
            $authCompanyId
        );
        if (empty($oven)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy lò sấy');
        }

        $ovenData = $oven->jsonSerialize();
        if ((int)($ovenData['factory_id'] ?? 0) !== (int)($hangingRun['factory_id'] ?? 0)) {
            throw new HttpBadRequestException($this->request, 'Lò sấy và hanging run không cùng nhà máy');
        }

        $persistResult = $this->productionOvenRepository->createDryingRunFromHanging([
            'hanging_run_id' => $hangingRunId,
            'oven_id' => (int)$ovenData['oven_id'],
            'notes' => $notes !== '' ? $notes : null,
            'updated_by' => $authUserId,
        ]);

        if (empty($persistResult)) {
            throw new HttpBadRequestException($this->request, 'Tạo drying run thất bại');
        }

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'production_drying_run',
            'action' => 'transfer_hanging_to_drying',
            'user_id' => (string)$authUserId,
            'extra_1' => (string)$hangingRunId,
            'extra_2' => (string)$persistResult['drying_run_id'],
            'extra_3' => $ovenCode,
        ];

        Utils::save_log($this->logger, $log);

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'data' => [
                'hanging_run_id' => $hangingRunId,
                'oven_code' => $ovenCode,
                'drying_run' => $persistResult,
            ],
        ]);
    }
}