<?php

declare(strict_types=1);

namespace App\Application\Actions\Harvest;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;


class UpdateHarvestPlanAction extends HarvestAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        // Check authentication
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        // Check permission to update harvest plan
        $scope = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'harvest_plan', 'update');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $harvest_plan_code = addslashes(trim((string)$this->resolveArg('code')));

        $harvest_plan = $this->harvestRepository->findHarvestPlanOfCodeWithPermission($harvest_plan_code, $this->auth_data['user_id'], (string)$scope);
        if (empty($harvest_plan)) {
            throw new HttpNotFoundException($this->request, "Kế hoạch thu hoạch không tồn tại");
        }

        $formData = $this->getFormData();
        $data_update = [];

        $harvest_start_date = "";
        if (!empty($formData['harvest_start_date'])) {
            $harvest_start_date = htmlspecialchars(trim($formData['harvest_start_date']));
            if (!Utils::isValidDate($harvest_start_date)) {
                throw new HttpBadRequestException($this->request, "Ngày bắt đầu thu hoạch không hợp lệ");
            }
            $data_update['harvest_start_date'] = $harvest_start_date;
        }

        $harvest_end_date = "";
        if (!empty($formData['harvest_end_date'])) {
            $harvest_end_date = htmlspecialchars(trim($formData['harvest_end_date']));
            if (!Utils::isValidDate($harvest_end_date)) {
                throw new HttpBadRequestException($this->request, "Ngày kết thúc thu hoạch không hợp lệ");
            }
            $data_update['harvest_end_date'] = $harvest_end_date;
        }

        if (!empty($formData['harvest_start_date']) && !empty($formData['harvest_end_date'])) {
            if (strtotime($harvest_start_date) > strtotime($harvest_end_date)) {
                throw new HttpBadRequestException($this->request, "Ngày bắt đầu thu hoạch không thể sau ngày kết thúc thu hoạch");
            }
        }

        $tapping_regime = "";
        if (!empty($formData['tapping_regime'])) {
            $tapping_regime = htmlspecialchars(trim($formData['tapping_regime']));
            if (!in_array($tapping_regime, ['D1', 'D2', 'D3', 'D4', 'Flexible'])) {
                throw new HttpBadRequestException($this->request, "Chế độ khai thác không hợp lệ");
            }
            $data_update['tapping_regime'] = $tapping_regime;
        }


        $expected_yield = 0;
        if(!empty($formData['expected_yield'])) {
            $expected_yield = floatval($formData['expected_yield']);
            $data_update['expected_yield'] = $expected_yield;
        }

        $eudr_status = 0;
        if(!empty($formData['eudr_status'])) {
            $eudr_status = intval($formData['eudr_status']);
            $data_update['eudr_status'] = $eudr_status;
        }

        $plot_ids = [];
        if (!empty($formData['plot_ids']) && is_array($formData['plot_ids'])) {
            $plot_ids = $formData['plot_ids'];
            $data_update['plot_ids'] = $plot_ids;
        }

        $notes = "";
        if (!empty($formData['notes'])) {
            $notes = htmlspecialchars(trim($formData['notes']));
            $data_update['notes'] = $notes;
        }

        $contract_code = "";
        if (!empty($formData['contract_code'])) {
            $contract_code = htmlspecialchars(trim($formData['contract_code']));
            // Check if contract exists
            $contract = $this->transactionTicketRepository->findTransactionTicketOfContractCode($contract_code);
            if (empty($contract)) {
                throw new HttpNotFoundException($this->request, "Hợp đồng không tồn tại");
            }

            if($this->auth_data['user_id'] != $contract->getSellerUserId()) {
                throw new HttpForbiddenException($this->request, "Bạn không phải là người bán trong hợp đồng này");
            }
            $data_update['contract_code'] = $contract_code;
        }

        $harvest_plan = $this->harvestRepository->updateHarvestPlan($harvest_plan->getId(), $data_update);

        $action = 'update';
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
