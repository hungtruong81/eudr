<?php

declare(strict_types=1);

namespace App\Application\Actions\ExternalMaterial;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class ListExternalMaterialAction extends ExternalMaterialAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        // Check Authentication
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        // Check permission
        $scope = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'finished_goods_receipt', 'view'); // external_material
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $formData = $this->request->getQueryParams();

        $validator = new Validator($this->request);

        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('status', $formData['status'] ?? null, 'in:all,draft,confirmed,cancelled');
        $validator->validate('start_date', $formData['start_date'] ?? null, 'date');
        $validator->validate('end_date', $formData['end_date'] ?? null, 'date');
        $validator->validate('factory_id', $formData['factory_id'] ?? null, 'integer');
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

        $sanitizeRules = [
            'page' => 'integer',
            'limit' => 'integer',
            'status' => 'string',
            'start_date' => 'date',
            'end_date' => 'date',
            'factory_id' => 'integer',
            'search' => 'string',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $params = [
            "page" => $cleanData['page'],
            "page_limit" => $cleanData['limit'],
            "status" => $cleanData['status'] ?? 'all',
            "start_date" => $cleanData['start_date'] ?? null,
            "end_date" => $cleanData['end_date'] ?? null,
            "factory_id" => $cleanData['factory_id'] ?? 0,
            "search" => $cleanData['search'] ?? '',
            "scope" => $scope,
        ];

        $data = $this->externalMaterialRepository->findAll($params);

        $res_return = ["result" => "success"];
        $res_return['data'] = $data;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }
}
