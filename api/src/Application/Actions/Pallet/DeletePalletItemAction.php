<?php

declare(strict_types=1);

namespace App\Application\Actions\Pallet;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class DeletePalletItemAction extends PalletAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'pallet', 'update');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $code = addslashes(trim((string)$this->resolveArg('code')));
        $pallet_item_id = (int)$this->resolveArg('pallet_item_id');
        if ($pallet_item_id <= 0) {
            throw new HttpBadRequestException($this->request, 'pallet_item_id không hợp lệ');
        }

        $pallet = $this->palletRepository->findPalletOfCodeWithPermission(
            $code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($pallet)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy pallet');
        }

        if ((string)$pallet->getStatus() !== 'empty') {
            throw new HttpBadRequestException($this->request, 'Chỉ xóa bành khi pallet ở trạng thái empty');
        }

        $this->palletRepository->removePalletItem((int)$pallet->getId(), $pallet_item_id);
        $updatedPallet = $this->palletRepository->findPalletOfId((int)$pallet->getId());

        return $this->respondWithData([
            'result' => 'success',
            'data' => [
                'pallet' => $updatedPallet ? $updatedPallet->jsonSerialize() : null,
                'items' => $this->palletRepository->listPalletItems((int)$pallet->getId()),
            ],
            'trace_id' => $trace_id,
        ]);
    }
}
