<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionPallet;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

class ListBalesAction extends ProductionPalletAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'production_pallet', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $formData = $this->request->getQueryParams();
        $validator = new Validator($this->request);

        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('production_order_id', $formData['production_order_id'] ?? null, 'integer|min:1');
        $validator->validate('factory_id', $formData['factory_id'] ?? null, 'integer|min:1');
        $validator->validate('pallet_run_id', $formData['pallet_run_id'] ?? null, 'integer|min:1');
        $validator->validate('pressing_run_id', $formData['pressing_run_id'] ?? null, 'integer|min:1');
        $validator->validate('grade_id', $formData['grade_id'] ?? null, 'integer|min:1');
        $validator->validate('status', $formData['status'] ?? null, 'string|in:formed,qc_pass,qc_fail,packed,all');
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
            'production_order_id' => 'integer',
            'factory_id' => 'integer',
            'pallet_run_id' => 'integer',
            'pressing_run_id' => 'integer',
            'grade_id' => 'integer',
            'status' => 'string',
            'search' => 'string',
        ]);

        $result = $this->productionPalletRepository->findAllBales(
            [
                'page' => $cleanData['page'],
                'page_limit' => $cleanData['limit'],
                'production_order_id' => $cleanData['production_order_id'] ?? 0,
                'factory_id' => $cleanData['factory_id'] ?? 0,
                'pallet_run_id' => $cleanData['pallet_run_id'] ?? 0,
                'pressing_run_id' => $cleanData['pressing_run_id'] ?? 0,
                'grade_id' => $cleanData['grade_id'] ?? 0,
                'status' => $cleanData['status'] ?? 'all',
                'search' => isset($cleanData['search']) ? trim((string)$cleanData['search']) : '',
            ],
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'data' => $result,
        ]);
    }
}
