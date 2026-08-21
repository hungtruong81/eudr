<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionCuttingMachine;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class ViewCuttingRunAction extends ProductionCuttingMachineAction
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

        $cuttingRunId = (int)$this->resolveArg('cutting_run_id');
        if ($cuttingRunId <= 0) {
            throw new HttpBadRequestException($this->request, 'cutting_run_id không hợp lệ');
        }

        $detail = $this->productionCuttingMachineRepository->getCuttingRunDetailWithPermission(
            $cuttingRunId,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        if (empty($detail)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy cutting run');
        }

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'production_cutting_run',
            'action' => 'view_detail',
            'user_id' => (string)$this->auth_data['user_id'],
            'extra_1' => (string)$cuttingRunId,
        ];
        
        Utils::save_log($this->logger, $log);

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'data' => $detail,
        ]);
    }
}