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

class RejectSetupChangeRequestProductionOrderAction extends ProductionOrderAction
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
            throw new HttpForbiddenException($this->request, 'Thiếu quyền từ chối yêu cầu thay đổi thiết lập lệnh sản xuất');
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

        $changeRequestId = (int)$this->resolveArg('change_request_id');
        if ($changeRequestId <= 0) {
            throw new HttpBadRequestException($this->request, 'ID yêu cầu thay đổi không hợp lệ');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);
        $validator->validate('approval_notes', $formData['approval_notes'] ?? null, 'string');

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
            'approval_notes' => 'string',
        ]);

        try {
            $changeRequest = $this->productionOrderRepository->rejectSetupChangeRequest(
                $changeRequestId,
                (int)$this->auth_data['user_id'],
                $cleanData['approval_notes'] ?? null
            );

            // Verify change request belongs to this order
            if (!isset($changeRequest['production_order_id']) || (int)$changeRequest['production_order_id'] !== (int)$order->getId()) {
                throw new HttpNotFoundException($this->request, 'Yêu cầu thay đổi không thuộc về lệnh sản xuất này');
            }
        } catch (\Exception $e) {
            if ($e instanceof HttpNotFoundException) {
                throw $e;
            }
            throw new HttpNotFoundException($this->request, $e->getMessage());
        }

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'production_order',
            'action' => 'reject_setup_change_request',
            'user_id' => (string)$this->auth_data['user_id'],
            'extra_1' => (string)$order->getId(),
            'extra_2' => (string)$changeRequestId,
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
