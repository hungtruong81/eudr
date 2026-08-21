<?php

declare(strict_types=1);

namespace App\Application\Actions\Pallet;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class DeletePalletAction extends PalletAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $deleteScope = Utils::resolveScope($permissions, 'pallet', 'delete');
        if (empty($deleteScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $viewScope = Utils::resolveScope($permissions, 'pallet', 'view');
        if (empty($viewScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $code = addslashes(trim((string)$this->resolveArg('code')));
        $pallet = $this->palletRepository->findPalletOfCodeWithPermission(
            $code,
            (int)$this->auth_data['user_id'],
            (string)$viewScope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($pallet)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy pallet');
        }

        if (!in_array((string)$pallet->getStatus(), ['empty', 'cancelled'], true)) {
            throw new HttpBadRequestException($this->request, 'Chỉ xóa pallet ở trạng thái empty hoặc cancelled');
        }

        $this->palletRepository->deletePalletWithPermission(
            (int)$pallet->getId(),
            (int)$this->auth_data['user_id'],
            (string)$deleteScope,
            $this->auth_data['company_id'] ?? null
        );

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
        ]);
    }
}
