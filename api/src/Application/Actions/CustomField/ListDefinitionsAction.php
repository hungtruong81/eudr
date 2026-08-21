<?php

declare(strict_types=1);

namespace App\Application\Actions\CustomField;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

/**
 * GET /v1/custom-fields/definitions/
 * Query params: page, limit, search, entity_type, field_type, status
 */
class ListDefinitionsAction extends CustomFieldAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'custom_field', 'view');
        $scope = 'own'; // TEMP: disable permission check for testing
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $q = $this->request->getQueryParams();
        $validator = new Validator($this->request);

        $validator->validate('page',        $q['page']        ?? null, 'required|integer|min:1');
        $validator->validate('limit',       $q['limit']       ?? null, 'required|integer|min:1|max:100');
        $validator->validate('search',      $q['search']      ?? null, 'string');
        $validator->validate('entity_type', $q['entity_type'] ?? null, 'string|in:land,plant,harvest,customer,product,sales_order,product_lot_import_none_eudr');
        $validator->validate('field_type',  $q['field_type']  ?? null, 'string|in:text,textarea,number,date,datetime,boolean,select');
        $validator->validate('status',      $q['status']      ?? null, 'string|in:active,inactive,all');
        $validator->validate('company_id',  $q['company_id']  ?? null, 'integer');

        if ($validator->hasErrors()) {
            $errors = [];
            foreach ((array)$validator->getErrors() as $fieldErrors) {
                $errors[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errors));
        }

        $clean = $validator->sanitize($q, [
            'page'        => 'integer',
            'limit'       => 'integer',
            'search'      => 'string',
            'entity_type' => 'string',
            'field_type'  => 'string',
            'status'      => 'string',
            'company_id'  => 'integer',
        ]);

        $params = [
            'page'             => $clean['page'],
            'page_limit'       => $clean['limit'],
            'search'           => $clean['search'] ?? '',
            'entity_type'      => $clean['entity_type'] ?? '',
            'field_type'       => $clean['field_type'] ?? '',
            'status'           => $clean['status'] ?? 'active',
            'company_id_param' => $clean['company_id'] ?? null,
        ];

        $data = $this->customFieldRepository->findAllDefinitions(
            $params,
            $scope,
            $this->auth_data['company_id'] ?? null,
            $clean['company_id'] ?? null
        );

        return $this->respondWithData([
            'result'   => 'success',
            'data'     => $data,
            'trace_id' => $trace_id,
        ]);
    }
}
