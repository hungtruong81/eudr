<?php

declare(strict_types=1);

namespace App\Application\Actions\Land;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;

class ViewLandAction extends LandAction
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
        // Check permission
        $scope = Utils::resolveScope($permissions, 'land', 'view');

        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $formData = $this->request->getQueryParams();

        $plot_code = addslashes(trim((string)$this->resolveArg('code')));

        $land = $this->landRepository->findLandOfCodeWithPermission(
            $plot_code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($land)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy lô đất");
        }

        $action = 'view';
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
        $res_return['data'] = $land->jsonSerialize();
        if(!empty($res_return['data']['land_records'])) {
            $res_return['data']['land_records'] = $this->fileRepository->mapFileIdsToMap($res_return['data']['land_records']);
        }
        if(!empty($res_return['data']['land_document_detection'])) {
            $res_return['data']['land_document_detection'] = $this->settings->get('url_cdn') . '/' . ltrim($res_return['data']['land_document_detection'], '/');
        }

        return $this->respondWithData($res_return);
    }
}
