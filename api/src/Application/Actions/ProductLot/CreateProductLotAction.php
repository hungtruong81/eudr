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

class CreateProductLotAction extends ProductLotAction
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

        // Check permission to create product lots
        $scope = Utils::resolveScope($permissions, 'finished_goods_receipt', 'create');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $formData = $this->getFormData();

        $validator = new Validator($this->request);

        $validator->validate('factory_id', $formData['factory_id'] ?? null, 'required|integer|min:1');
        $validator->validate('grade_id', $formData['grade_id'] ?? null, 'required|integer|min:1');

        // Validate rubber_block_ids (required array)
        if (empty($formData['rubber_block_ids']) || !is_array($formData['rubber_block_ids'])) {
            throw new HttpBadRequestException($this->request, "rubber_block_ids phải là một mảng và không được rỗng");
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
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $factory_id = $cleanData['factory_id'];
        $grade_id = $cleanData['grade_id'];

        // Validate factory exists
        $existingFactory = $this->factoryRepository->findFactoryOfId($factory_id);
        if (empty($existingFactory)) {
            throw new HttpNotFoundException($this->request, "Nhà máy không tồn tại");
        }

        // Validate grade exists
        $existingGrade = $this->gradeRepository->findGradeOfId($grade_id);
        if (empty($existingGrade)) {
            throw new HttpNotFoundException($this->request, "Grade không tồn tại");
        }

        // Validate rubber block IDs
        $rubber_block_ids = array_map('intval', $formData['rubber_block_ids']);
        $rubber_block_ids = array_unique(array_filter($rubber_block_ids, fn($id) => $id > 0));

        if (empty($rubber_block_ids)) {
            throw new HttpBadRequestException($this->request, "rubber_block_ids không hợp lệ");
        }

        // Validate each rubber block exists and is available
        foreach ($rubber_block_ids as $index => $block_id) {
            $block = $this->rubberBlockRepository->findRubberBlockOfId($block_id);
            if (empty($block)) {
                throw new HttpNotFoundException($this->request, "Bành cao su ID $block_id không tồn tại");
            }
            if ($block->getStatus() !== 'available') {
                throw new HttpBadRequestException($this->request, "Bành cao su ID $block_id không ở trạng thái khả dụng (hiện tại: " . $block->getStatus() . ")");
            }
        }

        // Generate product lot code
        $product_lot_code = $this->productLotRepository->generateCode();

        $now = date('Y-m-d H:i:s', time());

        $data = [
            'product_lot_code' => $product_lot_code,
            'grade_id' => $grade_id,
            'grade' => $existingGrade->getName(),
            'factory_id' => $factory_id,
            'owner_company_id' => (int)$this->auth_data['company_id'],
            'owner_id' => (int)$this->auth_data['user_id'],
            'production_date_from' => null,
            'production_date_to' => null,
            'total_blocks' => 0,
            'total_weight' => 0,
            'status' => 'confirmed',
            'confirmed_at' => $now,
        ];

        $productLot = $this->productLotRepository->createProductLotWithItems($data, $rubber_block_ids);
        if (empty($productLot)) {
            throw new HttpBadRequestException($this->request, "Tạo lô hàng thất bại");
        }

        // Get created items
        $items = $this->productLotRepository->getProductLotItems($productLot->getId());

        $action = 'create';
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
