<?php

declare(strict_types=1);

namespace App\Application\Actions\RubberBlock;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;

class ViewRubberBlockAction extends RubberBlockAction
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

        // Check permission to view rubber blocks
        $scope = Utils::resolveScope($permissions, 'finished_goods_receipt', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $rubber_block_code = addslashes(trim((string)$this->resolveArg('code')));

        $rubber_block = $this->rubberBlockRepository->findRubberBlockOfCode($rubber_block_code);
        if (empty($rubber_block)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy bành cao su");
        }

        $action = 'view';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'rubber_block',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$rubber_block->getId(),
        );
        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['data'] = $rubber_block->jsonSerialize();

        return $this->respondWithData($res_return);
    }
}
