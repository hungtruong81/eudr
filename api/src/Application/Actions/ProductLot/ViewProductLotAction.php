<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductLot;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;

class ViewProductLotAction extends ProductLotAction
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

        // Check permission to view product lots
        $scope = Utils::resolveScope($permissions, 'finished_goods_receipt', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $product_lot_code = addslashes(trim((string)$this->resolveArg('code')));

        $product_lot = $this->productLotRepository->findProductLotOfCode($product_lot_code);
        if (empty($product_lot)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy lô hàng");
        }

        // Get lot items
        $items = $this->productLotRepository->getProductLotItems($product_lot->getId());

        $action = 'view';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'product_lot',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$product_lot->getId(),
        );
        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['data'] = $product_lot->jsonSerialize();
        $res_return['data']['items'] = array_map(fn($item) => $item->jsonSerialize(), $items);

        return $this->respondWithData($res_return);
    }
}
