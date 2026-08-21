<?php

declare(strict_types=1);

namespace App\Application\Actions\Pallet;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class ViewPalletAction extends PalletAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'pallet', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $code = addslashes(trim((string)$this->resolveArg('code')));
        $pallet = $this->palletRepository->findPalletOfCodeWithPermission(
            $code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        if (empty($pallet)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy pallet');
        }

        $data = $pallet->jsonSerialize();
        $data['items'] = $this->palletRepository->listPalletItems((int)$pallet->getId());

        return $this->respondWithData([
            'result' => 'success',
            'data' => $data,
            'trace_id' => $trace_id,
        ]);
    }
}
