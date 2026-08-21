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

class TransferPressingToPalletAction extends ProductionPalletAction
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

        //$pressingViewScope = Utils::resolveScope($permissions, 'production_pressing', 'view');
        $pressingViewScope = Utils::resolveScope($permissions, 'production_pallet', 'view');
        if (empty($pressingViewScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền xem bước ép bành');
        }

        $palletCreateScope = Utils::resolveScope($permissions, 'production_pallet', 'create');
        if (empty($palletCreateScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền tạo đợt pallet');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('pressing_run_id', $formData['pressing_run_id'] ?? null, 'required|integer|min:1');
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
            'pressing_run_id' => 'integer',
            'notes' => 'string',
        ]);

        $pressingRunId = (int)$cleanData['pressing_run_id'];
        $notes = trim((string)($cleanData['notes'] ?? ''));

        $pressingRun = $this->productionPalletRepository->findPressingRunOfIdWithPermission(
            $pressingRunId,
            $authUserId,
            (string)$pressingViewScope,
            $authCompanyId
        );
        if (empty($pressingRun)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy pressing run');
        }

        if ((string)($pressingRun['status'] ?? '') !== 'completed') {
            throw new HttpBadRequestException($this->request, 'Pressing run phải completed để chuyển qua đóng pallet');
        }

        $created = $this->productionPalletRepository->createPalletRunFromPressing([
            'pressing_run_id' => $pressingRunId,
            'notes' => $notes !== '' ? $notes : null,
            'updated_by' => $authUserId,
        ]);

        if (empty($created)) {
            throw new HttpBadRequestException($this->request, 'Tạo pallet run thất bại');
        }

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'data' => $created,
        ]);
    }
}
