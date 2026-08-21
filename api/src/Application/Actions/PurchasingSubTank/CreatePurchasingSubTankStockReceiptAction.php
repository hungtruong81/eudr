<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingSubTank;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use RuntimeException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

final class CreatePurchasingSubTankStockReceiptAction extends PurchasingSubTankAction
{
    protected function action(): Response
    {
        $userId = (int)($this->auth_data['user_id'] ?? 0);
        if (!$userId) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }
        $permissions = $this->userRepository->getUserPermissions($userId);
        $scope = Utils::resolveScope($permissions, 'purchasing_sub_tank', 'update');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền cập nhật bình con');
        }

        $tank = $this->purchasingSubTankRepository->findPurchasingSubTankOfCodeWithPermission(
            trim((string)$this->resolveArg('tankCode')),
            $userId,
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );
        if (!$tank) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy bình con');
        }

        $input = $this->getFormData();
        $input['rubber_type'] = $input['rubber_type'] ?? 'latex';
        $input['event_time'] = $input['event_time'] ?? date('Y-m-d H:i:s');

        $validator = new Validator($this->request);
        $validator->validate('weight_kg', $input['weight_kg'] ?? null, 'required|numeric|min:0.01');
        $validator->validate('rubber_type', $input['rubber_type'], 'required|in:latex,cup_lump,scrap_rubber,mixed');
        $validator->validate('event_time', $input['event_time'], 'required|date');
        $validator->validate('notes', $input['notes'] ?? null, 'string|max:255');
        if ($validator->hasErrors()) {
            $messages = [];
            foreach ($validator->getErrors() as $fieldErrors) {
                $messages[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
            }
            throw new HttpBadRequestException($this->request, implode('; ', $messages));
        }

        $clean = $validator->sanitize($input, [
            'weight_kg' => 'float',
            'rubber_type' => 'string',
            'event_time' => 'string',
            'notes' => 'string',
        ]);

        try {
            $updated = $this->purchasingSubTankRepository->recordStockMovement(
                (int)$tank->getId(),
                (int)($this->auth_data['company_id'] ?? 0),
                (float)$clean['weight_kg'],
                (string)$clean['rubber_type'],
                (string)$clean['event_time'],
                $clean['notes'] ?? null,
                $userId,
                'stock_receipt'
            );
        } catch (RuntimeException $exception) {
            throw new HttpBadRequestException($this->request, $exception->getMessage(), $exception);
        }

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => Utils::generateRandomString(25),
            'purchasing_sub_tank' => $updated,
        ], 201);
    }
}
