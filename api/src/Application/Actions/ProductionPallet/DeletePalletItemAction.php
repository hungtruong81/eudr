<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionPallet;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

class DeletePalletItemAction extends ProductionPalletAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $authUserId = (int)$this->auth_data['user_id'];
        $permissions = $this->userRepository->getUserPermissions($authUserId);

        $scope = Utils::resolveScope($permissions, 'production_pallet', 'update');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền xóa pallet item');
        }

        $palletItemId = (int)$this->resolveArg('pallet_item_id');
        if ($palletItemId <= 0) {
            throw new HttpBadRequestException($this->request, 'pallet_item_id không hợp lệ');
        }

        $deleted = $this->productionPalletRepository->deletePalletItem([
            'pallet_item_id' => $palletItemId,
            'updated_by' => $authUserId,
        ]);

        if (empty($deleted)) {
            throw new HttpBadRequestException($this->request, 'Xóa pallet item thất bại');
        }

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'data' => $deleted,
        ]);
    }
}
