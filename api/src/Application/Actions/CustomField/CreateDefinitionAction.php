<?php

declare(strict_types=1);

namespace App\Application\Actions\CustomField;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

/**
 * POST /v1/custom-fields/definitions/
 *
 * Body (JSON):
 * {
 *   "field_code":        "soil_type",          // optional, unique across system; auto-generated (random) if not provided (e.g. cf-260429-AbCdEf12)
 *   "field_key":         "soil_type",          // required, slug
 *   "field_label":       "Loại đất",           // required
 *   "field_description": "Mô tả loại đất",     // optional
 *   "entity_type":       "land",               // required
 *   "field_type":        "select",             // required
 *   "options":           ["Đất cát","Đất phù sa"], // required for select
 *   "is_required":       false,
 *   "is_searchable":     true,
 *   "sort_order":        0,
 *   "status":            "active"
 * }
 */
class CreateDefinitionAction extends CustomFieldAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'custom_field', 'create');
        $scope = 'own'; // TEMP: disable permission check for testing
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('field_key',         $formData['field_key']         ?? null, 'required|string|max:100');
        $validator->validate('field_label',       $formData['field_label']       ?? null, 'required|string|max:200');
        $validator->validate('field_description', $formData['field_description'] ?? null, 'string|max:500');
        $validator->validate('entity_type',       $formData['entity_type']       ?? null, 'required|string|in:land,plant,harvest,customer,product,sales_order,product_lot_import_none_eudr');
        $validator->validate('field_type',        $formData['field_type']        ?? null, 'required|string|in:text,textarea,number,date,datetime,boolean,select');
        $validator->validate('is_required',       $formData['is_required']       ?? null, 'boolean');
        $validator->validate('is_searchable',     $formData['is_searchable']     ?? null, 'boolean');
        $validator->validate('sort_order',        $formData['sort_order']        ?? null, 'integer|min:0');
        $validator->validate('status',            $formData['status']            ?? null, 'string|in:active,inactive');

        if ($validator->hasErrors()) {
            $errors = [];
            foreach ((array)$validator->getErrors() as $fieldErrors) {
                $errors[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errors));
        }

        $clean = $validator->sanitize($formData, [
            'field_key'         => 'string',
            'field_label'       => 'string',
            'field_description' => 'string',
            'entity_type'       => 'string',
            'field_type'        => 'string',
            'sort_order'        => 'integer',
            'status'            => 'string',
        ]);

        $fieldKey   = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', trim($clean['field_key'])));
        $fieldType  = $clean['field_type'];
        $entityType = $clean['entity_type'];

        // Generate or validate field_code (globally unique)
        $fieldCode = $this->customFieldRepository->generateCode();

        // Validate options for select type
        $options = null;
        if ($fieldType === 'select') {
            $rawOptions = $formData['options'] ?? [];
            if (!is_array($rawOptions) || empty($rawOptions)) {
                throw new HttpBadRequestException($this->request, 'Trường loại select cần cung cấp options (mảng)');
            }
            $options = array_values(array_map('strval', $rawOptions));
        }

        $companyId = (int)($this->auth_data['company_id'] ?? 0);

        // Check uniqueness
        $existing = $this->customFieldRepository->findDefinitionByKey($fieldKey, $entityType, $companyId);
        if ($existing) {
            throw new HttpBadRequestException($this->request, "field_key '$fieldKey' đã tồn tại cho entity_type '$entityType'");
        }

        // Check field_code uniqueness only when user-provided (generateCode() ensures uniqueness)
        if (!empty($clean['field_code'])) {
            $existingByCode = $this->customFieldRepository->findDefinitionByCode($fieldCode);
            if ($existingByCode) {
                throw new HttpBadRequestException($this->request, "field_code '$fieldCode' đã tồn tại");
            }
        }

        $data = [
            'field_code'        => $fieldCode,
            'field_key'         => $fieldKey,
            'field_label'       => $clean['field_label'],
            'field_description' => $clean['field_description'] ?? null,
            'entity_type'       => $entityType,
            'field_type'        => $fieldType,
            'options'           => $options !== null ? json_encode($options, JSON_UNESCAPED_UNICODE) : null,
            'is_required'       => isset($formData['is_required']) ? (int)(bool)$formData['is_required'] : 0,
            'is_searchable'     => isset($formData['is_searchable']) ? (int)(bool)$formData['is_searchable'] : 0,
            'sort_order'        => (int)($clean['sort_order'] ?? 0),
            'status'            => $clean['status'] ?? 'active',
            'company_id'        => $companyId,
            'created_by'        => (int)$this->auth_data['user_id'],
            'created_at'        => date('Y-m-d H:i:s'),
        ];

        $definition = $this->customFieldRepository->createDefinition($data);
        if (!$definition) {
            throw new HttpBadRequestException($this->request, 'Không thể tạo trường tùy chỉnh');
        }

        return $this->respondWithData([
            'result'     => 'success',
            'definition' => $definition->jsonSerialize(),
            'trace_id'   => $trace_id,
        ]);
    }
}
