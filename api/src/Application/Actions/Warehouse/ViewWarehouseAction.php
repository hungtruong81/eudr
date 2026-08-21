<?php

declare(strict_types=1);

namespace App\Application\Actions\Warehouse;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class ViewWarehouseAction extends WarehouseAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'warehouse', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $warehouse_code = addslashes(trim((string)$this->resolveArg('code')));

        $warehouse = $this->warehouseRepository->findWarehouseOfCodeWithPermission(
            $warehouse_code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        if (empty($warehouse)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy kho');
        }

        Utils::save_log($this->logger, [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'warehouse',
            'action' => 'view',
            'user_id' => (string)$this->auth_data['user_id'],
            'extra_1' => (string)$warehouse->getId(),
        ]);

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'data' => $warehouse->jsonSerialize(),
        ]);
    }
}
