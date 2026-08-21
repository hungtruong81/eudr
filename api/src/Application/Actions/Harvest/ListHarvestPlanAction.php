<?php

declare(strict_types=1);

namespace App\Application\Actions\Harvest;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;


class ListHarvestPlanAction extends HarvestAction
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

        // Check permission
        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'harvest_plan', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $formData = $this->request->getQueryParams();

        $validator = new Validator($this->request);

        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('contract_code', $formData['contract_code'] ?? null, 'string');
        $validator->validate('search', $formData['search'] ?? null, 'string');
        $validator->validate('harvest_start_date', $formData['harvest_start_date'] ?? null, 'date');
        $validator->validate('harvest_end_date', $formData['harvest_end_date'] ?? null, 'date');
        $validator->validate('tapping_regime', $formData['tapping_regime'] ?? null, 'in:D1,D2,D3,D4,Flexible');


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
            'search' => 'string',
            'harvest_start_date' => 'date',
            'harvest_end_date' => 'date',
            'tapping_regime' => 'string',
            'contract_code' => 'string',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $page = $cleanData['page'];
        $limit = $cleanData['limit'];
        $search = $cleanData['search'] ?? '';
        $harvest_start_date = $cleanData['harvest_start_date'] ?? null;
        $harvest_end_date = $cleanData['harvest_end_date'] ?? null;
        $tapping_regime = $cleanData['tapping_regime'] ?? '';
        $contract_code = $cleanData['contract_code'] ?? '';

        if (!empty($harvest_start_date) && !empty($harvest_end_date)) {
            if (strtotime($harvest_start_date) > strtotime($harvest_end_date)) {
                throw new HttpBadRequestException($this->request, "Ngày bắt đầu thu hoạch không thể sau ngày kết thúc thu hoạch");
            }
        }

        $params = [
            "page" => $page,
            "page_limit" => $limit,
            "search" => $search,
            "harvest_start_date" => $harvest_start_date,
            "harvest_end_date" => $harvest_end_date,
            "tapping_regime" => $tapping_regime,
            "scope" => (string)$scope,
            "user_id" => $this->auth_data['user_id'],
            "company_id" => $this->auth_data['company_id'] ?? null,
            "contract_code" => $contract_code
        ];

        $harvest_plans = $this->harvestRepository->findAllHarvestPlans($params);

        $res_return = ["result" => "success"];
        $res_return['data'] = $harvest_plans;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }

}
