<?php

declare(strict_types=1);

namespace App\Application\Actions\RubberBlock;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class ListRubberBlockAction extends RubberBlockAction
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

        // Check permission to view rubber blocks
        $scope = Utils::resolveScope($permissions, 'finished_goods_receipt', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $formData = $this->request->getQueryParams();

        $validator = new Validator($this->request);

        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('production_order_id', $formData['production_order_id'] ?? null, 'integer|min:1');
        $validator->validate('product_type_id', $formData['product_type_id'] ?? null, 'integer|min:1');
        $validator->validate('grade', $formData['grade'] ?? null, 'string');
        $validator->validate('status', $formData['status'] ?? null, 'string|in:available,allocated,shipped,defective,all');
        $validator->validate('search', $formData['search'] ?? null, 'string');
        $validator->validate('production_date_from', $formData['production_date_from'] ?? null, 'date');
        $validator->validate('production_date_to', $formData['production_date_to'] ?? null, 'date');

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
            'production_order_id' => 'integer',
            'product_type_id' => 'integer',
            'grade' => 'string',
            'status' => 'string',
            'search' => 'string',
            'production_date_from' => 'date',
            'production_date_to' => 'date',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $params = [
            "page" => $cleanData['page'],
            "page_limit" => $cleanData['limit'],
            "production_order_id" => $cleanData['production_order_id'] ?? 0,
            "product_type_id" => $cleanData['product_type_id'] ?? 0,
            "grade" => $cleanData['grade'] ?? '',
            "status" => $cleanData['status'] ?? 'all',
            "search" => $cleanData['search'] ?? '',
            "production_date_from" => $cleanData['production_date_from'] ?? null,
            "production_date_to" => $cleanData['production_date_to'] ?? null,
        ];

        $rubber_blocks = $this->rubberBlockRepository->findAll($params);

        $res_return = ["result" => "success"];
        $res_return['data'] = $rubber_blocks;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }
}
