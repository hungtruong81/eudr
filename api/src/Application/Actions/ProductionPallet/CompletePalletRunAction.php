<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionPallet;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class CompletePalletRunAction extends ProductionPalletAction
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

        $palletViewScope = Utils::resolveScope($permissions, 'production_pallet', 'view');
        if (empty($palletViewScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền xem đợt pallet');
        }

        $palletUpdateScope = Utils::resolveScope($permissions, 'production_pallet', 'update');
        if (empty($palletUpdateScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền hoàn tất đợt pallet');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('pallet_run_id', $formData['pallet_run_id'] ?? null, 'required|integer|min:1');
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
            'pallet_run_id' => 'integer',
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

        $palletRunId = (int)$cleanData['pallet_run_id'];

        $run = $this->productionPalletRepository->findPalletRunOfIdWithPermission(
            $palletRunId,
            $authUserId,
            (string)$palletViewScope,
            $authCompanyId
        );
        if (empty($run)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy pallet run');
        }

        $completed = $this->productionPalletRepository->completePalletRun([
            'pallet_run_id' => $palletRunId,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'updated_by' => $authUserId,
        ]);

        if (empty($completed)) {
            throw new HttpBadRequestException($this->request, 'Hoàn tất pallet run thất bại');
        }

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'data' => $completed,
        ]);
    }
}
