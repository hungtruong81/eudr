<?php

declare(strict_types=1);

namespace App\Application\Actions\General;

use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpUnauthorizedException;

class UpsertSettingsAction extends GeneralAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $companyId = (int)($this->auth_data['company_id'] ?? 0);
        if ($companyId <= 0) {
            throw new HttpBadRequestException($this->request, 'Không xác định công ty hiện tại');
        }

        $formData = $this->getFormData();

        // Bulk mode: {"settings": [{"setting_code":"...", "comment":"...", "value":"..."}, ...]}
        // Backward-compatible single mode: {"setting_code":"...", "comment":"...", "value":"..."}
        $items = $formData['settings'] ?? null;
        if (!is_array($items)) {
            $items = [[
                'setting_code' => $formData['setting_code'] ?? null,
                'comment' => $formData['comment'] ?? null,
                'value' => $formData['value'] ?? null,
            ]];
        }

        if (empty($items)) {
            throw new HttpBadRequestException($this->request, 'settings phải là mảng không rỗng');
        }

        $savedItems = [];
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                throw new HttpBadRequestException($this->request, "settings[$index] không hợp lệ");
            }

            // Normalize scalar values so string rules behave consistently for JSON numeric input.
            if (isset($item['setting_code']) && is_scalar($item['setting_code']) && !is_string($item['setting_code'])) {
                $item['setting_code'] = (string)$item['setting_code'];
            }
            if (isset($item['comment']) && is_scalar($item['comment']) && !is_string($item['comment'])) {
                $item['comment'] = (string)$item['comment'];
            }
            if (isset($item['value']) && is_scalar($item['value']) && !is_string($item['value'])) {
                $item['value'] = (string)$item['value'];
            }

            $itemValidator = new Validator($this->request);
            $itemValidator->validate('setting_code', $item['setting_code'] ?? null, 'required|string|max:50');
            $itemValidator->validate('comment', $item['comment'] ?? null, 'string|max:255');
            $itemValidator->validate('value', $item['value'] ?? null, 'required|string|max:255');

            if ($itemValidator->hasErrors()) {
                $errors = [];
                foreach ((array)$itemValidator->getErrors() as $fieldErrors) {
                    $errors[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
                }
                throw new HttpBadRequestException($this->request, "settings[$index]: " . implode('; ', $errors));
            }

            $cleanData = $itemValidator->sanitize($item, [
                'setting_code' => 'string',
                'comment' => 'string',
                'value' => 'string',
            ]);

            $settingCode = trim((string)$cleanData['setting_code']);
            $comment = isset($cleanData['comment']) ? trim((string)$cleanData['comment']) : '';
            $value = trim((string)$cleanData['value']);

            if ($settingCode === '') {
                throw new HttpBadRequestException($this->request, "settings[$index]: setting_code không hợp lệ");
            }

            $payload = [
                'comment' => $comment,
                'value' => $value,
                'time' => time(),
                'active' => 1,
            ];

            $this->db->where('setting_code', $settingCode);
            $this->db->where('company_id', $companyId);
            $existing = $this->db->getOne('eudr_settings', 'setting_id,setting_code,comment,value,company_id,active');

            if (!empty($existing)) {
                $this->db->where('setting_id', (int)$existing['setting_id']);
                $ok = $this->db->update('eudr_settings', $payload);
                if (!$ok) {
                    throw new HttpBadRequestException($this->request, "settings[$index]: không thể cập nhật cấu hình");
                }
            } else {
                $insertData = [
                    'setting_code' => $settingCode,
                    'comment' => $comment,
                    'value' => $value,
                    'time' => time(),
                    'company_id' => $companyId,
                    'active' => 1,
                ];
                $insertId = $this->db->insert('eudr_settings', $insertData);
                if (empty($insertId)) {
                    throw new HttpBadRequestException($this->request, "settings[$index]: không thể tạo cấu hình");
                }
            }

            $this->db->where('setting_code', $settingCode);
            $this->db->where('company_id', $companyId);
            $saved = $this->db->getOne('eudr_settings', 'setting_id,setting_code,comment,value,company_id,active,time');
            if (!empty($saved)) {
                $savedItems[] = $saved;
            }
        }

        return $this->respondWithData([
            'result' => 'success',
            'count' => count($savedItems),
            'data' => $savedItems,
        ]);
    }
}
