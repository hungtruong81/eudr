<?php

declare(strict_types=1);

namespace App\Application\Actions\Sales\Customers;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class ListCustomerAction extends CustomerAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'sales_customer', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $formData = $this->request->getQueryParams();
        $validator = new Validator($this->request);

        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('search', $formData['search'] ?? null, 'string');
        $validator->validate('status', $formData['status'] ?? null, 'string|in:active,inactive,all');
        $validator->validate('company_id', $formData['company_id'] ?? null, 'integer');

        if ($validator->hasErrors()) {
            $errorMessages = [];
            foreach ((array)$validator->getErrors() as $fieldErrors) {
                $errorMessages[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errorMessages));
        }

        $clean = $validator->sanitize($formData, [
            'page' => 'integer',
            'limit' => 'integer',
            'search' => 'string',
            'status' => 'string',
            'company_id' => 'integer',
        ]);

        $params = [
            'page' => $clean['page'],
            'page_limit' => $clean['limit'],
            'search' => $clean['search'] ?? '',
            'status' => $clean['status'] ?? 'all',
            'company_id_param' => $clean['company_id'] ?? null,
        ];

        $data = $this->salesCustomerRepository->findAll(
            $params,
            (int)$this->auth_data['user_id'],
            $scope,
            $this->auth_data['company_id'] ?? null,
            $clean['company_id'] ?? null
        );

        $res = ['result' => 'success', 'data' => $data, 'trace_id' => $trace_id];
        return $this->respondWithData($res);
    }
}
