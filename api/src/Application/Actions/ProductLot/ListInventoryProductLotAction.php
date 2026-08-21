<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductLot;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

class ListInventoryProductLotAction extends ProductLotAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'finished_goods_receipt', 'view');

        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $formData = $this->request->getQueryParams();
        $validator = new Validator($this->request);

        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('search', $formData['search'] ?? null, 'string');
        $validator->validate('factory_id', $formData['factory_id'] ?? null, 'integer|min:1');
        $validator->validate('grade', $formData['grade'] ?? null, 'string');
        $validator->validate('lot_type', $formData['lot_type'] ?? null, 'string|in:internal,external,all');
        $validator->validate('eudr_type', $formData['eudr_type'] ?? null, 'string|in:eudr,non_eudr,all');
        $validator->validate('production_date_from', $formData['production_date_from'] ?? null, 'date');
        $validator->validate('production_date_to', $formData['production_date_to'] ?? null, 'date');
        $validator->validate('company_id', $formData['company_id'] ?? null, 'integer|min:1');

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

        $cleanData = $validator->sanitize($formData, [
            'page' => 'integer',
            'limit' => 'integer',
            'search' => 'string',
            'factory_id' => 'integer',
            'grade' => 'string',
            'lot_type' => 'string',
            'eudr_type' => 'string',
            'production_date_from' => 'date',
            'production_date_to' => 'date',
            'company_id' => 'integer',
        ]);

        $inventoryCompanyId = (int)($this->auth_data['company_id'] ?? 0);
        $inventoryUserId = (int)($this->auth_data['user_id'] ?? 0);
        
        // Scope 'all' allows filtering by company; 'own' restricts to user's own lots
        if ($scope === 'all' && !empty($cleanData['company_id'])) {
            $inventoryCompanyId = (int)$cleanData['company_id'];
        }

        $params = [
            'page' => $cleanData['page'],
            'page_limit' => $cleanData['limit'],
            'factory_id' => $cleanData['factory_id'] ?? 0,
            'grade' => $cleanData['grade'] ?? '',
            'status' => 'all',
            'lot_type' => $cleanData['lot_type'] ?? 'all',
            'eudr_type' => $cleanData['eudr_type'] ?? 'all',
            'search' => $cleanData['search'] ?? '',
            'production_date_from' => $cleanData['production_date_from'] ?? null,
            'production_date_to' => $cleanData['production_date_to'] ?? null,
            'inventory_only' => 1,
        ];

        // Filter by scope: 'own' uses owner_id, 'all' uses owner_company_id
        if ($scope === 'own') {
            $params['owner_id'] = $inventoryUserId;
        } else {
            $params['owner_company_id'] = $inventoryCompanyId;
        }
        
        $product_lots = $this->productLotRepository->findAll($params);

        $res_return = ["result" => "success"];
        $res_return['data'] = $product_lots;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }
}