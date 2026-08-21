<?php

declare(strict_types=1);

namespace App\Application\Actions\Sales\Customers;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class CreateCustomerAction extends CustomerAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'sales_customer', 'create');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('customer_code', $formData['customer_code'] ?? null, 'required|string|max:30');
        $validator->validate('customer_name', $formData['customer_name'] ?? null, 'required|string|max:255');
        $validator->validate('customer_email', $formData['customer_email'] ?? null, 'email');
        $validator->validate('customer_phone', $formData['customer_phone'] ?? null, ['required', 'regex:/^0[0-9]{9}$/']);
        $validator->validate('customer_company_name', $formData['customer_company_name'] ?? null, 'string|max:255');
        $validator->validate('tax_code', $formData['tax_code'] ?? null, ['regex:/^[0-9]{10,}$/']);
        $validator->validate('billing_address', $formData['billing_address'] ?? null, 'string|max:255');
        $validator->validate('shipping_address', $formData['shipping_address'] ?? null, 'string|max:255');
        $validator->validate('customer_type', $formData['customer_type'] ?? null, 'string|max:50');
        $validator->validate('status', $formData['status'] ?? null, 'string|in:active,inactive');
        $validator->validate('notes', $formData['notes'] ?? null, 'string|max:255');
        $validator->validate('business_license_file_ids', $formData['business_license_file_ids'] ?? null, 'array');
        $validator->validate('company_id', $formData['company_id'] ?? null, 'integer');

        if ($validator->hasErrors()) {
            $errors = [];
            foreach ((array)$validator->getErrors() as $fieldErrors) {
                $errors[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errors));
        }

        $clean = $validator->sanitize($formData, [
            'customer_code' => 'string',
            'customer_name' => 'string',
            'customer_email' => 'email',
            'customer_phone' => 'string',
            'customer_company_name' => 'string',
            'tax_code' => 'string',
            'billing_address' => 'string',
            'shipping_address' => 'string',
            'customer_type' => 'string',
            'status' => 'string',
            'notes' => 'string',
            'company_id' => 'integer',
        ]);

        $customer_code = $clean['customer_code'];
        // Check if customer code already exists
        $customer_exists = $this->salesCustomerRepository->findCustomerOfCode($customer_code);
        if ($customer_exists) {
            $customer_code = $this->salesCustomerRepository->generateCode();
        }

        $business_license_file_ids = [];
        if (!empty($formData['business_license_file_ids']) && is_array($formData['business_license_file_ids'])) {
            $business_license_file_ids = array_map('intval', $formData['business_license_file_ids']);
        }

        $company_id = $clean['company_id'] ?? ($this->auth_data['company_id'] ?? 0);

        $data = [
            'customer_code' => $customer_code,
            'company_id' => $company_id,
            'customer_name' => $clean['customer_name'],
            'customer_email' => $clean['customer_email'] ?? null,
            'customer_phone' => $clean['customer_phone'] ?? null,
            'customer_company_name' => $clean['customer_company_name'] ?? null,
            'business_license_file_ids' => json_encode($business_license_file_ids),
            'tax_code' => $clean['tax_code'] ?? null,
            'billing_address' => $clean['billing_address'] ?? null,
            'shipping_address' => $clean['shipping_address'] ?? null,
            'customer_type' => $clean['customer_type'] ?? null,
            'status' => $clean['status'] ?? 'active',
            'notes' => $clean['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $this->auth_data['user_id'],
        ];

        $customer = $this->salesCustomerRepository->createCustomer($data);

        // Log action
        $action = 'create';
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
            'customer' => $customer ? $customer->jsonSerialize() : null, 
            'trace_id' => $trace_id
        ];

        return $this->respondWithData($res);
    }
}
