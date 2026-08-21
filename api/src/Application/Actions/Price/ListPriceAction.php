<?php

declare(strict_types=1);

namespace App\Application\Actions\Price;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

class ListPriceAction extends PriceAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'price', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $formData = $this->request->getQueryParams();
        $validator = new Validator($this->request);

        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('search', $formData['search'] ?? null, 'string');
        $validator->validate('price_type', $formData['price_type'] ?? null, 'string');
        $validator->validate('price_code', $formData['price_code'] ?? null, 'string');
        $validator->validate('price_id', $formData['price_id'] ?? null, 'integer|min:1');

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
            'price_type' => 'string',
            'price_code' => 'string',
            'price_id' => 'integer',
        ]);

        $data = $this->priceRepository->findAll(
            [
                'page' => $clean['page'],
                'page_limit' => $clean['limit'],
                'search' => $clean['search'] ?? '',
                'price_type' => $clean['price_type'] ?? 'all',
                'price_code' => $clean['price_code'] ?? '',
                'price_id' => $clean['price_id'] ?? 0,
            ],
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'data' => $data,
        ]);
    }
}
