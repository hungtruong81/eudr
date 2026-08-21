<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductLot;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;

class CancelExternalProductLotAction extends ProductLotAction
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

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'finished_goods_receipt', 'update');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $product_lot_code = addslashes(trim((string)$this->resolveArg('code')));

        $productLot = $this->productLotRepository->findProductLotOfCode($product_lot_code);
        if (empty($productLot)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy lô hàng");
        }

        if ($productLot->getLotType() !== 'external') {
            throw new HttpBadRequestException($this->request, "Chỉ có thể hủy lô hàng bên ngoài");
        }

        if (!in_array($productLot->getStatus(), ['draft', 'confirmed'])) {
            throw new HttpBadRequestException($this->request, "Không thể hủy lô hàng ở trạng thái hiện tại");
        }

        $productLot = $this->productLotRepository->cancelProductLot(
            $productLot->getId(),
            (int)$this->auth_data['user_id']
        );

        if (empty($productLot)) {
            throw new HttpBadRequestException($this->request, "Hủy lô hàng thất bại");
        }

        $log = [
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'product_lot',
            "action" => 'cancel_external',
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$productLot->getId(),
        ];
        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['data'] = $productLot->jsonSerialize();

        return $this->respondWithData($res_return);
    }
}
