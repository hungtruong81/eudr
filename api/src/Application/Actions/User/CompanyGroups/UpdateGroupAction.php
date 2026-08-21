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

class UpdateGroupAction extends CompanyGroupAction
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

		// Check permission to update company groups
		$scope = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'company_group', 'update');
		if (empty($scope)) {
			throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
		}

		$company_group_code = addslashes(trim((string)$this->resolveArg('code')));
		$group = $this->companyGroupRepository->findGroupOfCodeWithPermission(
			$company_group_code,
			(int)$this->auth_data['user_id'],
			(string)$scope
		);

		if (empty($group)) {
			throw new HttpNotFoundException($this->request, "Nhóm người dùng không tồn tại");
		}

		$formData = $this->getFormData();

		$validator = new Validator($this->request);

		$validator->validate('name', $formData['name'] ?? null, 'string|max:255');
		$validator->validate('description', $formData['description'] ?? null, 'string|max:1000');
		$validator->validate('status', $formData['status'] ?? null, 'string|in:active,inactive');

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

        
        // Sanitize and extract data
        $sanitizeRules = [
            'name' => 'string',
            'description' => 'string'
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $name = $cleanData['name'];
        $description = $cleanData['description'] ?? '';
		$is_default = !empty($formData['is_default']) ? 1 : 0;

		$data_update = array(
            'name' => $name,
            'description' => $description,
            'status' => $formData['status'] ?? $group->getStatus(),
            'is_default' => $is_default,
            'updated_at' => date('Y-m-d H:i:s', time()),
            'updated_by' => $this->auth_data['user_id'],
        );

		$updatedGroup = $this->companyGroupRepository->updateGroupWithPermission(
			$group->getId(),
			$data_update,
			(int)$this->auth_data['user_id'],
			(string)$scope
		);

		$action = 'update_company_group';
		$log = [
			'milliseconds' => floor(microtime(true) * 1000),
			'trace_id' => $trace_id,
			'log_type' => 'company_group',
			'action' => $action,
			'user_id' => (string)$this->auth_data['user_id'],
			'extra_1' => (string)$updatedGroup->getId(),
		];
		Utils::save_log($this->logger, $log);

		$res_return = ['result' => 'success'];
		$res_return['trace_id'] = $trace_id;
		$res_return['group'] = $updatedGroup->jsonSerialize();

		return $this->respondWithData($res_return);
	}
}

