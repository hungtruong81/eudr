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

class CreateProductionChannelAction extends ProductionChannelAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $createScope = Utils::resolveScope($permissions, 'production_channel', 'create');
        if (empty($createScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $factoryViewScope = Utils::resolveScope($permissions, 'factory', 'view');
        if (empty($factoryViewScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập nhà máy');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('channel_code', $formData['channel_code'] ?? null, 'string|max:30');
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
            'channel_code' => 'string',
            'factory_id' => 'integer',
            'channel_name' => 'string',
            'capacity_kg' => 'float',
            'status' => 'string',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $factory_id = (int)$cleanData['factory_id'];
        $channel_name = trim((string)$cleanData['channel_name']);
        $capacity_kg = (float)$cleanData['capacity_kg'];
        $status = trim((string)($cleanData['status'] ?? 'available'));
        if ($status === '') {
            $status = 'available';
        }

        $channel_code = trim((string)($cleanData['channel_code'] ?? ''));
        if ($channel_code === '') {
            $channel_code = $this->productionChannelRepository->generateCode();
        }

        $existingChannel = $this->productionChannelRepository->findProductionChannelOfCode($channel_code);
        if (!empty($existingChannel)) {
            $channel_code = $this->productionChannelRepository->generateCode();
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
            'channel_code' => $channel_code,
            'channel_name' => $channel_name,
            'capacity_kg' => $capacity_kg,
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s', time()),
            'created_by' => (int)$this->auth_data['user_id'],
        ];

        $productionChannel = $this->productionChannelRepository->createProductionChannel($data_insert);
        if (empty($productionChannel)) {
            throw new HttpBadRequestException($this->request, 'Không thể tạo mương chứa mủ');
        }

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'production_channel',
            'action' => 'create',
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
