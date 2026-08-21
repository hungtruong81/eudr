<?php

declare(strict_types=1);

namespace App\Application\Actions\RawMaterialRelease;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;

class GenerateCodeRawMaterialReleaseAction extends RawMaterialReleaseAction
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

        // Permission: reuse create scope for code generation
        $scope = Utils::resolveScope($permissions, 'raw_material_release', 'create');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $raw_material_release_code = $this->rawMaterialReleaseRepository->generateCode();

        $action = 'generate_code';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'raw_material_release',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$raw_material_release_code,
        );
        
        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['data'] = ['raw_material_release_code' => $raw_material_release_code];

        return $this->respondWithData($res_return);
    }
}
