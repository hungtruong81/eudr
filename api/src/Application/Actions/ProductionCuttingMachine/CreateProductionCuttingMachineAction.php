<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionCuttingMachine;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class CreateProductionCuttingMachineAction extends ProductionCuttingMachineAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $createScope = Utils::resolveScope($permissions, 'production_cutting_machine', 'create');
        if (empty($createScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $factoryViewScope = Utils::resolveScope($permissions, 'factory', 'view');
        if (empty($factoryViewScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập nhà máy');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('cutting_machine_code', $formData['cutting_machine_code'] ?? null, 'string|max:30');
        $validator->validate('factory_id', $formData['factory_id'] ?? null, 'required|integer|min:1');
        $validator->validate('cutting_machine_name', $formData['cutting_machine_name'] ?? null, 'required|string|max:100');
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
            'cutting_machine_code' => 'string',
            'factory_id' => 'integer',
            'cutting_machine_name' => 'string',
            'status' => 'string',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $factory_id = (int)$cleanData['factory_id'];
        $cutting_machine_name = trim((string)$cleanData['cutting_machine_name']);
        $status = trim((string)($cleanData['status'] ?? 'available'));
        if ($status === '') {
            $status = 'available';
        }

        $cutting_machine_code = trim((string)($cleanData['cutting_machine_code'] ?? ''));
        if ($cutting_machine_code === '') {
            $cutting_machine_code = $this->productionCuttingMachineRepository->generateCode();
        }

        $existingCuttingMachine = $this->productionCuttingMachineRepository->findProductionCuttingMachineOfCode($cutting_machine_code);
        if (!empty($existingCuttingMachine)) {
            $cutting_machine_code = $this->productionCuttingMachineRepository->generateCode();
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
            'cutting_machine_code' => $cutting_machine_code,
            'cutting_machine_name' => $cutting_machine_name,
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s', time()),
            'created_by' => (int)$this->auth_data['user_id'],
        ];

        $productionCuttingMachine = $this->productionCuttingMachineRepository->createProductionCuttingMachine($data_insert);
        if (empty($productionCuttingMachine)) {
            throw new HttpBadRequestException($this->request, 'Không thể tạo máy cắt mủ');
        }

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'production_cutting_machine',
            'action' => 'create',
            'user_id' => (string)$this->auth_data['user_id'],
            'extra_1' => (string)$productionCuttingMachine->getId(),
        ];
        Utils::save_log($this->logger, $log);

        $res_return = ['result' => 'success'];
        $res_return['trace_id'] = $trace_id;
        $res_return['production_cutting_machine'] = $productionCuttingMachine->jsonSerialize();

        return $this->respondWithData($res_return);
    }
}
