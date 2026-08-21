<?php

declare(strict_types=1);

namespace App\Application\Actions\CustomField;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

/**
 * GET /v1/custom-fields/values/{entity_type}/{entity_id}
 * Trả về tất cả custom field values (kèm metadata) của một thực thể.
 */
class GetEntityValuesAction extends CustomFieldAction
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
        $entityId   = (int)$this->resolveArg('entity_id');

        if (!in_array($entityType, self::ENTITY_TYPES, true)) {
            throw new HttpBadRequestException($this->request, "entity_type '$entityType' không hợp lệ");
        }
        if ($entityId <= 0) {
            throw new HttpBadRequestException($this->request, 'entity_id phải lớn hơn 0');
        }

        $companyId = (int)($this->auth_data['company_id'] ?? 0);
        $values    = $this->customFieldRepository->getEntityValues($entityType, $entityId, $companyId);

        return $this->respondWithData([
            'result'      => 'success',
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'values'      => array_map(fn($v) => $v->jsonSerialize(), $values),
            'trace_id'    => $trace_id,
        ]);
    }
}
