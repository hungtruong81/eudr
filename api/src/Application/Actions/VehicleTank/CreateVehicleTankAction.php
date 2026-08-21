<?php

declare(strict_types=1);

namespace App\Application\Actions\VehicleTank;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use App\Domain\VehicleTank\VehicleTankException;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

class CreateVehicleTankAction extends VehicleTankAction
{
    protected function action(): Response
    {
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'vehicle_tank', 'create');
        $vehicleScope = Utils::resolveScope($permissions, 'vehicle', 'view');

        if (empty($scope) || empty($vehicleScope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $data = $this->getFormData();
        $validator = new Validator($this->request);
        $rules = [
            'vehicle_id' => 'required|integer|min:1',
            'vehicle_tank_name' => 'required|string',
            'tank_type' => 'required|in:latex,cup_lump,scrap_rubber,mixed',
            'capacity_kg' => 'required|numeric|min:0.01',
            'compartment_no' => 'string',
            'status' => 'required|in:idle,loading,in_transit,unloading,cleaning,maintenance,inactive',
            'notes' => 'string',
        ];

        foreach ($rules as $field => $rule) {
            $validator->validate($field, $data[$field] ?? null, $rule);
        }

        if ($validator->hasErrors()) {
            throw new HttpBadRequestException($this->request, 'Dữ liệu bồn không hợp lệ');
        }

        $clean = $validator->sanitize($data, [
            'vehicle_id' => 'integer',
            'vehicle_tank_name' => 'string',
            'tank_type' => 'string',
            'capacity_kg' => 'float',
            'compartment_no' => 'string',
            'status' => 'string',
            'notes' => 'string',
        ]);

        $code = $this->vehicleTankRepository->generateCode();

        if ($this->vehicleTankRepository->findByCode($code)) {
            throw new HttpBadRequestException($this->request, 'Mã bồn đã tồn tại');
        }

        $vehicle = $this->vehicleRepository->findVehicleOfIdWithPermission(
            (int)$clean['vehicle_id'],
            (int)$this->auth_data['user_id'],
            (string)$vehicleScope,
            (int)($this->auth_data['company_id'] ?? 0)
        );

        if (!$vehicle) {
            throw new HttpBadRequestException($this->request, 'Phương tiện không hợp lệ');
        }

        try {
            $tank = $this->vehicleTankRepository->create([
                'vehicle_id' => $clean['vehicle_id'],
                'vehicle_tank_code' => $code,
                'vehicle_tank_name' => trim($clean['vehicle_tank_name']),
                'tank_type' => $clean['tank_type'],
                'capacity_kg' => $clean['capacity_kg'],
                'current_weight_kg' => 0,
                'compartment_no' => $clean['compartment_no'] ?? null,
                'status' => $clean['status'],
                'notes' => $clean['notes'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $this->auth_data['user_id'],
            ]);
        } catch (VehicleTankException $e) {
            throw new HttpBadRequestException($this->request, $e->getMessage());
        }

        if (!$tank) {
            throw new HttpBadRequestException($this->request, 'Không thể tạo bồn trên xe');
        }

        return $this->respondWithData([
            'result' => 'success',
            'vehicle_tank' => $tank->jsonSerialize(),
        ]);
    }
}
