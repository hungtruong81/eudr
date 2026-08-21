<?php

declare(strict_types=1);

namespace App\Application\Actions\RawMaterialRelease;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;


class ListRawMaterialReleaseAction extends RawMaterialReleaseAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        // Check authentication
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);

        // Check permission to view raw material releases
        $scope = Utils::resolveScope($permissions, 'raw_material_release', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $formData = $this->request->getQueryParams();

        $validator = new Validator($this->request);

        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('status', $formData['status'] ?? null, 'in:pending,approved,rejected,all');
        $validator->validate('search', $formData['search'] ?? null, 'string');
        $validator->validate('created_date_from', $formData['created_date_from'] ?? null, 'date');
        $validator->validate('created_date_to', $formData['created_date_to'] ?? null, 'date');


        if ($validator->hasErrors()) {
            $errorMessages = [];
            $errors = $validator->getErrors();
            if (is_array($errors)) {
                foreach ($errors as $field => $fieldErrors) {
                    if (is_array($fieldErrors)) {
                        $errorMessages[] = implode(', ', $fieldErrors);
                    } else {
                        $errorMessages[] = (string)$fieldErrors;
                    }
                }
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errorMessages));
        }

        // Sanitize and extract data
        $sanitizeRules = [
            'page' => 'integer',
            'limit' => 'integer',
            'status' => 'string',
            'search' => 'string',
            'created_date_from' => 'date',
            'created_date_to' => 'date'
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $page = $cleanData['page'];
        $limit = $cleanData['limit'];
        $status = $cleanData['status'] ?? 'all';
        $search = $cleanData['search'] ?? '';
        $created_date_from = $cleanData['created_date_from'] ?? null;
        $created_date_to = $cleanData['created_date_to'] ?? null;
        
        $params = [
            "page" => $page,
            "page_limit" => $limit,
            "user_id" => $this->auth_data['user_id'],
            "status" => $status,
            "search" => $search,
            "created_date_from" => $created_date_from,
            "created_date_to" => $created_date_to
        ];

        $raw_material_releases = $this->rawMaterialReleaseRepository->findAll(
            $params,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        $res_return = ["result" => "success"];
        $res_return['data'] = $raw_material_releases;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }

}
