<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionOven;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class ViewDryingRunAction extends ProductionOvenAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'production_oven', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $dryingRunId = (int)$this->resolveArg('drying_run_id');
        if ($dryingRunId <= 0) {
            throw new HttpBadRequestException($this->request, 'drying_run_id không hợp lệ');
        }

        $detail = $this->productionOvenRepository->getDryingRunDetailWithPermission(
            $dryingRunId,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        if (empty($detail)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy drying run');
        }

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'production_drying_run',
            'action' => 'view_detail',
            'user_id' => (string)$this->auth_data['user_id'],
            'extra_1' => (string)$dryingRunId,
        ];
        Utils::save_log($this->logger, $log);

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'data' => $detail,
        ]);
    }
}