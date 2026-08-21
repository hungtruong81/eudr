<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionChannel;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class UpdateProductionChannelAction extends ProductionChannelAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $updateScope = Utils::resolveScope($permissions, 'production_channel', 'update');
        if (empty($updateScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $viewScope = Utils::resolveScope($permissions, 'production_channel', 'view');
        if (empty($viewScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $factoryViewScope = Utils::resolveScope($permissions, 'factory', 'view');
        if (empty($factoryViewScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập nhà máy');
        }

        $channel_code_path = addslashes(trim((string)$this->resolveArg('code')));

        $productionChannel = $this->productionChannelRepository->findProductionChannelOfCodeWithPermission(
            $channel_code_path,
            (int)$this->auth_data['user_id'],
            (string)$viewScope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($productionChannel)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy mương chứa mủ');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('factory_id', $formData['factory_id'] ?? null, 'required|integer|min:1');
        $validator->validate('channel_name', $formData['channel_name'] ?? null, 'required|string|max:100');
        $validator->validate('capacity_kg', $formData['capacity_kg'] ?? null, 'required|numeric|min:0');
        $validator->validate('status', $formData['status'] ?? null, 'string|in:available,in_use,cleaning');

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
            'channel_name' => 'string',
            'capacity_kg' => 'float',
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

        $status = trim((string)($cleanData['status'] ?? $productionChannel->getStatus()));
        if ($status === '') {
            $status = $productionChannel->getStatus() ?? 'available';
        }

        $data_update = [
            'factory_id' => $factory_id,
            'channel_name' => trim((string)$cleanData['channel_name']),
            'capacity_kg' => (float)$cleanData['capacity_kg'],
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s', time()),
            'updated_by' => (int)$this->auth_data['user_id'],
        ];

        $productionChannel = $this->productionChannelRepository->updateProductionChannelWithPermission(
            $productionChannel->getId(),
            $data_update,
            (int)$this->auth_data['user_id'],
            (string)$updateScope,
            $this->auth_data['company_id'] ?? null
        );

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'production_channel',
            'action' => 'update',
            'user_id' => (string)$this->auth_data['user_id'],
            'extra_1' => (string)$productionChannel->getId(),
        ];
        
        Utils::save_log($this->logger, $log);

        $res_return = ['result' => 'success'];
        $res_return['trace_id'] = $trace_id;
        $res_return['production_channel'] = $productionChannel->jsonSerialize();

        return $this->respondWithData($res_return);
    }
}
