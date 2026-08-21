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

class CreateVendorLandAction extends VendorLandAction
{
    protected function action(): Response
    {
        $traceId = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $userId = (int)$this->auth_data['user_id'];
        $permissions = $this->userRepository->getUserPermissions($userId);
        $createScope = Utils::resolveScope($permissions, 'vendor_land', 'create');
        if (empty($createScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $vendor = $this->vendorRepository->findVendorOfCodeWithPermission(
            trim((string)$this->resolveArg('vendor_code')),
            $userId,
            (string)$createScope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($vendor)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy vendor');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('plot_id', $formData['plot_id'] ?? null, 'required|integer|min:1');
        $validator->validate('status', $formData['status'] ?? null, 'in:active,inactive');
        $validator->validate('notes', $formData['notes'] ?? null, 'string|max:255');

        if ($validator->hasErrors()) {
            throw new HttpBadRequestException($this->request, 'Dữ liệu vườn không hợp lệ');
        }

        $cleanData = $validator->sanitize($formData, [
            'plot_id' => 'integer',
            'status' => 'string',
            'notes' => 'string',
        ]);
        $plotId = (int)$cleanData['plot_id'];

        if ($this->landRepository->findLandOfId($plotId) === null) {
            throw new HttpBadRequestException($this->request, 'Không tìm thấy vườn');
        }

        if (($cleanData['status'] ?? 'active') === 'active'
            && $this->vendorLandRepository->activeRelationExists((int)$vendor->getId(), $plotId)) {
            throw new HttpBadRequestException($this->request, 'Vườn đã được gán cho vendor');
        }

        $record = $this->vendorLandRepository->create([
            'vendor_id' => (int)$vendor->getId(),
            'plot_id' => $plotId,
            'status' => $cleanData['status'] ?? 'active',
            'notes' => $cleanData['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => (int)$this->auth_data['user_id'],
        ]);

        if ($record === null) {
            throw new HttpBadRequestException($this->request, 'Không thể gán vườn cho vendor');
        }

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $traceId,
            'vendor_land' => $record,
        ]);
    }
}