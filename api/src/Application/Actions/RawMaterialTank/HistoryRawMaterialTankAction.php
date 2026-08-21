<?php

declare(strict_types=1);

namespace App\Application\Actions\RawMaterialTank;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;


class HistoryRawMaterialTankAction extends RawMaterialTankAction
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

        // Check permission to view raw material tank history
        $scope = Utils::resolveScope($permissions, 'raw_material_tank', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $raw_material_tank_code = addslashes(trim((string)$this->resolveArg('code')));

        $raw_material_tank = $this->rawMaterialTankRepository->findRawMaterialTankOfCodeWithPermission(
            $raw_material_tank_code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($raw_material_tank)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy bồn chứa nguyên liệu thô");
        }

        $formData = $this->request->getQueryParams();

        $validator = new Validator($this->request);

        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('action_type', $formData['action_type'] ?? null, 'in:input,output,all');
        $validator->validate('rubber_type', $formData['rubber_type'] ?? null, 'in:latex,mixed,all');
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
            'action_type' => 'string',
            'rubber_type' => 'string',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $page = $cleanData['page'];
        $limit = $cleanData['limit'];
        $action_type = $cleanData['action_type'] ?? 'all';
        $rubber_type = $cleanData['rubber_type'] ?? 'all';

        $params = [
            "page" => $page,
            "page_limit" => $limit,
            "user_id" => $this->auth_data['user_id'],
            "action_type" => $action_type,
            "rubber_type" => $rubber_type,
        ];

        $data_history = $this->rawMaterialTankRepository->getHistoryOfRawMaterialTank(
            $raw_material_tank->getId(),
            $params,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        $res_return = ["result" => "success"];
        $res_return['data'] = $data_history;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }

}
