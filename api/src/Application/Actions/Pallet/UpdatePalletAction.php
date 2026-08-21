<?php

declare(strict_types=1);

namespace App\Application\Actions\Pallet;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class UpdatePalletAction extends PalletAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'pallet', 'update');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $code = addslashes(trim((string)$this->resolveArg('code')));
        $pallet = $this->palletRepository->findPalletOfCodeWithPermission(
            $code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($pallet)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy pallet');
        }

        if ($pallet->getStatus() === 'shipped') {
            throw new HttpBadRequestException($this->request, 'Không thể sửa pallet đã xuất');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);
        $validator->validate('warehouse_id', $formData['warehouse_id'] ?? null, 'integer|min:1');

        if ($validator->hasErrors()) {
            $errors = [];
            foreach ((array)$validator->getErrors() as $fieldErrors) {
                $errors[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errors));
        }

        $clean = $validator->sanitize($formData, ['warehouse_id' => 'integer']);

        $data_update = [
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => (int)$this->auth_data['user_id'],
        ];
        if (!empty($clean['warehouse_id'])) {
            $data_update['warehouse_id'] = (int)$clean['warehouse_id'];
        }

        $updated = $this->palletRepository->updatePalletWithPermission(
            (int)$pallet->getId(),
            $data_update,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        return $this->respondWithData([
            'result' => 'success',
            'pallet' => $updated->jsonSerialize(),
            'trace_id' => $trace_id,
        ]);
    }
}
