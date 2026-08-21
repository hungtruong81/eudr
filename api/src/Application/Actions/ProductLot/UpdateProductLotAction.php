<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductLot;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class UpdateProductLotAction extends ProductLotAction
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

        // Check permission to update product lots
        $scope = Utils::resolveScope($permissions, 'finished_goods_receipt', 'update');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $product_lot_code = addslashes(trim((string)$this->resolveArg('code')));

        $product_lot = $this->productLotRepository->findProductLotOfCode($product_lot_code);
        if (empty($product_lot)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy lô hàng");
        }

        // Only allow update on draft lots
        if ($product_lot->getStatus() !== 'draft') {
            throw new HttpBadRequestException($this->request, "Chỉ có thể cập nhật lô hàng ở trạng thái nháp");
        }

        $formData = $this->getFormData();

        $validator = new Validator($this->request);

        $validator->validate('factory_id', $formData['factory_id'] ?? null, 'integer|min:1');
        $validator->validate('grade_id', $formData['grade_id'] ?? null, 'required|integer|min:1');
        $validator->validate('status', $formData['status'] ?? null, 'string|in:draft,confirmed,cancelled');

        // Validate rubber_block_ids if provided
        if (isset($formData['rubber_block_ids']) && !is_array($formData['rubber_block_ids'])) {
            throw new HttpBadRequestException($this->request, "rubber_block_ids phải là một mảng");
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
            'factory_id' => 'integer',
            'grade_id' => 'integer',
            'status' => 'string',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        // Validate factory if provided
        $factory_id = $cleanData['factory_id'] ?? null;
        if (!empty($factory_id)) {
            $existingFactory = $this->factoryRepository->findFactoryOfId($factory_id);
            if (empty($existingFactory)) {
                throw new HttpNotFoundException($this->request, "Nhà máy không tồn tại");
            }
        }

        // Validate grade if provided
        $grade_id = $cleanData['grade_id'] ?? null;
        if (!empty($grade_id)) {
            $existingGrade = $this->gradeRepository->findGradeOfId($grade_id);
            if (empty($existingGrade)) {
                throw new HttpNotFoundException($this->request, "Grade không tồn tại");
            }
        }


        $now = date('Y-m-d H:i:s', time());

        // Build update data
        $data_update = [];
        if (!empty($factory_id)) {
            $data_update['factory_id'] = $factory_id;
        }
        if (!empty($existingGrade)) {
            $data_update['grade_id'] = $grade_id;
            $data_update['grade'] = $existingGrade->getName();
        }

        // Handle status change
        $new_status = $cleanData['status'] ?? null;
        if (!empty($new_status)) {
            $data_update['status'] = $new_status;
            if ($new_status === 'confirmed') {
                $data_update['confirmed_at'] = $now;
            }
        }

        // If rubber_block_ids provided, replace all items
        if (isset($formData['rubber_block_ids'])) {
            $rubber_block_ids = array_map('intval', $formData['rubber_block_ids']);
            $rubber_block_ids = array_unique(array_filter($rubber_block_ids, fn($id) => $id > 0));

            if (empty($rubber_block_ids)) {
                throw new HttpBadRequestException($this->request, "rubber_block_ids không hợp lệ");
            }

            // Validate each rubber block exists and is available (or already in this lot)
            $current_items = $this->productLotRepository->getProductLotItems($product_lot->getId());
            $current_block_ids = array_map(fn($item) => $item->getRubberBlockId(), $current_items);

            foreach ($rubber_block_ids as $block_id) {
                $block = $this->rubberBlockRepository->findRubberBlockOfId($block_id);
                if (empty($block)) {
                    throw new HttpNotFoundException($this->request, "Bành cao su ID $block_id không tồn tại");
                }
                // Allow blocks already in this lot or blocks that are available
                if (!in_array($block_id, $current_block_ids) && $block->getStatus() !== 'available') {
                    throw new HttpBadRequestException($this->request, "Bành cao su ID $block_id không ở trạng thái khả dụng (hiện tại: " . $block->getStatus() . ")");
                }
            }

            $productLot = $this->productLotRepository->updateProductLotWithItems($product_lot->getId(), $data_update, $rubber_block_ids);
        } else {
            // Update lot data only (no item changes)
            if (!empty($data_update)) {
                $productLot = $this->productLotRepository->updateProductLot($product_lot->getId(), $data_update);
            } else {
                $productLot = $product_lot;
            }
        }

        if (empty($productLot)) {
            throw new HttpBadRequestException($this->request, "Cập nhật lô hàng thất bại");
        }

        // Get updated items
        $items = $this->productLotRepository->getProductLotItems($productLot->getId());

        $action = 'update';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'product_lot',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$productLot->getId(),
        );

        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['data'] = $productLot->jsonSerialize();
        $res_return['data']['items'] = array_map(fn($item) => $item->jsonSerialize(), $items);

        return $this->respondWithData($res_return);
    }
}
