<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingSubTankIntake;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

final class ListPurchasingSubTankIntakeAction extends PurchasingSubTankIntakeAction
{
    protected function action(): Response
    {
        $userId = (int)($this->auth_data['user_id'] ?? 0);
        if (!$userId) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }
        $permissions = $this->userRepository->getUserPermissions($userId);
        $scope = Utils::resolveScope($permissions, 'purchasing_sub_tank', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $params = $this->request->getQueryParams();
        if (isset($this->args['tankCode'])) {
            $tank = $this->subTankRepository->findPurchasingSubTankOfCodeWithPermission(
                trim((string)$this->args['tankCode']),
                $userId,
                (string)$scope,
                $this->auth_data['company_id'] ?? null
            );
            if ($tank === null) {
                throw new HttpNotFoundException($this->request, 'Không tìm thấy bình con');
            }
            $params['sub_tank_id'] = $tank->getId();
        }

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => Utils::generateRandomString(25),
            'data' => $this->intakeRepository->findAll($params, (int)($this->auth_data['company_id'] ?? 0)),
        ]);
    }
}
