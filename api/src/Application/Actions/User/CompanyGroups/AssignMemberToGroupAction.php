<?php

declare(strict_types=1);

namespace App\Application\Actions\User\CompanyGroups;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;

class AssignMemberToGroupAction extends CompanyGroupAction
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

		// Reuse update permission for managing group members
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
			throw new HttpNotFoundException($this->request, "Nhóm quyền không tồn tại");
		}

		$formData = $this->getFormData();

		$assign_ids = isset($formData['assign_user_ids']) && is_array($formData['assign_user_ids'])
			? array_map('intval', $formData['assign_user_ids'])
			: [];
		$remove_ids = isset($formData['remove_user_ids']) && is_array($formData['remove_user_ids'])
			? array_map('intval', $formData['remove_user_ids'])
			: [];

		$assign_ids = array_values(array_unique(array_filter($assign_ids)));
		$remove_ids = array_values(array_unique(array_filter($remove_ids)));

		if (empty($assign_ids) && empty($remove_ids)) {
			throw new HttpBadRequestException($this->request, "Danh sách thành viên cập nhật không hợp lệ");
		}

		if (!empty($assign_ids)) {
			$this->companyGroupRepository->assignMembers((int)$group->getId(), $assign_ids, (int)$this->auth_data['user_id']);
		}
		if (!empty($remove_ids)) {
			$this->companyGroupRepository->removeMembers((int)$group->getId(), $remove_ids);
		}

		$member_ids = $this->companyGroupRepository->getGroupMemberIds((int)$group->getId());

		$action = 'update_company_group_members';
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
		$res_return['group']['member_ids'] = $member_ids;

		return $this->respondWithData($res_return);
	}
}

