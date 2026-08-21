<?php

declare(strict_types=1);

namespace App\Application\Actions\RawMaterialRelease;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;


class CreateRawMaterialReleaseAction extends RawMaterialReleaseAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        // Check authentication
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);

        // Check permission to create raw material releases
        $scope = Utils::resolveScope($permissions, 'raw_material_release', 'create');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $formData = $this->getFormData();

        $validator = new Validator($this->request);

        $validator->validate('material_release_name', $formData['material_release_name'] ?? null, 'required|string');
        $validator->validate('material_release_code', $formData['material_release_code'] ?? null, 'required|string|max:30');
        $validator->validate('production_order_id', $formData['production_order_id'] ?? null, 'required|integer');
        $validator->validate('raw_material_tanks', $formData['raw_material_tanks'] ?? null, 'required|array');

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
            'material_release_name' => 'string',
            'material_release_code' => 'string',
            'production_order_id' => 'integer',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $material_release_name = $cleanData['material_release_name'];
        $material_release_code = $cleanData['material_release_code'];
        $production_order_id = $cleanData['production_order_id'];
        $raw_material_tanks = $formData['raw_material_tanks'] ?? [];

        // Validate production order
        $existingProductionOrder = $this->productionOrderRepository->findProductionOrderOfId($production_order_id);
        if (empty($existingProductionOrder)) {
            throw new HttpNotFoundException($this->request, "Phiếu sản xuất không tồn tại");
        }

        if ($existingProductionOrder->getStatus() !== 'approved') {
            throw new HttpBadRequestException($this->request, "Phiếu sản xuất chưa được duyệt");
        }

        // Validate raw material tanks
        $total_requested_weight = 0;
        foreach ($raw_material_tanks as $tank) {
            if (empty($tank['tank_id']) || empty($tank['rubber_type']) || empty($tank['weight_requested'])) {
                throw new HttpBadRequestException($this->request, "Dữ liệu bồn nguyên liệu thô không hợp lệ");
            }
            if (!is_numeric($tank['weight_requested']) || $tank['weight_requested'] <= 0) {
                throw new HttpBadRequestException($this->request, "Khối lượng yêu cầu không hợp lệ cho bồn nguyên liệu thô: " . $tank['tank_id']);
            }
            if (!is_string($tank['rubber_type']) || !in_array($tank['rubber_type'], ['latex', 'scrap_rubber'])) {
                throw new HttpBadRequestException($this->request, "Loại cao su không hợp lệ cho bồn nguyên liệu thô: " . $tank['tank_id']);
            }
            // Check uniqueness of tank_id in the request
            $tank_ids = array_column($raw_material_tanks, 'tank_id');
            if (count($tank_ids) !== count(array_unique($tank_ids))) {
                throw new HttpBadRequestException($this->request, "Bồn nguyên liệu thô bị trùng lặp trong yêu cầu: " . $tank['tank_id']);
            }

            $existingTank = $this->rawMaterialTankRepository->findRawMaterialTankOfId($tank['tank_id']);
            if (empty($existingTank)) {
                throw new HttpBadRequestException($this->request, "Bồn nguyên liệu thô không tồn tại: " . $tank['tank_id']);
            }
            // Validate rubber type matches
            if ($existingTank->getTankType() !== $tank['rubber_type']) {
                throw new HttpBadRequestException($this->request, "Loại cao su không khớp với bồn nguyên liệu thô: " . $existingTank->getName());
            }
            // Check volume availability
            $weight_requested = floatval($tank['weight_requested']);
            if ($existingTank->getCurrentVolume() < $weight_requested) {
                throw new HttpBadRequestException($this->request, "Bồn nguyên liệu thô không đủ khối lượng yêu cầu: " . $existingTank->getName());
            }
            $total_requested_weight += $weight_requested;
        }

        // Validate unique raw material release code
        $existingRawMaterialRelease = $this->rawMaterialReleaseRepository->findRawMaterialReleaseOfCode($material_release_code);
        if (!empty($existingRawMaterialRelease)) {
            $material_release_code = $this->rawMaterialReleaseRepository->generateCode();
        }

        // Data insert
        $data_update = [
            'company_id' => $this->auth_data['company_id'] ?? 0,
            'material_release_code' => $material_release_code,
            'material_release_name' => $material_release_name,
            'production_order_id' => $production_order_id,
            'total_requested_weight' => $total_requested_weight,
            'status' => 'in_progress', // Đang xuất nguyên liệu (Đang xuất kho)
            'created_at' => date('Y-m-d H:i:s', time()),
            'created_by' => (int)$this->auth_data['user_id'],
            'raw_material_tanks' => $raw_material_tanks,
        ];

        $materialRelease = $this->rawMaterialReleaseRepository->createRawMaterialRelease($data_update);

        $action = 'create';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'raw_material_release',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$materialRelease->getId(),
        );

        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['raw_material_release'] = $materialRelease->jsonSerialize();

        return $this->respondWithData($res_return);
        
    }
}
