<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionChannel;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class PourRawMaterialToChannelsAction extends ProductionChannelAction
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
            throw new HttpForbiddenException($this->request, 'Thiếu quyền đổ mủ vào mương');
        }

        $channelViewScope = Utils::resolveScope($permissions, 'production_channel', 'view');
        if (empty($channelViewScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền xem mương chứa mủ');
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
        $validator->validate('channels', $formData['channels'] ?? null, 'required|array');
        $validator->validate('input_quality_note', $formData['input_quality_note'] ?? null, 'string|max:255');
        $validator->validate('input_ph', $formData['input_ph'] ?? null, 'numeric|min:0');
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

        $sanitizeRules = [
            'production_order_code' => 'string',
            'raw_material_tank_code' => 'string',
            'input_quality_note' => 'string',
            'input_ph' => 'float',
            'notes' => 'string',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $productionOrderCode = trim((string)$cleanData['production_order_code']);
        $rawTankCode = trim((string)$cleanData['raw_material_tank_code']);
        $inputQualityNote = trim((string)($cleanData['input_quality_note'] ?? ''));
        $inputPh = array_key_exists('input_ph', $cleanData) ? (float)$cleanData['input_ph'] : null;
        $notes = trim((string)($cleanData['notes'] ?? ''));

        $channelsInput = $formData['channels'] ?? [];
        if (!is_array($channelsInput) || count($channelsInput) === 0) {
            throw new HttpBadRequestException($this->request, 'Danh sách mương chứa không hợp lệ');
        }

        $channelCodes = [];
        $channelsByCode = [];
        foreach ($channelsInput as $item) {
            if (!is_array($item) || empty($item['channel_code']) || !is_string($item['channel_code'])) {
                throw new HttpBadRequestException($this->request, 'Mỗi mương phải có channel_code');
            }
            $channelCode = trim((string)$item['channel_code']);
            if ($channelCode === '') {
                throw new HttpBadRequestException($this->request, 'channel_code không được rỗng');
            }
            $channelCodes[] = $channelCode;
            $channelsByCode[$channelCode] = $item;
        }

        if (count($channelCodes) !== count(array_unique($channelCodes))) {
            throw new HttpBadRequestException($this->request, 'Danh sách channel_code bị trùng lặp');
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
        $rawTankCurrentVolume = (float)($rawTankData['current_volume'] ?? 0);
        $rawTankType = (string)($rawTankData['tank_type'] ?? '');

        if ($productionOrderFactoryId !== $rawTankFactoryId) {
            throw new HttpBadRequestException($this->request, 'Bồn nguyên liệu và lệnh sản xuất không cùng nhà máy');
        }

        $channels = [];
        $totalPourWeight = 0.0;

        foreach ($channelCodes as $channelCode) {
            $channel = $this->productionChannelRepository->findProductionChannelOfCodeWithPermission(
                $channelCode,
                $authUserId,
                (string)$channelViewScope,
                $authCompanyId
            );
            if (empty($channel)) {
                throw new HttpNotFoundException($this->request, 'Không tìm thấy mương: ' . $channelCode);
            }

            $channelData = $channel->jsonSerialize();
            $channelId = (int)($channelData['channel_id'] ?? 0);
            $channelFactoryId = (int)($channelData['factory_id'] ?? 0);
            $channelCompanyId = (int)($channelData['company_id'] ?? 0);
            $channelStatus = (string)($channelData['status'] ?? '');
            $channelCapacity = (float)($channelData['capacity_kg'] ?? 0);
            $channelPayload = $channelsByCode[$channelCode] ?? [];
            $channelInputLatexKg = isset($channelPayload['input_latex_kg']) && $channelPayload['input_latex_kg'] !== ''
                ? (float)$channelPayload['input_latex_kg']
                : null;

            if ($channelFactoryId !== $productionOrderFactoryId) {
                throw new HttpBadRequestException($this->request, 'Mương ' . $channelCode . ' không thuộc cùng nhà máy của lệnh sản xuất');
            }
            if ($channelCompanyId !== $productionOrderCompanyId) {
                throw new HttpBadRequestException($this->request, 'Mương ' . $channelCode . ' không thuộc cùng công ty');
            }
            if ($channelStatus !== 'available') {
                throw new HttpBadRequestException($this->request, 'Mương ' . $channelCode . ' không ở trạng thái available');
            }
            if ($channelCapacity <= 0) {
                throw new HttpBadRequestException($this->request, 'Mương ' . $channelCode . ' chưa có capacity_kg hợp lệ');
            }

            if ($channelInputLatexKg !== null && $channelInputLatexKg <= 0) {
                throw new HttpBadRequestException($this->request, 'input_latex_kg của mương ' . $channelCode . ' phải lớn hơn 0');
            }

            $channelWeight = $channelInputLatexKg ?? $channelCapacity;

            $totalPourWeight += $channelWeight;
            $channels[] = [
                'channel_id' => $channelId,
                'channel_code' => $channelCode,
                'channel_name' => (string)($channelData['channel_name'] ?? ''),
                'capacity_kg' => $channelCapacity,
                'input_latex_kg' => $channelWeight,
            ];
        }

        /*
        if ($rawTankCurrentVolume < $totalPourWeight) {
            throw new HttpBadRequestException(
                $this->request,
                'Bồn nguyên liệu không đủ khối lượng. Hiện có: ' . $rawTankCurrentVolume . ' kg, cần: ' . $totalPourWeight . ' kg'
            );
        }
        */
        $persistResult = $this->productionChannelRepository->pourRawMaterialToChannels([
            'production_order_id' => $productionOrderId,
            'company_id' => $productionOrderCompanyId,
            'factory_id' => $productionOrderFactoryId,
            'raw_tank_id' => $rawTankId,
            'raw_tank_type' => $rawTankType,
            'input_quality_note' => $inputQualityNote !== '' ? $inputQualityNote : null,
            'input_ph' => $inputPh,
            'notes' => $notes !== '' ? $notes : null,
            'created_by' => $authUserId,
            'channels' => $channels,
        ]);

        if (empty($persistResult)) {
            throw new HttpBadRequestException($this->request, 'Đổ mủ từ bồn sang mương thất bại');
        }

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'production_channel_run',
            'action' => 'pour_raw_material',
            'user_id' => (string)$authUserId,
            'extra_1' => (string)$productionOrderId,
            'extra_2' => (string)$rawTankId,
            'extra_3' => (string)count($persistResult['channel_runs'] ?? []),
        ];

        Utils::save_log($this->logger, $log);

        $res_return = ['result' => 'success'];
        $res_return['trace_id'] = $trace_id;
        $res_return['data'] = [
            'production_order_code' => $productionOrderCode,
            'raw_material_tank_code' => $rawTankCode,
            'total_output_kg' => $persistResult['total_output_kg'],
            'remaining_tank_volume' => $persistResult['remaining_tank_volume'],
            'channel_runs' => $persistResult['channel_runs'],
        ];

        return $this->respondWithData($res_return);
    }
}
