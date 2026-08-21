<?php

declare(strict_types=1);

namespace App\Application\Actions\Sales\Customers;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class UpdateCustomerAction extends CustomerAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'sales_customer', 'update');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $customer_code = addslashes(trim((string)$this->resolveArg('code')));
        $customer = $this->salesCustomerRepository->findCustomerOfCodeWithPermission(
            $customer_code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null,
            $this->auth_data['company_id'] ?? null
        );
        if (!$customer || !$customer->getId()) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy khách hàng');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('customer_name', $formData['customer_name'] ?? null, 'required|string|max:255');
        $validator->validate('customer_email', $formData['customer_email'] ?? null, 'email');
        $validator->validate('customer_phone', $formData['customer_phone'] ?? null, ['required', 'regex:/^0[0-9]{9}$/']);
        $validator->validate('tax_code', $formData['tax_code'] ?? null, ['regex:/^[0-9]{10,}$/']);
        $validator->validate('billing_address', $formData['billing_address'] ?? null, 'string|max:500');
        $validator->validate('shipping_address', $formData['shipping_address'] ?? null, 'string|max:500');
        $validator->validate('customer_type', $formData['customer_type'] ?? null, 'string|max:50');
        $validator->validate('status', $formData['status'] ?? null, 'string|in:active,inactive');
        $validator->validate('notes', $formData['notes'] ?? null, 'string|max:500');

        if ($validator->hasErrors()) {
            $errors = [];
            foreach ((array)$validator->getErrors() as $fieldErrors) {
                $errors[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errors));
        }

        $clean = $validator->sanitize($formData, [
            'customer_name' => 'string',
            'customer_email' => 'email',
            'customer_phone' => 'string',
            'tax_code' => 'string',
            'billing_address' => 'string',
            'shipping_address' => 'string',
            'customer_type' => 'string',
            'status' => 'string',
            'notes' => 'string',
        ]);

        $data = [
            'customer_name' => $clean['customer_name'],
            'customer_email' => $clean['customer_email'] ?? null,
            'customer_phone' => $clean['customer_phone'] ?? null,
            'tax_code' => $clean['tax_code'] ?? null,
            'billing_address' => $clean['billing_address'] ?? null,
            'shipping_address' => $clean['shipping_address'] ?? null,
            'customer_type' => $clean['customer_type'] ?? null,
            'status' => $clean['status'] ?? 'active',
            'notes' => $clean['notes'] ?? null,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $this->auth_data['user_id'],
        ];

        $updated = $this->salesCustomerRepository->updateCustomerWithPermission(
            (int)$customer->getId(),
            $data,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null,
            $this->auth_data['company_id'] ?? null
        );

        $action = 'update';
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
            'customer' => $updated ? $updated->jsonSerialize() : null, 
            'trace_id' => $trace_id
        ];

        return $this->respondWithData($res);
    }
}
