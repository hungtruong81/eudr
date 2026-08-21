<?php

declare(strict_types=1);

namespace App\Application\Actions\Sales\Contracts;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class ListContractAction extends ContractAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'sales_contract', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $formData = $this->request->getQueryParams();
        $validator = new Validator($this->request);

        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('search', $formData['search'] ?? null, 'string');
        $validator->validate('status', $formData['status'] ?? null, 'string|in:draft,active,expired,terminated,cancelled,all');
        $validator->validate('customer_id', $formData['customer_id'] ?? null, 'integer');
        $validator->validate('start_date_from', $formData['start_date_from'] ?? null, 'date');
        $validator->validate('start_date_to', $formData['start_date_to'] ?? null, 'date');
        $validator->validate('company_id', $formData['company_id'] ?? null, 'integer');

        if ($validator->hasErrors()) {
            $errors = [];
            foreach ((array)$validator->getErrors() as $fieldErrors) {
                $errors[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errors));
        }

        $clean = $validator->sanitize($formData, [
            'page' => 'integer',
            'limit' => 'integer',
            'search' => 'string',
            'status' => 'string',
            'customer_id' => 'integer',
            'start_date_from' => 'date',
            'start_date_to' => 'date',
            'company_id' => 'integer',
        ]);

        $params = [
            'page' => $clean['page'],
            'page_limit' => $clean['limit'],
            'search' => $clean['search'] ?? '',
            'status' => $clean['status'] ?? 'all',
            'customer_id' => $clean['customer_id'] ?? 0,
            'start_date_from' => $clean['start_date_from'] ?? null,
            'start_date_to' => $clean['start_date_to'] ?? null,
            'company_id_param' => $clean['company_id'] ?? null,
        ];

        $data = $this->salesContractRepository->findAll(
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
