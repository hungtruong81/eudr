<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingSubTank;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class UpdatePurchasingSubTankAction extends PurchasingSubTankAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'purchasing_sub_tank', 'update');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        // $factoryScope = Utils::resolveScope($permissions, 'factory', 'view');
        // if (empty($factoryScope)) {
        //     throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập nhà máy');
        // }

        $code = addslashes(trim((string)$this->resolveArg('code')));
        $subTank = $this->purchasingSubTankRepository->findPurchasingSubTankOfCodeWithPermission(
            $code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        if (empty($subTank)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy bình con');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);
        $validator->validate('sub_tank_name', $formData['sub_tank_name'] ?? null, 'required|string');
        $validator->validate('rubber_type', $formData['rubber_type'] ?? null, 'required|in:latex,cup_lump,scrap_rubber,mixed');
        $validator->validate('capacity_kg', $formData['capacity_kg'] ?? null, 'required|numeric|min:0');
        $validator->validate('location', $formData['location'] ?? null, 'string');
        $validator->validate('status', $formData['status'] ?? null, 'required|in:idle,in_use,sealed,transporting,cleaning,damaged,inactive,maintenance,full');
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
            'sub_tank_name' => 'string',
            'factory_id' => 'integer',
            'rubber_type' => 'string',
            'capacity_kg' => 'float',
            'location' => 'string',
            'status' => 'string',
            'notes' => 'string',
        ]);

        $updated = $this->purchasingSubTankRepository->updatePurchasingSubTankWithPermission(
            (int)$subTank->getId(),
            [
                'sub_tank_name' => $cleanData['sub_tank_name'],
                'rubber_type' => $cleanData['rubber_type'],
                'capacity_kg' => (float)$cleanData['capacity_kg'],
                'location' => $cleanData['location'] ?? '',
                'status' => $cleanData['status'],
                'notes' => $cleanData['notes'] ?? '',
                'updated_at' => date('Y-m-d H:i:s', time()),
                'updated_by' => $this->auth_data['user_id'],
            ],
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        Utils::save_log($this->logger, [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'purchasing_sub_tank',
            'action' => 'update',
            'user_id' => (string)$this->auth_data['user_id'],
            'extra_1' => (string)$updated->getId(),
        ]);

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'purchasing_sub_tank' => $updated->jsonSerialize(),
        ]);
    }
}
