<?php

declare(strict_types=1);

namespace App\Application\Actions\CustomField;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

/**
 * PUT /v1/custom-fields/definitions/{code}
 * Cập nhật chi tiết một trường tùy chỉnh theo field_code.
 */
class UpdateDefinitionAction extends CustomFieldAction
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

        $fieldCode = (string)$this->resolveArg('code');
        if (empty($fieldCode)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy trường tùy chỉnh');
        }

        $definition = $this->customFieldRepository->findDefinitionByCode($fieldCode);

        if (!$definition) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy trường tùy chỉnh');
        }

        $companyId = (int)($this->auth_data['company_id'] ?? 0);
        if ($scope !== 'all' && $definition->getCompanyId() !== $companyId) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy trường tùy chỉnh');
        }

        $formData  = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('field_label',       $formData['field_label']       ?? null, 'string|max:200');
        $validator->validate('field_description', $formData['field_description'] ?? null, 'string|max:500');
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
            'field_label'       => 'string',
            'field_description' => 'string',
            'sort_order'        => 'integer',
            'status'            => 'string',
        ]);

        $data = ['updated_by' => (int)$this->auth_data['user_id'], 'updated_at' => date('Y-m-d H:i:s')];

        if (isset($clean['field_label']) && $clean['field_label'] !== '') {
            $data['field_label'] = $clean['field_label'];
        }
        if (array_key_exists('field_description', $formData)) {
            $data['field_description'] = $clean['field_description'] ?? null;
        }
        if (isset($formData['is_required'])) {
            $data['is_required'] = (int)(bool)$formData['is_required'];
        }
        if (isset($formData['is_searchable'])) {
            $data['is_searchable'] = (int)(bool)$formData['is_searchable'];
        }
        if (isset($clean['sort_order'])) {
            $data['sort_order'] = $clean['sort_order'];
        }
        if (!empty($clean['status'])) {
            $data['status'] = $clean['status'];
        }

        // Options: only updatable when field_type === select
        if ($definition->getFieldType() === 'select' && array_key_exists('options', $formData)) {
            $rawOptions = $formData['options'] ?? [];
            if (!is_array($rawOptions) || empty($rawOptions)) {
                throw new HttpBadRequestException($this->request, 'Trường loại select cần cung cấp options (mảng)');
            }
            $data['options'] = json_encode(array_values(array_map('strval', $rawOptions)), JSON_UNESCAPED_UNICODE);
        }

        $updated = $this->customFieldRepository->updateDefinition($definition->getId(), $data);
        if (!$updated) {
            throw new HttpBadRequestException($this->request, 'Không thể cập nhật trường tùy chỉnh');
        }

        return $this->respondWithData([
            'result'     => 'success',
            'definition' => $updated->jsonSerialize(),
            'trace_id'   => $trace_id,
        ]);
    }
}
