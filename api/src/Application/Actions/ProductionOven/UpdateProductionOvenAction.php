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

class UpdateProductionOvenAction extends ProductionOvenAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $updateScope = Utils::resolveScope($permissions, 'production_oven', 'update');
        if (empty($updateScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $viewScope = Utils::resolveScope($permissions, 'production_oven', 'view');
        if (empty($viewScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $factoryViewScope = Utils::resolveScope($permissions, 'factory', 'view');
        if (empty($factoryViewScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập nhà máy');
        }

        $oven_code_path = addslashes(trim((string)$this->resolveArg('code')));

        $productionOven = $this->productionOvenRepository->findProductionOvenOfCodeWithPermission(
            $oven_code_path,
            (int)$this->auth_data['user_id'],
            (string)$viewScope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($productionOven)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy lò sấy');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('factory_id', $formData['factory_id'] ?? null, 'required|integer|min:1');
        $validator->validate('oven_name', $formData['oven_name'] ?? null, 'required|string|max:100');
        $validator->validate('status', $formData['status'] ?? null, 'string|in:available,in_use,maintenance');

        if ($validator->hasErrors()) {
            $errorMessages = [];
            $errors = $validator->getErrors();
            if (is_array($errors)) {
                foreach ($errors as $field => $fieldErrors) {
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
            'factory_id' => 'integer',
            'oven_name' => 'string',
            'status' => 'string',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $factory_id = (int)$cleanData['factory_id'];
        $factory = $this->factoryRepository->findFactoryOfIdWithPermission(
            $factory_id,
            (int)$this->auth_data['user_id'],
            (string)$factoryViewScope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($factory)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy nhà máy');
        }

        $status = trim((string)($cleanData['status'] ?? $productionOven->getStatus()));
        if ($status === '') {
            $status = $productionOven->getStatus() ?? 'available';
        }

        $data_update = [
            'factory_id' => $factory_id,
            'oven_name' => trim((string)$cleanData['oven_name']),
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s', time()),
            'updated_by' => (int)$this->auth_data['user_id'],
        ];

        $productionOven = $this->productionOvenRepository->updateProductionOvenWithPermission(
            $productionOven->getId(),
            $data_update,
            (int)$this->auth_data['user_id'],
            (string)$updateScope,
            $this->auth_data['company_id'] ?? null
        );

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'production_oven',
            'action' => 'update',
            'user_id' => (string)$this->auth_data['user_id'],
            'extra_1' => (string)$productionOven->getId(),
        ];

        Utils::save_log($this->logger, $log);

        $res_return = ['result' => 'success'];
        $res_return['trace_id'] = $trace_id;
        $res_return['production_oven'] = $productionOven->jsonSerialize();

        return $this->respondWithData($res_return);
    }
}
