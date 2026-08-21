<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionGongCart;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class CreateProductionGongCartAction extends ProductionGongCartAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $createScope = Utils::resolveScope($permissions, 'production_gong_cart', 'create');
        if (empty($createScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $factoryViewScope = Utils::resolveScope($permissions, 'factory', 'view');
        if (empty($factoryViewScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập nhà máy');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('gong_cart_code', $formData['gong_cart_code'] ?? null, 'string|max:30');
        $validator->validate('factory_id', $formData['factory_id'] ?? null, 'required|integer|min:1');
        $validator->validate('gong_cart_name', $formData['gong_cart_name'] ?? null, 'required|string|max:100');
        $validator->validate('max_poles', $formData['max_poles'] ?? null, 'required|integer|min:1');
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
            'gong_cart_code' => 'string',
            'factory_id' => 'integer',
            'gong_cart_name' => 'string',
            'max_poles' => 'integer',
            'status' => 'string',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $factory_id = (int)$cleanData['factory_id'];
        $gong_cart_name = trim((string)$cleanData['gong_cart_name']);
        $max_poles = (int)$cleanData['max_poles'];
        $status = trim((string)($cleanData['status'] ?? 'available'));
        if ($status === '') {
            $status = 'available';
        }

        $gong_cart_code = trim((string)($cleanData['gong_cart_code'] ?? ''));
        if ($gong_cart_code === '') {
            $gong_cart_code = $this->productionGongCartRepository->generateCode();
        }

        $existingGongCart = $this->productionGongCartRepository->findProductionGongCartOfCode($gong_cart_code);
        if (!empty($existingGongCart)) {
            $gong_cart_code = $this->productionGongCartRepository->generateCode();
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
            'gong_cart_code' => $gong_cart_code,
            'gong_cart_name' => $gong_cart_name,
            'max_poles' => $max_poles,
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s', time()),
            'created_by' => (int)$this->auth_data['user_id'],
        ];

        $productionGongCart = $this->productionGongCartRepository->createProductionGongCart($data_insert);
        if (empty($productionGongCart)) {
            throw new HttpBadRequestException($this->request, 'Không thể tạo xe gòong');
        }

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'production_gong_cart',
            'action' => 'create',
            'user_id' => (string)$this->auth_data['user_id'],
            'extra_1' => (string)$productionGongCart->getId(),
        ];
        Utils::save_log($this->logger, $log);

        $res_return = ['result' => 'success'];
        $res_return['trace_id'] = $trace_id;
        $res_return['production_gong_cart'] = $productionGongCart->jsonSerialize();

        return $this->respondWithData($res_return);
    }
}
