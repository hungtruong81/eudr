<?php

declare(strict_types=1);

namespace App\Application\Actions\Sales\Customers;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;

class ViewCustomerAction extends CustomerAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'sales_customer', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $customer_code = addslashes(trim((string)$this->resolveArg('code')));
        $customer = $this->salesCustomerRepository->findCustomerOfCodeWithPermission(
            $customer_code,
            (int)$this->auth_data['user_id'],
            $scope,
            $this->auth_data['company_id'] ?? null,
            $this->auth_data['company_id'] ?? null
        );

        if (!$customer) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy khách hàng');
        }

        $action = 'view';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'sales_customer',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$customer->getId(),
        );
        
        Utils::save_log($this->logger, $log);

        $res = [
            'result' => 'success', 
            'customer' => $customer->jsonSerialize(), 
            'trace_id' => $trace_id
        ];
        
        return $this->respondWithData($res);
    }
}
