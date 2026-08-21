<?php

declare(strict_types=1);

namespace App\Application\Actions\User\CompanyGroups;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class SetGroupPermissionAction extends CompanyGroupAction
{
	/**
	 * {@inheritdoc}
	 */
	protected function action(): Response
	{
		// trace_id tracking request
		$trace_id = Utils::generateRandomString(25);

        // Check authenticated user
		if (empty($this->auth_data['user_id'])) {
			throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
		}

		// Reuse update permission for setting group permissions
		$scope = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'company_group', 'update');
		if (empty($scope)) {
			throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
		}

		$company_group_code = addslashes(trim((string)$this->resolveArg('code')));
		$group = $this->companyGroupRepository->findGroupOfCode($company_group_code);
		if (empty($group)) {
			throw new HttpNotFoundException($this->request, "Nhóm quyền không tồn tại");
		}

		$formData = $this->getFormData();

		$validator = new Validator($this->request);
		$validator->validate('permissions', $formData['permissions'] ?? null, 'required|array');

		if ($validator->hasErrors()) {
			$errorMessages = [];
			$errors = $validator->getErrors();
			if (is_array($errors)) {
				foreach ($errors as $field => $fieldErrors) {
					if (is_array($fieldErrors)) {
						$errorMessages[] = implode(', ', $fieldErrors);
					} else {
						$errorMessages[] = (string)$fieldErrors;
					}
				}
			}
			throw new HttpBadRequestException($this->request, implode('; ', $errorMessages));
		}

		$permissionNames = $formData['permissions'] ?? [];
		if (!is_array($permissionNames)) {
			throw new HttpBadRequestException($this->request, "Danh sách quyền không hợp lệ");
		}

		// Normalize to string list
		$permissionNames = array_values(array_unique(array_filter(array_map('strval', $permissionNames))));

		$this->companyGroupRepository->setGroupPermissionsByNames($group->getId(), $permissionNames);

		$updatedPermissions = $this->companyGroupRepository->getGroupPermissions((int)$group->getId());

		$action = 'update_company_group_permissions';
		$log = [
			'milliseconds' => floor(microtime(true) * 1000),
			'trace_id' => $trace_id,
			'log_type' => 'company_group',
			'action' => $action,
			'user_id' => (string)$this->auth_data['user_id'],
			'extra_1' => (string)$group->getId(),
		];

		Utils::save_log($this->logger, $log);

		$res_return = ['result' => 'success'];
		$res_return['trace_id'] = $trace_id;
		$res_return['group'] = $group->jsonSerialize();
		$res_return['group']['permissions'] = $updatedPermissions;

		return $this->respondWithData($res_return);
	}
}

