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

class UpdateFinishedGoodsReceiptAction extends FinishedGoodsReceiptAction
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

        // Check permission to update finished goods receipts
        $scope = Utils::resolveScope($permissions, 'finished_goods_receipt', 'update');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $finished_goods_receipt_code = addslashes(trim((string)$this->resolveArg('code')));

        $finished_goods_receipt = $this->finishedGoodsReceiptRepository->findFinishedGoodsReceiptOfCodeWithPermission(
            $finished_goods_receipt_code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($finished_goods_receipt)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy phiếu nhập thành phẩm");
        }

        $formData = $this->getFormData();

        $validator = new Validator($this->request);

        $validator->validate('production_order_name', $formData['production_order_name'] ?? null, 'required|string');
        $validator->validate('contract_id', $formData['contract_id'] ?? null, 'integer');
        $validator->validate('contract_code', $formData['contract_code'] ?? null, 'string');
        $validator->validate('product_type_category', $formData['product_type_category'] ?? null, 'required|in:scrap_rubber,concentrated_latex');
        $validator->validate('product_type_id', $formData['product_type_id'] ?? null, 'required|integer');
        $validator->validate('required_quantity', $formData['required_quantity'] ?? null, 'required|integer|min:1');
        $validator->validate('description', $formData['description'] ?? null, 'string');

        // Validate rubber_blocks (optional array for adding rubber blocks)
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
            'production_order_name' => 'string',
            'contract_id' => 'integer',
            'contract_code' => 'string',
            'product_type_category' => 'string',
            'product_type_id' => 'integer',
            'required_quantity' => 'integer',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $production_order_name = $cleanData['production_order_name'];
        $contract_id = $cleanData['contract_id'] ?? 0;
        $contract_code = $cleanData['contract_code'] ?? '';
        $product_type_category = $cleanData['product_type_category'];
        $product_type_id = $cleanData['product_type_id'];
        $required_quantity = $cleanData['required_quantity'];

        // Validate unique product type code
        $existingProductType = $this->productTypeRepository->findProductTypeOfId($product_type_id);
        if (empty($existingProductType)) {
            throw new HttpNotFoundException($this->request, "Loại sản phẩm không tồn tại");
        }

        $now = date("Y-m-d H:i:s", time());
        $production_order_id = $finished_goods_receipt->jsonSerialize()['production_order_id'] ?? 0;

        // === Process Rubber Blocks ===
        $rubber_blocks_input = $formData['rubber_blocks'] ?? [];
        $created_rubber_blocks = [];

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

        // Data Production Order
        $data_update = [
            'product_type_category' => $product_type_category,
            'production_order_name' => $production_order_name,
            'contract_id' => $contract_id,
            'contract_code' => $contract_code,
            'product_type_id' => $product_type_id,
            'required_quantity' => $required_quantity,
            'updated_at' => $now,
            'updated_by' => $this->auth_data['user_id'],
        ];

        $finished_goods_receipt = $this->finishedGoodsReceiptRepository->updateFinishedGoodsReceipt($finished_goods_receipt->getId(), $data_update);

        $action = 'update';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'finished_goods_receipt',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$finished_goods_receipt->getId(),
        );

        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['finished_goods_receipt'] = $finished_goods_receipt->jsonSerialize();

        // Include created rubber blocks in response
        if (!empty($created_rubber_blocks)) {
            $res_return['rubber_blocks'] = array_map(fn($block) => $block->jsonSerialize(), $created_rubber_blocks);
        }

        return $this->respondWithData($res_return);
        
    }
}
