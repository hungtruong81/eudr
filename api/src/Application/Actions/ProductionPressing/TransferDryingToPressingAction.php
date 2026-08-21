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

class TransferDryingToPressingAction extends ProductionPressingAction
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

        $ovenViewScope = Utils::resolveScope($permissions, 'production_oven', 'view');
        if (empty($ovenViewScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền xem bước sấy');
        }

        $pressingCreateScope = Utils::resolveScope($permissions, 'production_pallet', 'create');
        if (empty($pressingCreateScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền tạo lượt ép bành');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('drying_run_id', $formData['drying_run_id'] ?? null, 'required|integer|min:1');
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
            'drying_run_id' => 'integer',
            'notes' => 'string',
        ]);

        $dryingRunId = (int)$cleanData['drying_run_id'];
        $notes = trim((string)($cleanData['notes'] ?? ''));

        $dryingRun = $this->productionPressingRepository->findDryingRunOfIdWithPermission(
            $dryingRunId,
            $authUserId,
            (string)$ovenViewScope,
            $authCompanyId
        );
        if (empty($dryingRun)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy drying run');
        }

        if ((string)($dryingRun['status'] ?? '') !== 'completed') {
            throw new HttpBadRequestException($this->request, 'Drying run phải ở trạng thái completed để chuyển sang ép bành');
        }

        $persistResult = $this->productionPressingRepository->createPressingRunFromDrying([
            'drying_run_id' => $dryingRunId,
            'notes' => $notes !== '' ? $notes : null,
            'updated_by' => $authUserId,
        ]);

        if (empty($persistResult)) {
            throw new HttpBadRequestException($this->request, 'Tạo pressing run thất bại');
        }

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'production_pressing_run',
            'action' => 'transfer_drying_to_pressing',
            'user_id' => (string)$authUserId,
            'extra_1' => (string)$dryingRunId,
            'extra_2' => (string)$persistResult['pressing_run_id'],
        ];

        Utils::save_log($this->logger, $log);

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'data' => [
                'drying_run_id' => $dryingRunId,
                'pressing_run' => $persistResult,
            ],
        ]);
    }
}
