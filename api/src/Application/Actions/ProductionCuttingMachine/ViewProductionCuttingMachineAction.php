<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionCuttingMachine;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class ViewProductionCuttingMachineAction extends ProductionCuttingMachineAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'production_cutting_machine', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $cutting_machine_code = addslashes(trim((string)$this->resolveArg('code')));

        $productionCuttingMachine = $this->productionCuttingMachineRepository->findProductionCuttingMachineOfCodeWithPermission(
            $cutting_machine_code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        if (empty($productionCuttingMachine)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy máy cắt mủ');
        }

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'production_cutting_machine',
            'action' => 'view',
            'user_id' => (string)$this->auth_data['user_id'],
            'extra_1' => (string)$productionCuttingMachine->getId(),
        ];
        Utils::save_log($this->logger, $log);

        $res_return = ['result' => 'success'];
        $res_return['trace_id'] = $trace_id;
        $res_return['data'] = $productionCuttingMachine->jsonSerialize();

        return $this->respondWithData($res_return);
    }
}
