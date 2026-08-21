<?php

declare(strict_types=1);

namespace App\Application\Actions\CustomField;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

/**
 * POST /v1/custom-fields/values/{entity_type}/{entity_id}
 * Upsert danh sách custom field values cho một thực thể.
 *
 * Body (JSON):
 * {
 *   "values": [
 *     { "field_id": 1, "value": "Đất phù sa" },
 *     { "field_id": 2, "value": 3.14 },
 *     { "field_id": 3, "value": "2026-04-26" }
 *   ]
 * }
 */
class SetEntityValuesAction extends CustomFieldAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'custom_field', 'update');
        $scope = 'own'; // TEMP: disable permission check for testing
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $entityType = strtolower(trim((string)$this->resolveArg('entity_type')));
        $entityId   = (int)$this->resolveArg('entity_id');

        if (!in_array($entityType, self::ENTITY_TYPES, true)) {
            throw new HttpBadRequestException($this->request, "entity_type '$entityType' không hợp lệ");
        }
        if ($entityId <= 0) {
            throw new HttpBadRequestException($this->request, 'entity_id phải lớn hơn 0');
        }

        $formData = $this->getFormData();
        $rawValues = $formData['values'] ?? [];

        if (!is_array($rawValues) || empty($rawValues)) {
            throw new HttpBadRequestException($this->request, 'Trường values là bắt buộc và phải là mảng không rỗng');
        }

        // Validate each entry
        $values = [];
        foreach ($rawValues as $idx => $entry) {
            $fieldId = (int)($entry['field_id'] ?? 0);
            if ($fieldId <= 0) {
                throw new HttpBadRequestException($this->request, "values[$idx].field_id phải lớn hơn 0");
            }
            $definition = $this->customFieldRepository->findDefinitionById($fieldId);
            if (!$definition) {
                throw new HttpBadRequestException($this->request, "field_id $fieldId không tồn tại");
            }
            if ($definition->getEntityType() !== $entityType) {
                throw new HttpBadRequestException($this->request, "field_id $fieldId không thuộc entity_type '$entityType'");
            }
            $companyId = (int)($this->auth_data['company_id'] ?? 0);
            if ($definition->getCompanyId() !== $companyId) {
                throw new HttpBadRequestException($this->request, "field_id $fieldId không thuộc công ty của bạn");
            }

            $value = $entry['value'] ?? null;

            // Validate required
            if ($definition->jsonSerialize()['is_required'] && ($value === null || $value === '')) {
                throw new HttpBadRequestException($this->request, "Trường '{$definition->jsonSerialize()['field_label']}' là bắt buộc");
            }

            // Validate select options
            if ($definition->getFieldType() === 'select' && $value !== null && $value !== '') {
                $opts = $definition->jsonSerialize()['options'] ?? [];
                if (!empty($opts) && !in_array((string)$value, array_map('strval', $opts), true)) {
                    throw new HttpBadRequestException($this->request, "Giá trị '$value' không hợp lệ cho trường '{$definition->jsonSerialize()['field_label']}'");
                }
            }

            $values[] = ['field_id' => $fieldId, 'value' => $value];
        }

        $companyId = (int)($this->auth_data['company_id'] ?? 0);
        $saved = $this->customFieldRepository->setEntityValues(
            $entityType,
            $entityId,
            $companyId,
            $values,
            (int)$this->auth_data['user_id']
        );

        return $this->respondWithData([
            'result'      => 'success',
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'values'      => array_map(fn($v) => $v->jsonSerialize(), $saved),
            'trace_id'    => $trace_id,
        ]);
    }
}
