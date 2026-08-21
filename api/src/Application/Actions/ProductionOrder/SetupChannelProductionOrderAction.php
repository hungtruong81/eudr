<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductionOrder;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class SetupChannelProductionOrderAction extends ProductionOrderAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);

        $orderScope = Utils::resolveScope($permissions, 'production_order', 'update');
        if (empty($orderScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền cấu hình lệnh sản xuất');
        }

        $code = addslashes(trim((string)$this->resolveArg('code')));

        $order = $this->productionOrderRepository->findProductionOrderOfCodeWithPermission(
            $code,
            (int)$this->auth_data['user_id'],
            (string)$orderScope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($order)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy lệnh sản xuất');
        }

        if (!in_array($order->getStatus(), ['draft', 'approved'], true)) {
            throw new HttpBadRequestException($this->request, 'Lệnh sản xuất không ở trạng thái cho phép cấu hình (draft hoặc approved)');
        }

        $formData = $this->getFormData();
        $channels = $formData['channels'] ?? null;
        if (!is_array($channels) || empty($channels)) {
            throw new HttpBadRequestException($this->request, 'Trường channels là bắt buộc và phải là mảng có ít nhất 1 phần tử');
        }

        $setups = [];
        $processedChannelIds = [];

        try {
            foreach ($channels as $index => $channelItem) {
                if (!is_array($channelItem)) {
                    throw new HttpBadRequestException($this->request, "channels[$index] phải là object");
                }

                $validator = new Validator($this->request);
                $validator->validate('channel_id', $channelItem['channel_id'] ?? null, 'required|integer|min:1');
                $validator->validate('planned_volume_kg', $channelItem['planned_volume_kg'] ?? null, 'required|numeric|min:0');
                $validator->validate('coagulation_agent_type', $channelItem['coagulation_agent_type'] ?? null, 'string');
                $validator->validate('coagulation_agent_volume', $channelItem['coagulation_agent_volume'] ?? null, 'string');
                $validator->validate('started_at', $channelItem['started_at'] ?? null, 'string');
                $validator->validate('ended_at', $channelItem['ended_at'] ?? null, 'string');
                $validator->validate('notes', $channelItem['notes'] ?? null, 'string');

                if ($validator->hasErrors()) {
                    $errorMessages = [];
                    foreach ($validator->getErrors() as $fieldErrors) {
                        $errorMessages[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
                    }
                    throw new HttpBadRequestException($this->request, 'Lỗi channels[' . $index . ']: ' . implode('; ', $errorMessages));
                }

                $cleanItem = $validator->sanitize($channelItem, [
                    'channel_id'               => 'integer',
                    'planned_volume_kg'        => 'float',
                    'coagulation_agent_type'   => 'string',
                    'coagulation_agent_volume' => 'string',
                    'started_at'               => 'string',
                    'ended_at'                 => 'string',
                    'notes'                    => 'string',
                ]);

                $startedAt = !empty($cleanItem['started_at']) ? trim((string)$cleanItem['started_at']) : null;
                $endedAt = !empty($cleanItem['ended_at']) ? trim((string)$cleanItem['ended_at']) : null;
                if ($startedAt !== null && !Utils::isValidDateTime($startedAt)) {
                    throw new HttpBadRequestException($this->request, 'Lỗi channels[' . $index . ']: started_at không đúng định dạng Y-m-d H:i:s');
                }
                if ($endedAt !== null && !Utils::isValidDateTime($endedAt)) {
                    throw new HttpBadRequestException($this->request, 'Lỗi channels[' . $index . ']: ended_at không đúng định dạng Y-m-d H:i:s');
                }
                if ($startedAt !== null && $endedAt !== null && strtotime($startedAt) > strtotime($endedAt)) {
                    throw new HttpBadRequestException($this->request, 'Lỗi channels[' . $index . ']: started_at phải nhỏ hơn hoặc bằng ended_at');
                }

                $channelId = (int)$cleanItem['channel_id'];
                if (isset($processedChannelIds[$channelId])) {
                    throw new HttpBadRequestException($this->request, 'Trùng channel_id trong payload: ' . $channelId);
                }
                $processedChannelIds[$channelId] = true;

                $setups = $this->productionOrderRepository->setupChannel(
                    (int)$order->getId(),
                    $channelId,
                    (float)$cleanItem['planned_volume_kg'],
                    $cleanItem['coagulation_agent_type'] ?? null,
                    $cleanItem['coagulation_agent_volume'] ?? null,
                    $startedAt,
                    $endedAt,
                    $cleanItem['notes'] ?? null,
                    (int)($this->auth_data['company_id'] ?? 0),
                    (int)$order->getFactoryId(),
                    (int)$this->auth_data['user_id']
                );
            }
        } catch (\Exception $e) {
            if ($e instanceof HttpBadRequestException) {
                throw $e;
            }
            throw new HttpNotFoundException($this->request, $e->getMessage());
        }

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id'     => $trace_id,
            'log_type'     => 'production_order',
            'action'       => 'setup_channel',
            'user_id'      => (string)$this->auth_data['user_id'],
            'extra_1'      => (string)$order->getId(),
            'extra_2'      => implode(',', array_keys($processedChannelIds)),
        ];

        Utils::save_log($this->logger, $log);

        return $this->respondWithData([
            'result'              => 'success',
            'trace_id'            => $trace_id,
            'production_order_id' => $order->getId(),
            'channel_setups'      => $setups,
        ]);
    }
}
