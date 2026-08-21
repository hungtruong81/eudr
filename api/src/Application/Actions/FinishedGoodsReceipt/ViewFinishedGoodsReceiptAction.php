<?php

declare(strict_types=1);

namespace App\Application\Actions\FinishedGoodsReceipt;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;


class ViewFinishedGoodsReceiptAction extends FinishedGoodsReceiptAction
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

        // Check permission to view finished goods receipts
        $scope = Utils::resolveScope($permissions, 'finished_goods_receipt', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $formData = $this->request->getQueryParams();

        $finished_goods_receipt_code = addslashes(trim((string)$this->resolveArg('code')));

        $finished_goods_receipt = $this->finishedGoodsReceiptRepository->findFinishedGoodsReceiptOfCodeWithPermission(
            $finished_goods_receipt_code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        if (empty($finished_goods_receipt)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy phiếu nhập hàng hoàn thành");
        }

        $action = 'view';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'finished_goods_receipt',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$finished_goods_receipt->getId(),
        );
        Utils::save_log($this->logger, $log);

        // Get rubber blocks for this receipt's production order
        $production_order_id = $finished_goods_receipt->jsonSerialize()['production_order_id'] ?? 0;
        $rubber_blocks = [];
        if (!empty($production_order_id)) {
            $blocks = $this->rubberBlockRepository->findRubberBlocksByProductionOrderId((int)$production_order_id);
            $rubber_blocks = array_map(fn($block) => $block->jsonSerialize(), $blocks);
        }

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['data'] = $finished_goods_receipt->jsonSerialize();
        $res_return['data']['rubber_blocks'] = $rubber_blocks;

        return $this->respondWithData($res_return);
    }
}
