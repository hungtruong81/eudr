<?php

declare(strict_types=1);

namespace App\Application\Actions\Land\Support;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;

class DeleteLandAction extends LandSupportAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);
        
        // Check Authentication
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);

        // Check permission to delete land support
        $scope = Utils::resolveScope($permissions, 'land.support', 'delete');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $plot_code = addslashes(trim($this->resolveArg('code')));

        $land = $this->landRepository->findLandOfCode($plot_code);
        if (empty($land)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy lô đất");
        }

        if ($land->getCreatedBy() !== (int)$this->auth_data['user_id']) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        // Delete land
        $this->landRepository->deleteLand(
            $land->getId(),
            (int)$this->auth_data['user_id']
        );

        $action = 'delete';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'land',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$land->getId(),
        );
        
        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }
}
