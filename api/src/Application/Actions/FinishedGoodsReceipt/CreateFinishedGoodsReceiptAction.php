<?php

declare(strict_types=1);

namespace App\Application\Actions\FinishedGoodsReceipt;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;


class CreateFinishedGoodsReceiptAction extends FinishedGoodsReceiptAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);

        // Check permission to create finished goods receipts
        $scope = Utils::resolveScope($permissions, 'finished_goods_receipt', 'create');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $formData = $this->getFormData();

        $validator = new Validator($this->request);

        $validator->validate('finished_goods_receipt_name', $formData['finished_goods_receipt_name'] ?? null, 'required|string');
        $validator->validate('finished_goods_receipt_code', $formData['finished_goods_receipt_code'] ?? null, 'required|string|max:30');
        $validator->validate('production_order_id', $formData['production_order_id'] ?? null, 'required|integer');
        $validator->validate('product_tank_id', $formData['product_tank_id'] ?? null, 'required|integer');
        $validator->validate('product_type_category', $formData['product_type_category'] ?? null, 'required|in:scrap_rubber,concentrated_latex');
        $validator->validate('product_type_id', $formData['product_type_id'] ?? null, 'required|integer');
        $validator->validate('actual_quantity', $formData['actual_quantity'] ?? null, 'required|integer|min:1');

        // Validate rubber_blocks (optional array for creating rubber blocks)
        if (!empty($formData['rubber_blocks']) && !is_array($formData['rubber_blocks'])) {
            throw new HttpBadRequestException($this->request, "rubber_blocks phải là một mảng");
        }

        if ($validator->hasErrors()) {
            $errorMessages = [];
            $errors = $validator->getErrors();
            if (is_array($errors)) {
                foreach ($errors as $field => $fieldErrors) {
                    if (is_array($fieldErrors)) {
                        $errorMessages[] = implode(', ', $fieldErrors);
                    } else {
                        $errorMessages[] = (string)$fieldErrors;
                    }
                }
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errorMessages));
        }

        // Sanitize and extract data
        $sanitizeRules = [
            'finished_goods_receipt_name' => 'string',
            'finished_goods_receipt_code' => 'string',
            'production_order_id' => 'integer',
            'product_tank_id' => 'integer',
            'product_type_category' => 'string',
            'product_type_id' => 'integer',
            'actual_quantity' => 'integer'
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $finished_goods_receipt_name = $cleanData['finished_goods_receipt_name'];
        $finished_goods_receipt_code = $cleanData['finished_goods_receipt_code'];
        $production_order_id = $cleanData['production_order_id'];
        $product_tank_id = $cleanData['product_tank_id'];
        $product_type_category = $cleanData['product_type_category'];
        $product_type_id = $cleanData['product_type_id'];
        $actual_quantity = $cleanData['actual_quantity'];

        // Validate product type
        $existingProductType = $this->productTypeRepository->findProductTypeOfId($product_type_id);
        if (empty($existingProductType)) {
            throw new HttpNotFoundException($this->request, "Loại sản phẩm không tồn tại");
        }

        // Validate production order
        $productionOrder = $this->productionOrderRepository->findProductionOrderOfId($production_order_id);
        if (empty($productionOrder)) {
            throw new HttpNotFoundException($this->request, "Lệnh sản xuất không tồn tại");
        }

        if ($productionOrder->getStatus() !== "in_production") {
            throw new HttpBadRequestException($this->request, "Phiếu sản xuất không ở trạng thái đang sản xuất");
        }

        // Validate product tank
        $existingProductTank = $this->productTankRepository->findProductTankOfId($product_tank_id);
        if (empty($existingProductTank)) {
            throw new HttpNotFoundException($this->request, "Bồn thành phẩm không tồn tại");
        }

        // Validate unique finished goods receipt code
        $existingFinishedGoodsReceipt = $this->finishedGoodsReceiptRepository->findFinishedGoodsReceiptOfCode($finished_goods_receipt_code);
        if (!empty($existingFinishedGoodsReceipt)) {
            $finished_goods_receipt_code = $this->finishedGoodsReceiptRepository->generateCode();
        }

        // Data Finished Goods Receipt
        $actual_weight = $actual_quantity * $existingProductType->getProductWeight();

        // Validate tank capacity
        if (($existingProductTank->getCurrentVolume() + $actual_weight) > $existingProductTank->getCapacity()) {
            throw new HttpBadRequestException($this->request, "Thể tích bồn không đủ để nhận hàng thành phẩm");
        }

        // === Process Rubber Blocks ===
        $rubber_blocks_input = $formData['rubber_blocks'] ?? [];
        $created_rubber_blocks = [];
        $now = date('Y-m-d H:i:s', time());

        if (!empty($rubber_blocks_input) && is_array($rubber_blocks_input)) {
            foreach ($rubber_blocks_input as $index => $blockInput) {
                // Validate each rubber block entry
                $block_product_type_id = (int)($blockInput['product_type_id'] ?? 0);
                if (empty($block_product_type_id)) {
                    throw new HttpBadRequestException($this->request, "rubber_blocks[$index].product_type_id là bắt buộc");
                }

                $blockProductType = $this->productTypeRepository->findProductTypeOfId($block_product_type_id);
                if (empty($blockProductType)) {
                    throw new HttpNotFoundException($this->request, "rubber_blocks[$index].product_type_id: Loại sản phẩm không tồn tại");
                }

                if (empty($blockInput['weight']) || (float)$blockInput['weight'] <= 0) {
                    throw new HttpBadRequestException($this->request, "rubber_blocks[$index].weight phải lớn hơn 0");
                }

                $block_quantity = (int)($blockInput['quantity'] ?? 1);
                if ($block_quantity < 1) {
                    throw new HttpBadRequestException($this->request, "rubber_blocks[$index].quantity phải lớn hơn 0");
                }

                $block_weight = (float)$blockInput['weight'];
                $block_grade = trim((string)($blockInput['grade'] ?? ''));
                $production_date = date('Y-m-d');

                // Create N rubber blocks based on quantity
                for ($i = 0; $i < $block_quantity; $i++) {
                    $block_code = $this->rubberBlockRepository->generateCode();

                    $blockData = [
                        'rubber_block_code' => $block_code,
                        'production_order_id' => $production_order_id,
                        'product_type_id' => $block_product_type_id,
                        'weight' => $block_weight,
                        'grade' => $block_grade,
                        'production_date' => $production_date,
                        'status' => 'available',
                        'created_at' => $now,
                    ];

                    $createdBlock = $this->rubberBlockRepository->createRubberBlock($blockData);
                    if (empty($createdBlock)) {
                        throw new HttpBadRequestException($this->request, "Tạo bành cao su thất bại tại rubber_blocks[$index], bành thứ " . ($i + 1));
                    }
                    $created_rubber_blocks[] = $createdBlock;
                }
            }
        }

        $data_update = [
            'company_id' => $this->auth_data['company_id'] ?? 0,
            'finished_goods_receipt_code' => $finished_goods_receipt_code,
            'finished_goods_receipt_name' => $finished_goods_receipt_name,
            'production_order_id' => $production_order_id,
            'product_tank_id' => $product_tank_id,
            'product_type_category' => $product_type_category,
            'product_type_id' => $product_type_id,
            'actual_quantity' => $actual_quantity,
            'actual_weight' =>  $actual_weight,
            'tank_volume_before' => $existingProductTank->getCurrentVolume(),
            'tank_volume_after' => $existingProductTank->getCurrentVolume() + $actual_weight,
            'status' => 'completed', // Đã hoàn thành nhập kho
            'created_at' => $now,
            'created_by' => (int)$this->auth_data['user_id'],
        ];
        
        $finishedGoodsReceipt = $this->finishedGoodsReceiptRepository->createFinishedGoodsReceipt($data_update);
        if (empty($finishedGoodsReceipt)) {
            throw new HttpBadRequestException($this->request, "Tạo phiếu nhập kho thành phẩm thất bại");
        }

        $action = 'create';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'finished_goods_receipt',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$finishedGoodsReceipt->getId(),
        );

        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['finished_goods_receipt'] = $finishedGoodsReceipt->jsonSerialize();

        // Include created rubber blocks in response
        if (!empty($created_rubber_blocks)) {
            $res_return['rubber_blocks'] = array_map(fn($block) => $block->jsonSerialize(), $created_rubber_blocks);
        }

        return $this->respondWithData($res_return);
        
    }
}
