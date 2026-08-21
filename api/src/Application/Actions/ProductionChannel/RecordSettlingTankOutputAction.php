<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionChannel;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class RecordSettlingTankOutputAction extends ProductionChannelAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $authUserId = (int)$this->auth_data['user_id'];
        $authCompanyId = (int)($this->auth_data['company_id'] ?? 0);

        $permissions = $this->userRepository->getUserPermissions($authUserId);

        $channelCreateScope = Utils::resolveScope($permissions, 'production_channel', 'create');
        if (empty($channelCreateScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền ghi nhận dữ liệu lắng đọng');
        }

        $productionOrderViewScope = Utils::resolveScope($permissions, 'production_order', 'view');
        if (empty($productionOrderViewScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền xem lệnh sản xuất');
        }

        $tankViewScope = Utils::resolveScope($permissions, 'raw_material_tank', 'view');
        if (empty($tankViewScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền xem bồn nguyên liệu thô');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);
        $validator->validate('production_order_code', $formData['production_order_code'] ?? null, 'required|string|max:30');
        $validator->validate('raw_material_tank_code', $formData['raw_material_tank_code'] ?? null, 'required|string|max:30');
        $validator->validate('settling_tank_code', $formData['settling_tank_code'] ?? null, 'required|string|max:30');
        $validator->validate('input_latex_kg', $formData['input_latex_kg'] ?? null, 'required|numeric|min:0.001');
        $validator->validate('output_latex_kg', $formData['output_latex_kg'] ?? null, 'required|numeric|min:0');
        $validator->validate('input_ph', $formData['input_ph'] ?? null, 'numeric|min:0');
        $validator->validate('output_ph', $formData['output_ph'] ?? null, 'numeric|min:0');
        $validator->validate('started_at', $formData['started_at'] ?? null, 'string');
        $validator->validate('ended_at', $formData['ended_at'] ?? null, 'string');
        $validator->validate('settling_duration_hours', $formData['settling_duration_hours'] ?? 0, 'integer|min:0');
        $validator->validate('notes', $formData['notes'] ?? null, 'string');

        if ($validator->hasErrors()) {
            $errorMessages = [];
            $errors = $validator->getErrors();
            if (is_array($errors)) {
                foreach ($errors as $fieldErrors) {
                    if (is_array($fieldErrors)) {
                        $errorMessages[] = implode(', ', $fieldErrors);
                    } else {
                        $errorMessages[] = (string)$fieldErrors;
                    }
                }
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errorMessages));
        }

        $cleanData = $validator->sanitize($formData, [
            'production_order_code' => 'string',
            'raw_material_tank_code' => 'string',
            'settling_tank_code' => 'string',
            'input_latex_kg' => 'float',
            'output_latex_kg' => 'float',
            'input_ph' => 'float',
            'output_ph' => 'float',
            'started_at' => 'string',
            'ended_at' => 'string',
            'settling_duration_hours' => 'integer',
            'notes' => 'string',
        ]);

        $startedAt = !empty($cleanData['started_at']) ? trim((string)$cleanData['started_at']) : null;
        $endedAt = !empty($cleanData['ended_at']) ? trim((string)$cleanData['ended_at']) : null;
        if ($startedAt !== null && !Utils::isValidDateTime($startedAt)) {
            throw new HttpBadRequestException($this->request, 'started_at không đúng định dạng Y-m-d H:i:s');
        }
        if ($endedAt !== null && !Utils::isValidDateTime($endedAt)) {
            throw new HttpBadRequestException($this->request, 'ended_at không đúng định dạng Y-m-d H:i:s');
        }
        if ($startedAt !== null && $endedAt !== null && strtotime($startedAt) > strtotime($endedAt)) {
            throw new HttpBadRequestException($this->request, 'started_at phải nhỏ hơn hoặc bằng ended_at');
        }

        $productionOrderCode = trim((string)$cleanData['production_order_code']);
        $rawTankCode = trim((string)$cleanData['raw_material_tank_code']);
        $settlingTankCode = trim((string)$cleanData['settling_tank_code']);
        $inputLatexKg = (float)$cleanData['input_latex_kg'];
        $outputLatexKg = (float)$cleanData['output_latex_kg'];

        if ($outputLatexKg > $inputLatexKg) {
            throw new HttpBadRequestException($this->request, 'output_latex_kg không được lớn hơn input_latex_kg');
        }

        $productionOrder = $this->productionOrderRepository->findProductionOrderOfCodeWithPermission(
            $productionOrderCode,
            $authUserId,
            (string)$productionOrderViewScope,
            $authCompanyId
        );
        if (empty($productionOrder)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy lệnh sản xuất');
        }

        $productionOrderData = $productionOrder->jsonSerialize();
        $productionOrderId = (int)($productionOrderData['production_order_id'] ?? 0);
        $productionOrderFactoryId = (int)($productionOrderData['factory_id'] ?? 0);
        $productionOrderCompanyId = (int)($productionOrderData['company_id'] ?? 0);

        $rawTank = $this->rawMaterialTankRepository->findRawMaterialTankOfCodeWithPermission(
            $rawTankCode,
            $authUserId,
            (string)$tankViewScope,
            $authCompanyId
        );
        if (empty($rawTank)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy bồn nguyên liệu thô');
        }

        $rawTankData = $rawTank->jsonSerialize();
        $rawTankId = (int)($rawTankData['raw_material_tank_id'] ?? 0);
        $rawTankFactoryId = (int)($rawTankData['factory_id'] ?? 0);

        if ($rawTankFactoryId !== $productionOrderFactoryId) {
            throw new HttpBadRequestException($this->request, 'Bồn nguyên liệu và lệnh sản xuất không cùng nhà máy');
        }

        $persistResult = $this->productionChannelRepository->recordSettlingTankOutput([
            'production_order_id' => $productionOrderId,
            'company_id' => $productionOrderCompanyId,
            'factory_id' => $productionOrderFactoryId,
            'raw_tank_id' => $rawTankId,
            'settling_tank_code' => $settlingTankCode,
            'input_latex_kg' => $inputLatexKg,
            'output_latex_kg' => $outputLatexKg,
            'input_ph' => $cleanData['input_ph'] ?? null,
            'output_ph' => $cleanData['output_ph'] ?? null,
            'settling_duration_hours' => $cleanData['settling_duration_hours'] ?? 0,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'notes' => !empty($cleanData['notes']) ? $cleanData['notes'] : null,
            'created_by' => $authUserId,
        ]);

        if (empty($persistResult)) {
            throw new HttpBadRequestException($this->request, 'Ghi nhận khối lượng sau lắng thất bại');
        }

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'production_settling_tank_run',
            'action' => 'record_output',
            'user_id' => (string)$authUserId,
            'extra_1' => (string)$productionOrderId,
            'extra_2' => (string)($persistResult['settling_tank_run_id'] ?? 0),
        ];

        Utils::save_log($this->logger, $log);

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'data' => [
                'production_order_code' => $productionOrderCode,
                'raw_material_tank_code' => $rawTankCode,
                'settling_tank_code' => $settlingTankCode,
                'settling_tank_run' => $persistResult,
            ],
        ]);
    }
}
