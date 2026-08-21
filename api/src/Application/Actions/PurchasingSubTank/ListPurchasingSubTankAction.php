<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingSubTank;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

class ListPurchasingSubTankAction extends PurchasingSubTankAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'purchasing_sub_tank', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $formData = $this->request->getQueryParams();
        $validator = new Validator($this->request);
        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('rubber_type', $formData['rubber_type'] ?? null, 'in:latex,cup_lump,scrap_rubber,mixed,all');
        $validator->validate('status', $formData['status'] ?? null, 'in:idle,in_use,sealed,transporting,cleaning,damaged,inactive,maintenance,full,all');
        $validator->validate('search', $formData['search'] ?? null, 'string');

        if ($validator->hasErrors()) {
            $errorMessages = [];
            $errors = $validator->getErrors();
            if (is_array($errors)) {
                foreach ($errors as $fieldErrors) {
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
            'rubber_type' => 'string',
            'status' => 'string',
            'search' => 'string',
        ]);

        $data = $this->purchasingSubTankRepository->findAll(
            [
                'page' => $cleanData['page'],
                'page_limit' => $cleanData['limit'],
                'rubber_type' => $cleanData['rubber_type'] ?? 'all',
                'status' => $cleanData['status'] ?? 'all',
                'search' => $cleanData['search'] ?? '',
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
