<?php

declare(strict_types=1);

namespace App\Application\Actions\Vendor;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

class ListVendorAction extends VendorAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $viewScope = Utils::resolveScope($permissions, 'vendor', 'view');
        if (empty($viewScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $formData = $this->request->getQueryParams();
        $validator = new Validator($this->request);

        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('status', $formData['status'] ?? null, 'in:active,inactive,all');
        $validator->validate('vendor_type', $formData['vendor_type'] ?? null, 'in:individual,company,all');
        $validator->validate('search', $formData['search'] ?? null, 'string');
        $validator->validate('province_id', $formData['province_id'] ?? null, 'integer|min:1');

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
            'vendor_type' => 'string',
            'search' => 'string',
            'province_id' => 'integer',
        ]);

        $data = $this->vendorRepository->findAll(
            [
                'page' => $cleanData['page'],
                'page_limit' => $cleanData['limit'],
                'status' => $cleanData['status'] ?? 'all',
                'vendor_type' => $cleanData['vendor_type'] ?? 'all',
                'search' => $cleanData['search'] ?? '',
                'province_id' => $cleanData['province_id'] ?? 0,
            ],
            (int)$this->auth_data['user_id'],
            (string)$viewScope,
            $this->auth_data['company_id'] ?? null
        );

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'data' => $data,
        ]);
    }
}
