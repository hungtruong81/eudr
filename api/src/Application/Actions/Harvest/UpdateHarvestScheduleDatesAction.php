<?php

declare(strict_types=1);

namespace App\Application\Actions\Harvest;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;

class UpdateHarvestScheduleDatesAction extends HarvestAction
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

        // Permission: update harvest result (requested)
        $scope = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'harvest_result', 'update');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $formData = $this->getFormData();

        $required_fields = ['harvest_plan_code', 'schedules'];
        $missing_fields = Utils::validFields($required_fields, $formData);
        if (!empty($missing_fields)) {
            throw new HttpBadRequestException($this->request, "Thiếu trường " . implode(", ", $missing_fields));
        }

        $harvest_plan_code = '';
        if (!empty($formData['harvest_plan_code'])) {
            $harvest_plan_code = htmlspecialchars(trim($formData['harvest_plan_code']));
        }

        $harvest_plan = $this->harvestRepository->findHarvestPlanOfCodeWithPermission($harvest_plan_code, $this->auth_data['user_id'], (string)$scope);
        if (empty($harvest_plan)) {
            throw new HttpBadRequestException($this->request, "Kế hoạch thu hoạch không tồn tại");
        }

        $contract_code = $harvest_plan->getContractCode();
        if (empty($contract_code)) {
            throw new HttpBadRequestException($this->request, "Hợp đồng không tồn tại");
        }

        $data_transaction_ticket = $this->transactionTicketRepository->findTransactionTicketOfContractCode($contract_code);
        $buyer_user_id = $data_transaction_ticket->getBuyerUserId();

        // Verify buyer user exists
        $buyer_user = $this->userRepository->findUserOfId($buyer_user_id);
        if (empty($buyer_user)) {
            throw new HttpNotFoundException($this->request, "Người mua không tồn tại");
        }

        $buyer_company_id = $buyer_user->getCompanyId() ?? 0;

        $schedules = [];
        if (!empty($formData['schedules']) && is_array($formData['schedules'])) {
            foreach ($formData['schedules'] as $k => $schedule) {
                if (empty($schedule['plot_id'])
                    || empty($schedule['pickup_date'])
                    || empty($schedule['pickup_time'])
                    || empty($schedule['expected_yield'])) {
                    throw new HttpBadRequestException($this->request, "Thiếu trường dữ liệu trong danh sách lịch thu hoạch hàng " . ($k + 1));
                }

                if (!empty($schedule['harvest_schedule_id']) && !is_numeric($schedule['harvest_schedule_id'])) {
                    throw new HttpBadRequestException($this->request, "Định dạng lịch thu hoạch (harvest_schedule_id) không hợp lệ hàng " . ($k + 1));
                }
                if (!is_numeric($schedule['plot_id'])) {
                    throw new HttpBadRequestException($this->request, "Định dạng đất (plot_id) không hợp lệ hàng " . ($k + 1));
                }
                if (!is_numeric($schedule['expected_yield'])) {
                    throw new HttpBadRequestException($this->request, "Định dạng sản lượng dự kiến (expected_yield) không hợp lệ hàng " . ($k + 1));
                }

                if (!Utils::isValidDate($schedule['pickup_date'], 'Y-m-d')) {
                    throw new HttpBadRequestException($this->request, "Định dạng ngày thu hoạch (pickup_date) không hợp lệ hàng " . ($k + 1));
                }

                foreach ($schedules as $existing_schedule) {
                    if ($existing_schedule['pickup_date'] === $schedule['pickup_date']) {
                        throw new HttpBadRequestException($this->request, "Ngày thu hoạch (pickup_date) bị trùng hàng " . ($k + 1));
                    }
                }

                if (!Utils::isValidTime($schedule['pickup_time'], 'H:i')) {
                    throw new HttpBadRequestException($this->request, "Định dạng thời gian thu hoạch (pickup_time) không hợp lệ hàng " . ($k + 1));
                }

                $schedules[] = [
                    'harvest_plan_id' => $harvest_plan->getId(),
                    'harvest_schedule_id' => !empty($schedule['harvest_schedule_id']) ? intval($schedule['harvest_schedule_id']) : 0,
                    'plot_id' => intval($schedule['plot_id']),
                    'pickup_date' => htmlspecialchars(trim($schedule['pickup_date'])),
                    'pickup_time' => htmlspecialchars(trim($schedule['pickup_time'])),
                    'expected_yield' => intval($schedule['expected_yield']),
                    'buyer_user_id' => $buyer_user_id,
                    'buyer_company_id' => $buyer_company_id,
                ];
            }
        }

        $data_update = [
            'harvest_plan_id' => $harvest_plan->getId(),
            'schedules' => $schedules,
            'updated_by' => $this->auth_data['user_id'],
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $harvest_schedules = $this->harvestRepository->createOrUpdateHarvestSchedules($data_update);
        if (empty($harvest_schedules)) {
            throw new HttpBadRequestException($this->request, "Không thể cập nhật lịch thu hoạch");
        }

        $log = [
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'harvest_schedule',
            "action" => 'update_dates',
            "user_id" => (string)$this->auth_data['user_id'],
        ];
        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['harvest_schedules'] = $harvest_schedules;

        return $this->respondWithData($res_return);
    }
}
