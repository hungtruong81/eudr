<?php

declare(strict_types=1);

namespace App\Application\Actions\Vendor;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class UpdateVendorAction extends VendorAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $updateScope = Utils::resolveScope($permissions, 'vendor', 'update');
        if (empty($updateScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $code = addslashes(trim((string)$this->resolveArg('code')));
        $vendor = $this->vendorRepository->findVendorOfCodeWithPermission(
            $code,
            (int)$this->auth_data['user_id'],
            (string)$updateScope,
            $this->auth_data['company_id'] ?? null
        );

        if (empty($vendor)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy vendor');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);
        $vendorType = (string)($formData['vendor_type'] ?? $vendor->getType());

        $validator->validate('vendor_name', $formData['vendor_name'] ?? null, 'required|string|max:255');
        $validator->validate('vendor_type', $vendorType, 'in:individual,company');
        $validator->validate('identity_number', $formData['identity_number'] ?? null, 'string|max:50');
        $validator->validate('contact_name', $formData['contact_name'] ?? null, 'string|max:150');
        $validator->validate('contact_phone', $formData['contact_phone'] ?? null, 'string|max:20');
        $validator->validate('tax_code', $formData['tax_code'] ?? null, 'string|max:50');
        $validator->validate('address', $formData['address'] ?? null, 'string|max:255');
        $validator->validate('province_id', $formData['province_id'] ?? null, 'integer|min:1');
        $validator->validate('status', $formData['status'] ?? null, 'required|in:active,inactive');
        $validator->validate('notes', $formData['notes'] ?? null, 'string|max:255');

        if ($vendorType === 'individual' && trim((string)($formData['identity_number'] ?? '')) === '') {
            throw new HttpBadRequestException($this->request, 'Số định danh cá nhân là bắt buộc với vendor cá nhân');
        }

        if ($validator->hasErrors()) {
            $errors = [];
            foreach ((array)$validator->getErrors() as $fieldErrors) {
                $errors[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errors));
        }

        $cleanData = $validator->sanitize($formData, [
            'vendor_name' => 'string',
            'vendor_type' => 'string',
            'identity_number' => 'string',
            'contact_name' => 'string',
            'contact_phone' => 'string',
            'tax_code' => 'string',
            'address' => 'string',
            'province_id' => 'integer',
            'status' => 'string',
            'notes' => 'string',
        ]);

        $identityNumber = trim((string)($cleanData['identity_number'] ?? '')) ?: null;
        $taxCode = trim((string)($cleanData['tax_code'] ?? '')) ?: null;
        if ($identityNumber !== null && $this->vendorRepository->identifierExists('identity_number', $identityNumber, (int)$vendor->getId())) {
            throw new HttpBadRequestException($this->request, 'Số định danh cá nhân đã tồn tại');
        }
        if ($taxCode !== null && $this->vendorRepository->identifierExists('tax_code', $taxCode, (int)$vendor->getId())) {
            throw new HttpBadRequestException($this->request, 'Mã số thuế đã tồn tại');
        }

        $updated = $this->vendorRepository->updateVendorWithPermission(
            (int)$vendor->getId(),
            [
                'vendor_name' => $cleanData['vendor_name'],
                'vendor_type' => $vendorType,
                'identity_number' => $identityNumber,
                'contact_name' => $cleanData['contact_name'] ?? null,
                'contact_phone' => $cleanData['contact_phone'] ?? null,
                'tax_code' => $taxCode,
                'address' => $cleanData['address'] ?? null,
                'province_id' => (int)($cleanData['province_id'] ?? 0),
                'status' => $cleanData['status'],
                'notes' => $cleanData['notes'] ?? null,
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => (int)$this->auth_data['user_id'],
            ],
            (int)$this->auth_data['user_id'],
            (string)$updateScope,
            $this->auth_data['company_id'] ?? null
        );

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'vendor' => $updated->jsonSerialize(),
        ]);
    }
}
