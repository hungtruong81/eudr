<?php

declare(strict_types=1);

namespace App\Application\Actions\Land;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;


class ApproveLandAction extends LandAction
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

        // Check Permission to approve land
        $scope = Utils::resolveScope($permissions, 'land', 'approve');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $land_code = addslashes(trim((string)$this->resolveArg('code')));
        
        $land = $this->landRepository->findLandOfCode($land_code);
        if (empty($land)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy thửa đất với mã {$land_code}");
        }

        $formData = $this->getFormData();

        $is_approved = 0;
        if (!empty($formData['is_approved'])) {
            $is_approved = intval($formData['is_approved']);
        }

        $eudr_status = 0;
        if (!empty($formData['eudr_status'])) {
            $eudr_status = intval($formData['eudr_status']);
        }

       // Update land
        $data_update = [
            "is_approved" => $is_approved,
            "eudr_status" => $eudr_status,
            "approved_by" => $this->auth_data['user_id'],
            "approved_at" => date("Y-m-d H:i:s", time()),
            // "updated_by" => $this->auth_data['user_id'],
            // "updated_at" => date("Y-m-d H:i:s", time()),
        ];
        
        $land = $this->landRepository->updateLand($land->getId(), $data_update);

        $action = 'approve';
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
        $res_return['land'] = $land->jsonSerialize();

        return $this->respondWithData($res_return);

    }
}
