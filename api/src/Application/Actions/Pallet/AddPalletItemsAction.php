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

class AddPalletItemsAction extends PalletAction
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

        if ((string)$pallet->getStatus() !== 'empty') {
            throw new HttpBadRequestException($this->request, 'Chỉ thêm bành khi pallet ở trạng thái empty');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);
        $validator->validate('rubber_block_ids', $formData['rubber_block_ids'] ?? null, 'required|array|min:1');

        if ($validator->hasErrors()) {
            $errors = [];
            foreach ((array)$validator->getErrors() as $fieldErrors) {
                $errors[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errors));
        }

        $rubber_block_ids = $formData['rubber_block_ids'] ?? [];
        if (!is_array($rubber_block_ids) || empty($rubber_block_ids)) {
            throw new HttpBadRequestException($this->request, 'rubber_block_ids không hợp lệ');
        }

        $valid_ids = [];
        foreach ($rubber_block_ids as $rbid) {
            $id = (int)$rbid;
            if ($id <= 0) {
                continue;
            }
            $block = $this->rubberBlockRepository->findRubberBlockOfId($id);
            if (empty($block) || !$block->getId()) {
                throw new HttpNotFoundException($this->request, 'Không tìm thấy rubber block: ' . $id);
            }
            $blockData = $block->jsonSerialize();
            if (($blockData['status'] ?? 'available') === 'shipped' || ($blockData['status'] ?? 'available') === 'defective') {
                throw new HttpBadRequestException($this->request, 'Rubber block không khả dụng: ' . $id);
            }
            $valid_ids[] = $id;
        }

        $inserted = $this->palletRepository->addPalletItems((int)$pallet->getId(), $valid_ids);
        $updatedPallet = $this->palletRepository->findPalletOfId((int)$pallet->getId());

        return $this->respondWithData([
            'result' => 'success',
            'data' => [
                'inserted' => $inserted,
                'pallet' => $updatedPallet ? $updatedPallet->jsonSerialize() : null,
                'items' => $this->palletRepository->listPalletItems((int)$pallet->getId()),
            ],
            'trace_id' => $trace_id,
        ]);
    }
}
