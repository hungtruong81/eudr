<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingOrder\Order;

use App\Application\Actions\PurchasingOrder\PurchasingOrderAction;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class DeletePurchasingOrderAction extends PurchasingOrderAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $userId = (int)$this->auth_data['user_id'];
        $companyId = (int)($this->auth_data['company_id'] ?? 0);
        $permissions = $this->userRepository->getUserPermissions($userId);
        $scope = Utils::resolveScope($permissions, 'purchasing_order', 'delete');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $orderCode = addslashes(trim((string)$this->resolveArg('code')));
        $order = $this->purchasingOrderRepository->findOrderOfCodeWithPermission(
            $orderCode,
            $userId,
            (string)$scope,
            $companyId
        );
        if (empty($order)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy phiếu thu mua');
        }

        if ($order->getStatus() !== 'draft') {
            throw new HttpBadRequestException($this->request, 'Chỉ được xóa phiếu ở trạng thái draft');
        }

        $deleted = $this->purchasingOrderRepository->deleteDraftOrderWithPermission(
            (int)$order->getId(),
            $userId,
            $userId,
            (string)$scope,
            $companyId
        );
        if (!$deleted) {
            throw new HttpBadRequestException($this->request, 'Không thể xóa phiếu thu mua');
        }

        Utils::save_log($this->logger, [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'purchasing_order',
            'action' => 'delete',
            'user_id' => (string)$userId,
            'extra_1' => (string)$order->getId(),
        ]);

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
        ]);
    }
}