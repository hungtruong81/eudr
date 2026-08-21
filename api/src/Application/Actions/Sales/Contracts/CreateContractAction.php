<?php

declare(strict_types=1);

namespace App\Application\Actions\Sales\Contracts;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class CreateContractAction extends ContractAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $scope = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'sales_contract', 'create');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('customer_id', $formData['customer_id'] ?? null, 'required|integer|min:1');
        $validator->validate('title', $formData['title'] ?? null, 'required|string|max:255');
        $validator->validate('start_date', $formData['start_date'] ?? null, 'required|date');
        $validator->validate('end_date', $formData['end_date'] ?? null, 'date');
        $validator->validate('payment_terms', $formData['payment_terms'] ?? null, 'string|max:255');
        $validator->validate('delivery_terms', $formData['delivery_terms'] ?? null, 'string|max:255');
        $validator->validate('currency', $formData['currency'] ?? null, 'string|max:10');
        $validator->validate('status', $formData['status'] ?? null, 'string|in:draft,active');
        $validator->validate('notes', $formData['notes'] ?? null, 'string|max:500');
        $validator->validate('company_id', $formData['company_id'] ?? null, 'integer');

        if ($validator->hasErrors()) {
            $errors = [];
            foreach ((array)$validator->getErrors() as $fieldErrors) {
                $errors[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errors));
        }

        $clean = $validator->sanitize($formData, [
            'customer_id' => 'integer',
            'title' => 'string',
            'start_date' => 'date',
            'end_date' => 'date',
            'payment_terms' => 'string',
            'delivery_terms' => 'string',
            'currency' => 'string',
            'status' => 'string',
            'notes' => 'string',
            'company_id' => 'integer',
        ]);

        $items = $formData['items'] ?? [];
        if (!is_array($items) || empty($items)) {
            throw new HttpBadRequestException($this->request, 'Danh sách dòng hợp đồng trống');
        }

        $preparedItems = [];
        foreach ($items as $item) {
            $preparedItems[] = [
                'product_id' => (int)($item['product_id'] ?? 0),
                'uom' => (string)($item['uom'] ?? ''),
                'qty_committed' => (float)($item['qty_committed'] ?? 0),
                'price' => (float)($item['price'] ?? 0),
                'currency' => (string)($item['currency'] ?? ($clean['currency'] ?? 'VND')),
                'min_qc_grade' => $item['min_qc_grade'] ?? null,
                'delivery_start' => $item['delivery_start'] ?? null,
                'delivery_end' => $item['delivery_end'] ?? null,
                'notes' => $item['notes'] ?? null,
            ];
        }

        $company_id = $clean['company_id'] ?? ($this->auth_data['company_id'] ?? 0);
        $data = [
            'contract_code' => $this->salesContractRepository->generateCode(),
            'company_id' => $company_id,
            'customer_id' => $clean['customer_id'],
            'title' => $clean['title'],
            'start_date' => $clean['start_date'],
            'end_date' => $clean['end_date'] ?? null,
            'payment_terms' => $clean['payment_terms'] ?? null,
            'delivery_terms' => $clean['delivery_terms'] ?? null,
            'currency' => $clean['currency'] ?? 'VND',
            'status' => $clean['status'] ?? 'draft',
            'version_no' => 1,
            'signed_at' => null,
            'signed_by' => null,
            'notes' => $clean['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $this->auth_data['user_id'],
        ];

        $contract = $this->salesContractRepository->createContract($data, $preparedItems);

        if (!$contract) {
            throw new HttpBadRequestException($this->request, 'Không thể tạo hợp đồng');
        }

        $res = ['result' => 'success', 'contract' => $contract->jsonSerialize(), 'trace_id' => $trace_id];
        return $this->respondWithData($res);
    }
}
