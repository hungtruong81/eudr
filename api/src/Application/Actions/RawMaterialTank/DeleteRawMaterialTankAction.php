<?php

declare(strict_types=1);

namespace App\Application\Actions\RawMaterialTank;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;

class DeleteRawMaterialTankAction extends RawMaterialTankAction
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

        // Check permission to delete raw material tanks
        $scope = Utils::resolveScope($permissions, 'raw_material_tank', 'delete');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $raw_material_tank_code = addslashes(trim((string)$this->resolveArg('code')));

        $raw_material_tank = $this->rawMaterialTankRepository->findRawMaterialTankOfCodeWithPermission(
            $raw_material_tank_code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($raw_material_tank)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy bồn chứa nguyên liệu thô");
        }

        // Validate volume in tank
        if ($raw_material_tank->getCurrentVolume() > 0) {
            throw new HttpBadRequestException($this->request, "Không thể xóa bồn chứa nguyên liệu khi còn nguyên liệu bên trong");
        }

        // Delete raw material tank
        $this->rawMaterialTankRepository->deleteRawMaterialTankWithPermission(
            $raw_material_tank->getId(),
            (int)$this->auth_data['user_id'],
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        $action = 'delete';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'raw_material_tank',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$raw_material_tank->getId(),
        );
        
        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }
}
