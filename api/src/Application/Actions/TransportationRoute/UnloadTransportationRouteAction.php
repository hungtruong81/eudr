<?php

declare(strict_types=1);

namespace App\Application\Actions\TransportationRoute;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;


class UnloadTransportationRouteAction extends TransportationRouteAction
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

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);

        // Check permission to update transportation routes
        $scope = Utils::resolveScope($permissions, 'transportation_route', 'update');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $transportation_route_code = addslashes(trim((string)$this->resolveArg('code')));

        $transportation_route = $this->transportationRouteRepository->findTransportationRouteOfCodeWithPermission(
            $transportation_route_code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($transportation_route)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy lộ trình vận chuyển");
        }

        if ($transportation_route->getStatus() == 'unloaded') {
            throw new HttpBadRequestException($this->request, "Lộ trình vận chuyển đã được đổ vào bồn chứa nguyên liệu");
        }

        if ($transportation_route->getStatus() !== 'arrived') {
            throw new HttpBadRequestException($this->request, "Xe chưa đến nhà máy, vui lòng xác nhận trước khi đổ nguyên liệu vào bồn chứa");
        }

        $formData = $this->getFormData();

        $validator = new Validator($this->request);

        $validator->validate('unloading_items', $formData['unloading_items'] ?? null, 'required|array');

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
        $unloading_items = $formData['unloading_items'] ?? [];

        if (empty($unloading_items) || !is_array($unloading_items)) {
            throw new HttpBadRequestException($this->request, "Dữ liệu đổ nguyên liệu không hợp lệ");
        }

        // Kiểm tra trùng bồn chứa
        $tank_ids = array_column($unloading_items, 'raw_material_tank_id');
        if (count($tank_ids) !== count(array_unique($tank_ids))) {
            throw new HttpBadRequestException($this->request, "Không được đổ vào cùng một bồn nhiều lần");
        }
        
        // Validate từng bồn chứa
        foreach ($unloading_items as $item) {
            $tank = $this->rawMaterialTankRepository->findRawMaterialTankOfId($item['raw_material_tank_id'] ?? 0);
            if (empty($tank)) {
                throw new HttpBadRequestException($this->request, "Bồn chứa nguyên liệu thô không tồn tại");
            }
            // Kiểm tra loại mủ khớp với loại bồn
            if ($tank->getTankType() !== $item['rubber_type']) {
                throw new HttpBadRequestException(
                    $this->request,
                    "Bồn {$tank->getName()} chỉ chứa {$tank->getTankType()}, không thể đổ {$item['rubber_type']}"
                );
            }

            // Kiểm tra trọng lượng thực tế
            $actual_weight = (float)$item['actual_weight'] ?? 0;
            if ($actual_weight <= 0) {
                throw new HttpBadRequestException(
                    $this->request,
                    "Trọng lượng thực tế đổ vào bồn {$tank->getName()} phải lớn hơn 0"
                );
            }
            // Kiểm tra dung tích
            $remaining = $tank->getCapacity() - $tank->getCurrentVolume();
            if ($actual_weight > $remaining) {
                throw new HttpBadRequestException(
                    $this->request,
                    "Bồn {$tank->getName()} không đủ dung tích. Còn trống: {$remaining}kg, yêu cầu: {$actual_weight}kg"
                );
            }
        }

        $data_update = [
            //"status" => "unloaded",
            "user_id" => (int)$this->auth_data['user_id'],
            "unloading_items" => $unloading_items
        ];

        $transportationRoute = $this->transportationRouteRepository->unloadTransportationRouteWithPermission(
            $transportation_route->getId(),
            $data_update,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($transportationRoute)) {
            throw new HttpBadRequestException($this->request, "Lỗi khi đổ nguyên liệu vào bồn chứa");
        }

        $action = 'unload';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'transportation_route',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$transportationRoute->getId(),
        );

        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['transportation_route'] = $transportationRoute->jsonSerialize();

        return $this->respondWithData($res_return);
        
    }
}
