<?php

declare(strict_types=1);

namespace App\Application\Actions\Harvest;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;


class CreateOrUpdateHarvestResultAction extends HarvestAction
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

        $user_role = $this->userRepository->getUserRole($this->auth_data['user_id']);

        // Check permission to create or update harvest result
        $scope = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'harvest_result', 'update');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }
        
        $formData = $this->getFormData();

        // Validate data fields
        $required_fields = ['harvest_schedule_code', 'actual_yield'];

        $missing_fields = Utils::validFields($required_fields, $formData);

        if (!empty($missing_fields)) {
            throw new HttpBadRequestException($this->request, "Thiếu trường ".implode(", ", $missing_fields));
        }

        $harvest_schedule_code = "";
        if (!empty($formData['harvest_schedule_code'])) {
            $harvest_schedule_code = htmlspecialchars(trim($formData['harvest_schedule_code']));
        }

        $actual_yield = 0;
        if (!empty($formData['actual_yield'])) {
            $actual_yield = (float) $formData['actual_yield'];
        }

        $is_locked = -1;
        if (isset($formData['is_locked']) && $formData['is_locked'] !== '') {
            if (!is_numeric($formData['is_locked'])) {
                throw new HttpBadRequestException($this->request, "Giá trị is_locked không hợp lệ");
            }
            if ($formData['is_locked'] < -1 || $formData['is_locked'] > 1) {
                throw new HttpBadRequestException($this->request, "Giá trị is_locked không hợp lệ");
            }
            $is_locked = (int) $formData['is_locked'];
        }

        $data = [
            "user_role" => $user_role
        ];

        $can_update = $this->harvestRepository->canUpdateHarvestResult($harvest_schedule_code, $this->auth_data['user_id'], $worker_id=0, $data);

        if (!$can_update) {
            throw new HttpForbiddenException($this->request, "Không đủ quyền truy cập");
        }

        // Update harvest result
        $data_update = [
            'actual_yield' => $actual_yield,
            'updated_by' => (int)$this->auth_data['user_id'],
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // if ($is_locked !== -1 && $user_role !== 'worker') {
        //     $data_update['is_locked'] = $is_locked;
        // }

        // Update harvest schedule
        $harvest_schedules = $this->harvestRepository->updateActualYieldOfHarvestSchedule($harvest_schedule_code, $this->auth_data['user_id'], $data_update);
        if (empty($harvest_schedules)) {
            throw new HttpBadRequestException($this->request, "Không thể cập nhật lịch thu hoạch");
        }

        $action = 'upsert';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'harvest_result',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$harvest_schedules['harvest_schedule_id'],
        );

        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['harvest_result'] = $harvest_schedules;

        return $this->respondWithData($res_return);

    }
}
