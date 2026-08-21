<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingSubTank;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class HistoryPurchasingSubTankAction extends PurchasingSubTankAction
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

        $code = addslashes(trim((string)$this->resolveArg('code')));
        $subTank = $this->purchasingSubTankRepository->findPurchasingSubTankOfCodeWithPermission(
            $code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        if (empty($subTank)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy bình con');
        }

        $formData = $this->request->getQueryParams();
        $validator = new Validator($this->request);
        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('action_type', $formData['action_type'] ?? null, 'in:input,output,transfer,adjustment,all');
        $validator->validate('rubber_type', $formData['rubber_type'] ?? null, 'in:latex,cup_lump,scrap_rubber,mixed,all');

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
            'action_type' => 'string',
            'rubber_type' => 'string',
        ]);

        $history = $this->purchasingSubTankRepository->getHistoryOfPurchasingSubTank(
            (int)$subTank->getId(),
            [
                'page' => $cleanData['page'],
                'page_limit' => $cleanData['limit'],
                'action_type' => $cleanData['action_type'] ?? 'all',
                'rubber_type' => $cleanData['rubber_type'] ?? 'all',
            ],
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'data' => $history,
        ]);
    }
}
