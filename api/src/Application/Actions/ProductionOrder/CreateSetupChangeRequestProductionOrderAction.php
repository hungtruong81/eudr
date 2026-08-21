<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionOrder;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class CreateSetupChangeRequestProductionOrderAction extends ProductionOrderAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $orderScope = Utils::resolveScope($permissions, 'production_order', 'update');
        if (empty($orderScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền yêu cầu thay đổi thiết lập lệnh sản xuất');
        }

        $code = addslashes(trim((string)$this->resolveArg('code')));
        $order = $this->productionOrderRepository->findProductionOrderOfCodeWithPermission(
            $code,
            (int)$this->auth_data['user_id'],
            (string)$orderScope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($order)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy lệnh sản xuất');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);
        $validator->validate('change_type', $formData['change_type'] ?? null, 'required|string|in:raw_tank,settling_tank,channel,cutting_machine,roller,hanging,drying,pressing,pallet');
        $validator->validate('change_description', $formData['change_description'] ?? null, 'required|string|max:255');
        $validator->validate('old_value', $formData['old_value'] ?? null, 'array');
        $validator->validate('new_value', $formData['new_value'] ?? null, 'array');
        $validator->validate('reason', $formData['reason'] ?? null, 'string');

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
            'change_type' => 'string',
            'change_description' => 'string',
            'reason' => 'string',
        ]);

        $oldValue = $formData['old_value'] ?? null;
        $newValue = $formData['new_value'] ?? null;
        if ($oldValue !== null && !is_array($oldValue)) {
            throw new HttpBadRequestException($this->request, 'old_value phải là mảng JSON hợp lệ');
        }
        if ($newValue !== null && !is_array($newValue)) {
            throw new HttpBadRequestException($this->request, 'new_value phải là mảng JSON hợp lệ');
        }
        if (is_array($oldValue)) {
            foreach ($oldValue as $item) {
                if (!is_array($item)) {
                    throw new HttpBadRequestException($this->request, 'old_value phải là mảng object');
                }
            }
        }
        if (is_array($newValue)) {
            foreach ($newValue as $item) {
                if (!is_array($item)) {
                    throw new HttpBadRequestException($this->request, 'new_value phải là mảng object');
                }
            }
        }

        $changeRequest = $this->productionOrderRepository->createSetupChangeRequest(
            (int)$order->getId(),
            (string)$cleanData['change_type'],
            (string)$cleanData['change_description'],
            $oldValue,
            $newValue,
            $cleanData['reason'] ?? null,
            (int)$this->auth_data['user_id'],
            (int)($this->auth_data['company_id'] ?? 0),
            (int)$order->getFactoryId()
        );

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'production_order',
            'action' => 'create_setup_change_request',
            'user_id' => (string)$this->auth_data['user_id'],
            'extra_1' => (string)$order->getId(),
            'extra_2' => (string)$cleanData['change_type'],
        ];
        Utils::save_log($this->logger, $log);

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'production_order_id' => $order->getId(),
            'change_request' => $changeRequest,
        ]);
    }
}
