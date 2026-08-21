<?php

declare(strict_types=1);

namespace App\Application\Actions\Sales\Contracts;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;

class ViewContractAction extends ContractAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'sales_contract', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $contract_code = addslashes(trim((string)$this->resolveArg('code')));
        $contract = $this->salesContractRepository->findContractOfCodeWithPermission(
            $contract_code,
            (int)$this->auth_data['user_id'],
            $scope,
            $this->auth_data['company_id'] ?? null,
            $this->auth_data['company_id'] ?? null
        );

        if (!$contract) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy hợp đồng');
        }

        $res = ['result' => 'success', 'contract' => $contract->jsonSerialize(), 'trace_id' => $trace_id];
        return $this->respondWithData($res);
    }
}
