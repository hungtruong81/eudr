<?php

declare(strict_types=1);

namespace App\Application\Actions\CustomField;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

/**
 * GET /v1/custom-fields/schema/{entity_type}
 * Trả về danh sách tất cả định nghĩa active cho một entity_type của công ty đang đăng nhập.
 * Frontend dùng endpoint này để render form nhập liệu.
 */
class GetSchemaAction extends CustomFieldAction
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

        $entityType = strtolower(trim((string)$this->resolveArg('entity_type')));
        if (!in_array($entityType, self::ENTITY_TYPES, true)) {
            throw new HttpBadRequestException($this->request, "entity_type '$entityType' không hợp lệ");
        }

        $companyId   = (int)($this->auth_data['company_id'] ?? 0);
        $definitions = $this->customFieldRepository->findDefinitionsByEntityType($entityType, $companyId);

        return $this->respondWithData([
            'result'      => 'success',
            'entity_type' => $entityType,
            'schema'      => array_map(fn($d) => $d->jsonSerialize(), $definitions),
            'trace_id'    => $trace_id,
        ]);
    }
}
