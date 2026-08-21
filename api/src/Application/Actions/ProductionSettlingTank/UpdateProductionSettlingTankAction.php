<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionSettlingTank;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class UpdateProductionSettlingTankAction extends ProductionSettlingTankAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'raw_material_tank', 'update'); // production_settling_tank
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $factoryScope = Utils::resolveScope($permissions, 'factory', 'view');
        if (empty($factoryScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập nhà máy');
        }

        $code = addslashes(trim((string)$this->resolveArg('code')));

        $tank = $this->productionSettlingTankRepository->findProductionSettlingTankOfCodeWithPermission(
            $code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($tank)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy bồn lắng đọng tạm');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('settling_tank_name', $formData['settling_tank_name'] ?? null, 'required|string');
        $validator->validate('factory_id', $formData['factory_id'] ?? null, 'required|integer');
        $validator->validate('capacity_kg', $formData['capacity_kg'] ?? null, 'required|numeric|min:0');
        $validator->validate('status', $formData['status'] ?? null, 'required|in:available,in_use,cleaning,blocked');

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

        $sanitizeRules = [
            'settling_tank_name' => 'string',
            'factory_id' => 'integer',
            'capacity_kg' => 'float',
            'status' => 'string',
        ];
        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $factory = $this->factoryRepository->findFactoryOfIdWithPermission(
            (int)$cleanData['factory_id'],
            (int)$this->auth_data['user_id'],
            (string)$factoryScope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($factory)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy nhà máy');
        }

        $data_update = [
            'settling_tank_name' => $cleanData['settling_tank_name'],
            'factory_id' => (int)$cleanData['factory_id'],
            'capacity_kg' => (float)$cleanData['capacity_kg'],
            'status' => $cleanData['status'],
            'updated_at' => date('Y-m-d H:i:s', time()),
            'updated_by' => $this->auth_data['user_id'],
        ];

        $tank = $this->productionSettlingTankRepository->updateProductionSettlingTankWithPermission(
            (int)$tank->getId(),
            $data_update,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'production_settling_tank',
            'action' => 'update',
            'user_id' => (string)$this->auth_data['user_id'],
            'extra_1' => (string)$tank->getId(),
        ];
        
        Utils::save_log($this->logger, $log);

        $res_return = ['result' => 'success'];
        $res_return['trace_id'] = $trace_id;
        $res_return['production_settling_tank'] = $tank->jsonSerialize();

        return $this->respondWithData($res_return);
    }
}
