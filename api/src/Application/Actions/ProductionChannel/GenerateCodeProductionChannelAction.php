<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionChannel;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;

class GenerateCodeProductionChannelAction extends ProductionChannelAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'production_channel', 'create');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $channel_code = $this->productionChannelRepository->generateCode();

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'production_channel',
            'action' => 'generate_code',
            'user_id' => (string)$this->auth_data['user_id'],
            'extra_1' => (string)$channel_code,
        ];
        Utils::save_log($this->logger, $log);

        $res_return = ['result' => 'success'];
        $res_return['trace_id'] = $trace_id;
        $res_return['data'] = ['channel_code' => $channel_code];

        return $this->respondWithData($res_return);
    }
}
