<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionPallet;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class ViewPalletAction extends ProductionPalletAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'production_pallet', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $palletCode = addslashes(trim((string)$this->resolveArg('code')));

        $detail = $this->productionPalletRepository->getPalletDetailByCodeWithPermission(
            $palletCode,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        if (empty($detail)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy pallet');
        }

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'data' => $detail,
        ]);
    }
}
