<?php

declare(strict_types=1);

namespace App\Application\Actions\ExternalMaterial;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;

class DeleteExternalMaterialAction extends ExternalMaterialAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        // Check Authentication
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        // Check permission
        $scope = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'finished_goods_receipt', 'delete'); // external_material
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $code = addslashes(trim((string)$this->resolveArg('code')));

        $external_material = $this->externalMaterialRepository->findExternalMaterialOfCode($code);
        if (empty($external_material)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy nguyên liệu ngoài");
        }

        // Only allow delete when status is draft
        if ($external_material->getStatus() !== 'draft') {
            throw new HttpBadRequestException($this->request, "Chỉ có thể xóa khi trạng thái là nháp (draft)");
        }

        // Soft-delete linked lands that were created as external
        $existing_lands = $this->externalMaterialRepository->findLandsByExternalMaterialId($external_material->getId());
        foreach ($existing_lands as $existing_land) {
            if (($existing_land['register_type'] ?? '') === 'external') {
                $this->landRepository->deleteLand((int)$existing_land['plot_id'], $this->auth_data['user_id']);
            }
        }

        // Soft-delete external material
        $this->externalMaterialRepository->deleteExternalMaterial($external_material->getId(), $this->auth_data['user_id']);

        // Log action
        $log = [
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'external_material',
            "action" => 'delete',
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$external_material->getId(),
        ];
        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['message'] = "Đã xóa nguyên liệu ngoài thành công";

        return $this->respondWithData($res_return);
    }
}
