<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionOrder;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class ViewSetupProductionOrderAction extends ProductionOrderAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);

        $scope = Utils::resolveScope($permissions, 'production_order', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Không có quyền truy cập lệnh sản xuất');
        }

        $production_order_code = addslashes(trim((string)$this->resolveArg('code')));

        $production_order = $this->productionOrderRepository->findProductionOrderOfCodeWithPermission(
            $production_order_code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($production_order)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy lệnh sản xuất');
        }

        $setupData = $this->productionOrderRepository->getFullSetupOfProductionOrder((int)$production_order->getId());

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'production_order',
            'action' => 'view_setup',
            'user_id' => (string)$this->auth_data['user_id'],
            'extra_1' => (string)$production_order->getId(),
        ];
        
        Utils::save_log($this->logger, $log);

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'production_order' => $production_order->jsonSerialize(),
            'setup' => $setupData,
        ]);
    }
}
