<?php

declare(strict_types=1);

namespace App\Application\Actions\Pallet;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

class CreatePalletAction extends PalletAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'pallet', 'create');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Không có quyền tạo pallet');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('pallet_code', $formData['pallet_code'] ?? null, 'string|max:30');
        $validator->validate('warehouse_id', $formData['warehouse_id'] ?? null, 'required|integer|min:1');

        if ($validator->hasErrors()) {
            $errors = [];
            foreach ((array)$validator->getErrors() as $fieldErrors) {
                $errors[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errors));
        }

        $clean = $validator->sanitize($formData, [
            'pallet_code' => 'string',
            'warehouse_id' => 'integer',
        ]);

        $pallet_code = trim((string)($clean['pallet_code'] ?? ''));
        if ($pallet_code === '') {
            $pallet_code = $this->palletRepository->generateCode();
        } elseif (!empty($this->palletRepository->findPalletOfCode($pallet_code))) {
            throw new HttpBadRequestException($this->request, 'Mã pallet đã tồn tại');
        }

        $pallet = $this->palletRepository->createPallet([
            'pallet_code' => $pallet_code,
            'warehouse_id' => (int)$clean['warehouse_id'],
            'status' => 'empty',
            'total_bales' => 0,
            'total_weight' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'company_id' => (int)($this->auth_data['company_id'] ?? 0),
            'created_by' => (int)$this->auth_data['user_id'],
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => (int)$this->auth_data['user_id'],
        ]);

        if (empty($pallet)) {
            throw new HttpBadRequestException($this->request, 'Không thể tạo pallet');
        }

        return $this->respondWithData([
            'result' => 'success',
            'pallet' => $pallet->jsonSerialize(),
            'trace_id' => $trace_id,
        ]);
    }
}
