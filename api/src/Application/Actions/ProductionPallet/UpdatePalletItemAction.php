<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionPallet;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

class UpdatePalletItemAction extends ProductionPalletAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $authUserId = (int)$this->auth_data['user_id'];
        $permissions = $this->userRepository->getUserPermissions($authUserId);

        $scope = Utils::resolveScope($permissions, 'production_pallet', 'update');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền cập nhật pallet item');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);
        $validator->validate('pallet_item_id', $formData['pallet_item_id'] ?? null, 'required|integer|min:1');
        $validator->validate('bale_id', $formData['bale_id'] ?? null, 'required|integer|min:1');

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
            'pallet_item_id' => 'integer',
            'bale_id' => 'integer',
        ]);

        $updated = $this->productionPalletRepository->updatePalletItem([
            'pallet_item_id' => (int)$cleanData['pallet_item_id'],
            'bale_id' => (int)$cleanData['bale_id'],
            'updated_by' => $authUserId,
        ]);

        if (empty($updated)) {
            throw new HttpBadRequestException($this->request, 'Cập nhật pallet item thất bại');
        }

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'data' => $updated,
        ]);
    }
}
