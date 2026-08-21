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

class ListVendorLandAction extends VendorLandAction
{
    protected function action(): Response
    {
        $traceId = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $userId = (int)$this->auth_data['user_id'];
        $permissions = $this->userRepository->getUserPermissions($userId);
        $viewScope = Utils::resolveScope($permissions, 'vendor_land', 'view');
        if (empty($viewScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $vendorId = filter_var($this->resolveArg('vendor_id'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($vendorId === false) {
            throw new HttpBadRequestException($this->request, 'vendor_id không hợp lệ');
        }

        $vendor = $this->vendorRepository->findVendorOfIdWithPermission(
            $vendorId,
            $userId,
            (string)$viewScope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($vendor)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy vendor');
        }

        $formData = $this->request->getQueryParams();
        $validator = new Validator($this->request);

        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('status', $formData['status'] ?? null, 'in:active,inactive,all');
        $validator->validate('search', $formData['search'] ?? null, 'string');

        if ($validator->hasErrors()) {
            $errors = [];
            foreach ((array)$validator->getErrors() as $fieldErrors) {
                $errors[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
            }

            throw new HttpBadRequestException($this->request, implode('; ', $errors));
        }

        $cleanData = $validator->sanitize($formData, [
            'page' => 'integer',
            'limit' => 'integer',
            'status' => 'string',
            'search' => 'string',
        ]);

        $data = $this->vendorLandRepository->findAll((int)$vendor->getId(), [
            'page' => $cleanData['page'],
            'page_limit' => $cleanData['limit'],
            'status' => $cleanData['status'] ?? 'all',
            'search' => $cleanData['search'] ?? '',
        ]);

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $traceId,
            'data' => $data,
        ]);
    }
}
