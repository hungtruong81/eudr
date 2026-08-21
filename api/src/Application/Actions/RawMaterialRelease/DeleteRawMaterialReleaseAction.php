<?php

declare(strict_types=1);

namespace App\Application\Actions\RawMaterialRelease;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;


class DeleteRawMaterialReleaseAction extends RawMaterialReleaseAction
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

        // Check permission to delete raw material releases
        $scope = Utils::resolveScope($permissions, 'raw_material_release', 'delete');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $raw_material_release_code = addslashes(trim((string)$this->resolveArg('code')));

        $raw_material_release = $this->rawMaterialReleaseRepository->findRawMaterialReleaseOfCodeWithPermission(
            $raw_material_release_code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($raw_material_release)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy phiếu xuất nguyên liệu thô");
        }

        // Delete raw material release
        $this->rawMaterialReleaseRepository->deleteRawMaterialRelease($raw_material_release->getId(), $this->auth_data['user_id']);
        
        $action = 'delete';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'raw_material_release',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$raw_material_release->getId(),
        );
        
        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }
}
