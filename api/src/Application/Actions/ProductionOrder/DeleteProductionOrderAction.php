<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionOrder;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;

class DeleteProductionOrderAction extends ProductionOrderAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);
        
        // Check authentication
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        $user_role = $this->userRepository->getUserRole($this->auth_data['user_id']);

        // Load all permissions once per action
        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        
        // Check permission to delete production orders
        $deleteScope = Utils::resolveScope($permissions, 'production_order', 'delete');
		if (empty($deleteScope)) {
			throw new HttpForbiddenException($this->request, "Không có quyền xóa lệnh sản xuất");
		}

        // Check permission to view production orders (for existence check)
        $viewScope = Utils::resolveScope($permissions, 'production_order', 'view');
        if (empty($viewScope)) {
            throw new HttpForbiddenException($this->request, "Không có quyền truy cập lệnh sản xuất");
        }

        $production_order_code = addslashes(trim((string)$this->resolveArg('code')));

        $production_order = $this->productionOrderRepository->findProductionOrderOfCodeWithPermission(
            $production_order_code,
            (int)$this->auth_data['user_id'],
			(string)$viewScope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($production_order)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy lệnh sản xuất");
        }

        // Delete production order
        $this->productionOrderRepository->deleteProductionOrderWithPermission(
            $production_order->getId(),
            $this->auth_data['user_id'],
            (int)$this->auth_data['user_id'],
			(string)$deleteScope,
            $this->auth_data['company_id'] ?? null
        );
        
        $action = 'delete';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'production_order',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$production_order->getId(),
        );
        
        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }

}
