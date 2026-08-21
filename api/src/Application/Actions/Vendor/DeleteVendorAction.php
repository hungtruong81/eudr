<?php

declare(strict_types=1);

namespace App\Application\Actions\Vendor;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class DeleteVendorAction extends VendorAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $deleteScope = Utils::resolveScope($permissions, 'vendor', 'delete');
        if (empty($deleteScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $code = addslashes(trim((string)$this->resolveArg('code')));
        $vendor = $this->vendorRepository->findVendorOfCodeWithPermission(
            $code,
            (int)$this->auth_data['user_id'],
            (string)$deleteScope,
            $this->auth_data['company_id'] ?? null
        );

        if (empty($vendor)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy vendor');
        }

        $this->vendorRepository->deleteVendorWithPermission(
            (int)$vendor->getId(),
            (int)$this->auth_data['user_id'],
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
