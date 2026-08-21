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

class CreateProductionOvenAction extends ProductionOvenAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $createScope = Utils::resolveScope($permissions, 'production_oven', 'create');
        if (empty($createScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $factoryViewScope = Utils::resolveScope($permissions, 'factory', 'view');
        if (empty($factoryViewScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập nhà máy');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('oven_code', $formData['oven_code'] ?? null, 'string|max:30');
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
            'oven_code' => 'string',
            'factory_id' => 'integer',
            'oven_name' => 'string',
            'status' => 'string',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $factory_id = (int)$cleanData['factory_id'];
        $oven_name = trim((string)$cleanData['oven_name']);
        $status = trim((string)($cleanData['status'] ?? 'available'));
        if ($status === '') {
            $status = 'available';
        }

        $oven_code = trim((string)($cleanData['oven_code'] ?? ''));
        if ($oven_code === '') {
            $oven_code = $this->productionOvenRepository->generateCode();
        }

        $existingOven = $this->productionOvenRepository->findProductionOvenOfCode($oven_code);
        if (!empty($existingOven)) {
            $oven_code = $this->productionOvenRepository->generateCode();
        }

        $factory = $this->factoryRepository->findFactoryOfIdWithPermission(
            $factory_id,
            (int)$this->auth_data['user_id'],
            (string)$factoryViewScope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($factory)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy nhà máy');
        }

        $data_insert = [
            'company_id' => $this->auth_data['company_id'] ?? 0,
            'factory_id' => $factory_id,
            'oven_code' => $oven_code,
            'oven_name' => $oven_name,
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s', time()),
            'created_by' => (int)$this->auth_data['user_id'],
        ];

        $productionOven = $this->productionOvenRepository->createProductionOven($data_insert);
        if (empty($productionOven)) {
            throw new HttpBadRequestException($this->request, 'Không thể tạo lò sấy');
        }

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'production_oven',
            'action' => 'create',
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
