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


class UpdateTransportationRouteAction extends TransportationRouteAction
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
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập",);
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);

        // Check permission to update transportation routes
        $scope = Utils::resolveScope($permissions, 'transportation_route', 'update');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $transportation_route_code = $this->resolveArg('code');

        $transportation_route = $this->transportationRouteRepository->findTransportationRouteOfCodeWithPermission(
            $transportation_route_code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($transportation_route)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy lộ trình vận chuyển");
        }

        // Only allow update when status is 'pending'
        if($transportation_route->getStatus() !== 'pending') {
            throw new HttpBadRequestException($this->request, "Chỉ được phép cập nhật lộ trình ở trạng thái chờ xử lý");
        }

        $formData = $this->getFormData();

        $validator = new Validator($this->request);

        $validator->validate('vehicle_id', $formData['vehicle_id'] ?? null, 'required|integer');
        $validator->validate('driver_id', $formData['driver_id'] ?? null, 'integer');
        $validator->validate('driver_name', $formData['driver_name'] ?? null, 'required|string');
        $validator->validate('transport_date', $formData['transport_date'] ?? null, 'required|date');
        $validator->validate('pickup_time', $formData['pickup_time'] ?? null, 'required|string');
        $validator->validate('source_type', $formData['source_type'] ?? null, 'required|in:purchase_ticket,factory');
        $validator->validate('source_transaction_ticket_ids', $formData['source_transaction_ticket_ids'] ?? null, 'required|array');
        $validator->validate('source_factory_id', $formData['source_factory_id'] ?? null, 'integer');
        $validator->validate('destination_factory_id', $formData['destination_factory_id'] ?? null, 'required|integer');
        $validator->validate('destination_raw_material_tank_id', $formData['destination_raw_material_tank_id'] ?? null, 'integer');

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
            'vehicle_id' => 'integer',
            'driver_id' => 'integer',
            'driver_name' => 'string',
            'transport_date' => 'date',
            'pickup_time' => 'string',
            'source_type' => 'string',
            'source_factory_id' => 'integer',
            'destination_factory_id' => 'integer',
            'destination_raw_material_tank_id' => 'integer'
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $vehicle_id = $cleanData['vehicle_id'];
        $driver_id = $cleanData['driver_id'] ?? 0;
        $driver_name = $cleanData['driver_name'] ?? '';
        $transport_date = $cleanData['transport_date'];
        $pickup_time = $cleanData['pickup_time'];
        $source_type = $cleanData['source_type'];
        $source_transaction_ticket_ids = $formData['source_transaction_ticket_ids'] ?? [];
        $source_factory_id = $cleanData['source_factory_id'] ?? 0;
        $destination_factory_id = $cleanData['destination_factory_id'] ?? 0;
        $destination_raw_material_tank_id = $cleanData['destination_raw_material_tank_id'] ?? 0;

        // Validate input data
        if ($source_type === 'purchase_ticket' && (empty($source_transaction_ticket_ids) || !is_array($source_transaction_ticket_ids))) {
            throw new HttpBadRequestException($this->request, "Danh sách phiếu mua không hợp lệ");
        }

        if (count($source_transaction_ticket_ids) !== count(array_unique($source_transaction_ticket_ids))) {
            throw new HttpBadRequestException($this->request, "Danh sách phiếu mua không hợp lệ");
        }

        // Check vehicle exists
        $data_vehicle = $this->vehicleRepository->findVehicleOfId($vehicle_id);
        if (empty($data_vehicle)) {
            throw new HttpNotFoundException($this->request, "Xe vận chuyển không tồn tại");
        }

        // Check transaction ticket exists
        $data_transaction_ticket = $this->transactionTicketRepository->findPurchaseTicketsByIds($source_transaction_ticket_ids, $this->auth_data['user_id']);
        if (count($data_transaction_ticket) != count($source_transaction_ticket_ids)) {
            throw new HttpBadRequestException($this->request, "Danh sách phiếu mua không hợp lệ");
        }
        
        // Check destination factory exists
        $data_factory = $this->factoryRepository->findFactoryOfId($destination_factory_id);
        if (empty($data_factory)) {
            throw new HttpBadRequestException($this->request, "Nhà máy không tìm thấy");
        }
        
        // Data Transportation Route
        $data_update = [
            "vehicle_id" => $vehicle_id,
            "driver_id" => $driver_id,
            "driver_name" => $driver_name,
            "transport_date" => $transport_date,
            "pickup_time" => $pickup_time,
            "source_type" => $source_type,
            "source_transaction_ticket_ids" => $source_transaction_ticket_ids,
            "source_factory_id" => $source_factory_id,
            "destination_factory_id" => $destination_factory_id,
            "destination_raw_material_tank_id" => $destination_raw_material_tank_id,
            "updated_at" => date("Y-m-d H:i:s", time()),
            "updated_by" => $this->auth_data['user_id'],
        ];

        $transportation_route = $this->transportationRouteRepository->updateTransportationRouteWithPermission(
            $transportation_route->getId(),
            $data_update,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        $action = 'update';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'transportation_route',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$transportation_route->getId(),
        );

        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['transportation_route'] = $transportation_route->jsonSerialize();

        return $this->respondWithData($res_return);
        
    }
}
