<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingSubTankIntake;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

final class ViewPurchasingSubTankIntakeAction extends PurchasingSubTankIntakeAction
{
    protected function action(): Response
    {
        $userId = (int)($this->auth_data['user_id'] ?? 0);
        if (!$userId) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions($userId);
        if (!Utils::resolveScope($permissions, 'purchasing_sub_tank', 'view')) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $intake = $this->intakeRepository->findById((int)$this->resolveArg('intakeId'), (int)($this->auth_data['company_id'] ?? 0));
        if (!$intake) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy intake');
        }

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => Utils::generateRandomString(25),
            'purchasing_sub_tank_intake' => $intake,
        ]);
    }
}
