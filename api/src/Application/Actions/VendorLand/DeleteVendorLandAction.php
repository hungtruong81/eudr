<?php

declare(strict_types=1);

namespace App\Application\Actions\VendorLand;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class DeleteVendorLandAction extends VendorLandAction
{
    protected function action(): Response
    {
        $traceId = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $userId = (int)$this->auth_data['user_id'];
        $permissions = $this->userRepository->getUserPermissions($userId);
        $deleteScope = Utils::resolveScope($permissions, 'vendor_land', 'delete');
        if (empty($deleteScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $vendor = $this->vendorRepository->findVendorOfCodeWithPermission(
            trim((string)$this->resolveArg('vendor_code')),
            $userId,
            (string)$deleteScope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($vendor)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy vendor');
        }

        $vendorLandId = (int)$this->resolveArg('vendor_land_id');

        if ($this->vendorLandRepository->findOne((int)$vendor->getId(), $vendorLandId) === null) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy vườn của vendor');
        }

        $this->vendorLandRepository->delete(
            (int)$vendor->getId(),
            $vendorLandId,
            (int)$this->auth_data['user_id']
        );

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $traceId,
        ]);
    }
}