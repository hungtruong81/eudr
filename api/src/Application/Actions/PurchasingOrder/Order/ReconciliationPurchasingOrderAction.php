<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingOrder\Order;

use App\Application\Actions\PurchasingOrder\PurchasingOrderAction;
use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

final class ReconciliationPurchasingOrderAction extends PurchasingOrderAction
{
    protected function action(): Response
    {
        $userId = (int)($this->auth_data['user_id'] ?? 0);
        if ($userId <= 0) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }
        $scope = Utils::resolveScope($this->userRepository->getUserPermissions($userId), 'purchasing_order', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }
        $order = $this->purchasingOrderRepository->findOrderOfCodeWithPermission(
            trim((string)$this->resolveArg('code')),
            $userId,
            (string)$scope,
            (int)($this->auth_data['company_id'] ?? 0)
        );
        if ($order === null) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy phiếu thu mua');
        }

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => Utils::generateRandomString(25),
            'reconciliation' => $this->purchasingOrderRepository->getReconciliation($order->getId()),
        ]);
    }
}
