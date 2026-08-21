<?php

declare(strict_types=1);

namespace App\Application\Actions\Harvest;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;


class CreateHarvestPlanAction extends HarvestAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        // Check Authentication
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        // Check permission
        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'harvest_plan', 'create');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $formData = $this->getFormData();

        $validator = new Validator($this->request);

        $validator->validate('harvest_start_date', $formData['harvest_start_date'] ?? null, 'required|date');
        $validator->validate('harvest_end_date', $formData['harvest_end_date'] ?? null, 'required|date');
        $validator->validate('tapping_regime', $formData['tapping_regime'] ?? null, 'required|in:D1,D2,D3,D4,Flexible');
        $validator->validate('expected_yield', $formData['expected_yield'] ?? 0, 'required|numeric');
        $validator->validate('plot_ids', $formData['plot_ids'] ?? null, 'required|array');
        $validator->validate('contract_code', $formData['contract_code'] ?? null, 'required|string|max:100');
        $validator->validate('notes', $formData['notes'] ?? null, 'string|max:255');

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
            'harvest_start_date' => 'date',
            'harvest_end_date' => 'date',
            'tapping_regime' => 'string',
            'expected_yield' => 'numeric',
            //'plot_ids' => 'array',
            'contract_code' => 'string',
            'notes' => 'string'
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);
        
        $harvest_start_date = $cleanData['harvest_start_date'];
        $harvest_end_date = $cleanData['harvest_end_date'];
        $tapping_regime = $cleanData['tapping_regime'];
        $expected_yield = $cleanData['expected_yield'];
        $plot_ids = $formData['plot_ids'] ?? [];
        $contract_code = $cleanData['contract_code'];
        $notes = $cleanData['notes'] ?? '';

        // Check if harvest start date is before end date
        if (strtotime($harvest_start_date) > strtotime($harvest_end_date)) {
            throw new HttpBadRequestException($this->request, "Ngày bắt đầu thu hoạch không thể sau ngày kết thúc thu hoạch");
        }

        // Check if plot IDs are valid
        if (empty($plot_ids)) {
            throw new HttpBadRequestException($this->request, "ID thửa đất không hợp lệ");
        }

        // Generate code for harvest plan
        $harvest_plan_code = $this->harvestRepository->generateHarvestPlanCode();

        // Check if contract exists
        $data_contract = $this->transactionTicketRepository->findTransactionTicketOfContractCode($contract_code);
        if (empty($data_contract)) {
            throw new HttpNotFoundException($this->request, "Hợp đồng không tồn tại");
        }

        if($this->auth_data['user_id'] != $data_contract->getSellerUserId()) {
            throw new HttpBadRequestException($this->request, "Bạn không phải là người bán trong hợp đồng này");
        }
        
        // Create harvest plan
        $data_update = [
            "company_id" => $this->auth_data['company_id'] ?? 0,
            'farmer_id' => $this->auth_data['user_id'],
            'dealer_id' => $data_contract->getBuyerUserId(),
            'buyer_company_id' => $data_contract->getBuyerCompanyId(),
            'buyer_user_id' => $data_contract->getBuyerUserId(),
            'harvest_plan_code' => $harvest_plan_code,
            'harvest_start_date' => $harvest_start_date,
            'harvest_end_date' => $harvest_end_date,
            'tapping_regime' => $tapping_regime,
            'contract_code' => $contract_code,
            'expected_yield' => $expected_yield,
            'eudr_status' => 0,
            'plot_ids' => $plot_ids,
            'notes' => $notes,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $this->auth_data['user_id'],
        ];

        $harvest_plan = $this->harvestRepository->createHarvestPlan($data_update);

        $action = 'create';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'harvest_plan',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$harvest_plan->getId(),
        );

        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['harvest_plan'] = $harvest_plan->jsonSerialize();
        return $this->respondWithData($res_return);

    }
}
