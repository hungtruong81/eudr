<?php

declare(strict_types=1);

namespace App\Application\Actions\Harvest;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;


class ListHarvestScheduleAction extends HarvestAction
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

        // Check permission to view harvest schedules
        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'harvest_schedule', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $formData = $this->request->getQueryParams();

        $page = 1;
        if(!empty($formData['page'])) {
            $page = intval($formData['page']);
            if ($page < 1) {
                $page = 1;
            }

        }

        $limit = 10;
        if(!empty($formData['limit'])) {
            $limit = intval($formData['limit']);
            if ($limit < 1 || $limit > 100) {
                $limit = 10;
            }

        }

        $harvest_plan_code = "";
        $harverst_plan_id = 0;
        if (!empty($formData['harvest_plan_code'])) {
            $harvest_plan_code = htmlspecialchars(trim($formData['harvest_plan_code']));
            $harvest_plan = $this->harvestRepository->findHarvestPlanOfCode($harvest_plan_code);
            if (empty($harvest_plan)) {
                throw new HttpBadRequestException($this->request, "Kế hoạch thu hoạch không tồn tại");
            }
            $harverst_plan_id = $harvest_plan->getId();
        }

        $plot_code = "";
        $plot_id = 0;
        if (!empty($formData['plot_code'])) {
            $plot_code = htmlspecialchars(trim($formData['plot_code']));
            $land = $this->landRepository->findLandOfCode($plot_code);
            if (empty($land)) {
                throw new HttpBadRequestException($this->request, "Thửa đất không tồn tại");
            }
            $plot_id = $land->getId();
        }

        $pickup_date = "";
        if (!empty($formData['pickup_date'])) {
            $pickup_date = htmlspecialchars(trim($formData['pickup_date']));
        }

        // Validate pickup date
        if (!empty($pickup_date) && !Utils::isValidDate($pickup_date)) {
            throw new HttpBadRequestException($this->request, "Ngày thu hoạch không hợp lệ");
        }

        $search = "";
        if (!empty($formData['search'])) {
            $search = htmlspecialchars(trim($formData['search']));
        }

        $params = [
            "page" => $page,
            "page_limit" => $limit,
            "harvest_plan_code" => $harvest_plan_code,
            "search" => $search,
            "scope" => (string)$scope,
            "user_id" => $this->auth_data['user_id'],
            "company_id" => $this->auth_data['company_id'] ?? null,
            "harvest_plan_id" => $harverst_plan_id,
            "plot_id" => $plot_id,
            "pickup_date" => $pickup_date,
        ];

        $harvest_schedules = $this->harvestRepository->findAllHarvestSchedules($params);

        $res_return = ["result" => "success"];
        $res_return['data'] = $harvest_schedules;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }

}
