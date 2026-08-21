<?php

declare(strict_types=1);

namespace App\Application\Actions\User\CompanyGroups;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class CreateGroupAction extends CompanyGroupAction
{
	/**
	 * {@inheritdoc}
	 */
	protected function action(): Response
	{
		// trace_id tracking request
		$trace_id = Utils::generateRandomString(25);

		if (empty($this->auth_data['user_id'])) {
			throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
		}

		// Check permission to create company groups
		$scope = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'company_group', 'create');
		if (empty($scope)) {
			throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
		}
        
		$formData = $this->getFormData();

		$validator = new Validator($this->request);

		$validator->validate('name', $formData['name'] ?? null, 'required|string|max:255');
		$validator->validate('description', $formData['description'] ?? null, 'string|max:1000');

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

        $company_group_code = $this->companyGroupRepository->generateCode();

		$data_insert = [
			'company_group_code' => $company_group_code,
			'name' => $name,
			'description' => $description,
			'status' => 'active',
			'is_default' => $is_default,
			'created_at' => date('Y-m-d H:i:s', time()),
			'created_by' => $this->auth_data['user_id'],
		];

		$group = $this->companyGroupRepository->createGroup($data_insert);

		$action = 'create_company_group';
		$log = [
			'milliseconds' => floor(microtime(true) * 1000),
			'trace_id' => $trace_id,
			'log_type' => 'company_group',
			'action' => $action,
			'user_id' => (string)$this->auth_data['user_id'],
			'extra_1' => (string)($group ? $group->getId() : 0),
		];
		Utils::save_log($this->logger, $log);

		$res_return = ['result' => 'success'];
		$res_return['trace_id'] = $trace_id;
		$res_return['group'] = $group ? $group->jsonSerialize() : null;

		return $this->respondWithData($res_return);
	}
}

