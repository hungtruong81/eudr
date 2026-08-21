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

class CreatePalletWithBalesAction extends ProductionPalletAction
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

        $palletCreateScope = Utils::resolveScope($permissions, 'production_pallet', 'create');
        if (empty($palletCreateScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền tạo pallet');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('pallet_run_id', $formData['pallet_run_id'] ?? null, 'required|integer|min:1');
        $validator->validate('pallet_no', $formData['pallet_no'] ?? null, 'string|max:30');
        $validator->validate('warehouse_id', $formData['warehouse_id'] ?? null, 'integer|min:1');
        $validator->validate('bale_ids', $formData['bale_ids'] ?? null, 'required|array');
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
            'pallet_run_id' => 'integer',
            'pallet_no' => 'string',
            'warehouse_id' => 'integer',
            'notes' => 'string',
        ]);

        $palletRunId = (int)$cleanData['pallet_run_id'];
        $baleIds = $formData['bale_ids'] ?? [];
        if (!is_array($baleIds) || count($baleIds) === 0) {
            throw new HttpBadRequestException($this->request, 'bale_ids không hợp lệ');
        }

        $run = $this->productionPalletRepository->findPalletRunOfIdWithPermission(
            $palletRunId,
            $authUserId,
            (string)$palletViewScope,
            $authCompanyId
        );
        if (empty($run)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy pallet run');
        }

        $created = $this->productionPalletRepository->createPalletWithBales([
            'pallet_run_id' => $palletRunId,
            'pallet_no' => trim((string)($cleanData['pallet_no'] ?? '')),
            'warehouse_id' => $cleanData['warehouse_id'] ?? null,
            'bale_ids' => $baleIds,
            'notes' => isset($cleanData['notes']) ? trim((string)$cleanData['notes']) : null,
            'updated_by' => $authUserId,
        ]);

        if (empty($created)) {
            throw new HttpBadRequestException($this->request, 'Tạo pallet hoặc gán bales thất bại');
        }

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'data' => $created,
        ]);
    }
}
