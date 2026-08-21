<?php

declare(strict_types=1);

namespace App\Application\Actions\VendorLand;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class UpdateVendorLandAction extends VendorLandAction
{
    protected function action(): Response
    {
        $traceId = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $userId = (int)$this->auth_data['user_id'];
        $permissions = $this->userRepository->getUserPermissions($userId);
        $updateScope = Utils::resolveScope($permissions, 'vendor_land', 'update');
        if (empty($updateScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $vendor = $this->vendorRepository->findVendorOfCodeWithPermission(
            trim((string)$this->resolveArg('vendor_code')),
            $userId,
            (string)$updateScope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($vendor)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy vendor');
        }

        $vendorLandId = (int)$this->resolveArg('vendor_land_id');

        if ($this->vendorLandRepository->findOne((int)$vendor->getId(), $vendorLandId) === null) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy vườn của vendor');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('status', $formData['status'] ?? null, 'in:active,inactive');
        $validator->validate('notes', $formData['notes'] ?? null, 'string|max:255');

        if ($validator->hasErrors() || (!array_key_exists('status', $formData) && !array_key_exists('notes', $formData))) {
            throw new HttpBadRequestException($this->request, 'Dữ liệu cập nhật không hợp lệ');
        }

        $updateData = $validator->sanitize($formData, [
            'status' => 'string',
            'notes' => 'string',
        ]);
        $updateData['updated_at'] = date('Y-m-d H:i:s');
        $updateData['updated_by'] = (int)$this->auth_data['user_id'];

        $record = $this->vendorLandRepository->update(
            (int)$vendor->getId(),
            $vendorLandId,
            $updateData
        );

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $traceId,
            'vendor_land' => $record,
        ]);
    }
}