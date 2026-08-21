<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductLot;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;

class TraceProductLotAction extends ProductLotAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }
        /*
        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);

        $scope = Utils::resolveScope($permissions, 'finished_goods_receipt', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }
        */
        $product_lot_code = addslashes(trim((string)$this->resolveArg('code')));

        $product_lot = $this->productLotRepository->findProductLotOfCode($product_lot_code);
        if (empty($product_lot)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy lô hàng");
        }

        $farms = $this->productLotRepository->traceProductLotToFarms($product_lot->getId());

        $res_return = [
            'result' => 'success',
            'trace_id' => $trace_id,
            'product_lot' => $product_lot->jsonSerialize(),
            'total_farms' => count($farms),
            'farms' => $farms,
        ];

        return $this->respondWithData($res_return);
    }
}
