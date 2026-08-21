<?php

declare(strict_types=1);

namespace App\Application\Actions\Plant;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;


class ListPlantAction extends PlantAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        // Check Authentication
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);

        // Check permission to view plants and lands (land view scope is required for plot access)
        $plantScope = Utils::resolveScope($permissions, 'plant', 'view');
        $landScope = Utils::resolveScope($permissions, 'land', 'view');

        if (empty($plantScope) || empty($landScope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $formData = $this->request->getQueryParams();

        $validator = new Validator($this->request);

        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('plot_code', $formData['plot_code'] ?? null, 'string|max:50');
        $validator->validate('search', $formData['search'] ?? null, 'string');

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
            'plot_code' => 'string',
            'search' => 'string'
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $page = $cleanData['page'];
        $limit = $cleanData['limit'];
        $plot_code = $cleanData['plot_code'] ?? null;
        $search = $cleanData['search'] ?? null;
        
        $plot_id = 0;
        if(!empty($plot_code)) {
            $land = $this->landRepository->findLandOfCodeWithPermission($plot_code, $this->auth_data['user_id'], (string)$landScope);
            if (empty($land)) {
                throw new HttpNotFoundException($this->request, "Không tìm thấy lô đất");
            }
            $plot_id = $land->getId();
        }
        
        $params = [
            "page" => $page,
            "page_limit" => $limit,
            "plot_id" => $plot_id,
            "search" => $search,
        ];
        
        $plants = $this->plantRepository->findAll(
            $params,
            (int)$this->auth_data['user_id'],
            (string)$plantScope,
            $this->auth_data['company_id'] ?? null
        );

        $res_return = ["result" => "success"];
        $res_return['data'] = $plants;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }

}
