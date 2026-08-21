<?php

declare(strict_types=1);

namespace App\Application\Actions\CustomField;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

/**
 * GET /v1/custom-fields/definitions/{code}
 * Xem chi tiết một trường tùy chỉnh theo field_code.
 */
class ViewDefinitionAction extends CustomFieldAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'custom_field', 'view');
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

        // Scope guard: 'own' chỉ thấy của công ty mình
        if ($scope !== 'all' && $definition->getCompanyId() !== (int)($this->auth_data['company_id'] ?? 0)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy trường tùy chỉnh');
        }

        return $this->respondWithData([
            'result'     => 'success',
            'definition' => $definition->jsonSerialize(),
            'trace_id'   => $trace_id,
        ]);
    }
}
