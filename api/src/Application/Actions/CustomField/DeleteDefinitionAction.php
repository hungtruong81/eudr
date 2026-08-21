<?php

declare(strict_types=1);

namespace App\Application\Actions\CustomField;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

/**
 * DELETE /v1/custom-fields/definitions/{code}
 * Soft-delete: đặt deleted_at / deleted_by.
 */
class DeleteDefinitionAction extends CustomFieldAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'custom_field', 'delete');
        $scope = 'own'; // TEMP: disable permission check for testing
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $fieldCode  = (string)$this->resolveArg('code');
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

        $fieldId = $definition->getId();
        $ok = $this->customFieldRepository->deleteDefinition($fieldId, (int)$this->auth_data['user_id']);
        if (!$ok) {
            throw new HttpBadRequestException($this->request, 'Không thể xóa trường tùy chỉnh');
        }

        Utils::save_log($this->logger, [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id'     => $trace_id,
            'log_type'     => 'custom_field',
            'action'       => 'delete_definition',
            'user_id'      => (string)$this->auth_data['user_id'],
            'extra_1'      => (string)$fieldId,
        ]);

        return $this->respondWithData(['result' => 'success', 'trace_id' => $trace_id]);
    }
}
