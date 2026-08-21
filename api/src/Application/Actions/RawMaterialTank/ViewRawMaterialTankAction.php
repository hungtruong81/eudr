<?php

declare(strict_types=1);

namespace App\Application\Actions\RawMaterialTank;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;


class ViewRawMaterialTankAction extends RawMaterialTankAction
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

        // Check permission to view raw material tanks
        $scope = Utils::resolveScope($permissions, 'raw_material_tank', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }
        
        $formData = $this->request->getQueryParams();

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

        $action = 'view';
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
        $res_return['data'] = $raw_material_tank->jsonSerialize();

        return $this->respondWithData($res_return);
    }
}
