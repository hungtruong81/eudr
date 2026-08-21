<?php

declare(strict_types=1);

namespace App\Application\Actions\Sales\Orders;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

class ProductLotChartAction extends OrderAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'sales_order', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $formData = $this->request->getQueryParams();
        $validator = new Validator($this->request);

        $validator->validate('factory_id', $formData['factory_id'] ?? null, 'integer');
        $validator->validate('date_from', $formData['date_from'] ?? null, 'date');
        $validator->validate('date_to', $formData['date_to'] ?? null, 'date');

        if ($validator->hasErrors()) {
            $errors = [];
            foreach ((array)$validator->getErrors() as $fieldErrors) {
                $errors[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errors));
        }

        $clean = $validator->sanitize($formData, [
            'factory_id' => 'integer',
            'date_from' => 'date',
            'date_to' => 'date',
        ]);

        $params = [
            'factory_id' => $clean['factory_id'] ?? null,
            'date_from' => $clean['date_from'] ?? null,
            'date_to' => $clean['date_to'] ?? null,
        ];

        if ($scope === 'self' || $scope === 'own') {
            $params['company_id'] = $this->auth_data['company_id'] ?? 0;
        }

        $summary = $this->productLotRepository->getProductLotSummary($params);

        $res = ['result' => 'success', 'data' => $summary, 'trace_id' => $trace_id];
        return $this->respondWithData($res);
    }
}
