<?php

declare(strict_types=1);

namespace App\Application\Actions\ExternalMaterial;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class UpdateExternalMaterialAction extends ExternalMaterialAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        // Check Authentication
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        // Check permission
        $scope = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'finished_goods_receipt', 'update'); // external_material
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $code = addslashes(trim((string)$this->resolveArg('code')));

        $external_material = $this->externalMaterialRepository->findExternalMaterialOfCode($code);
        if (empty($external_material)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy nguyên liệu ngoài");
        }

        // Only allow update when status is draft
        if ($external_material->getStatus() !== 'draft') {
            throw new HttpBadRequestException($this->request, "Chỉ có thể cập nhật khi trạng thái là nháp (draft)");
        }

        $formData = $this->getFormData();

        $validator = new Validator($this->request);

        $validator->validate('factory_id', $formData['factory_id'] ?? null, 'required|integer|min:1');
        $validator->validate('supplier_name', $formData['supplier_name'] ?? null, 'required|string');
        $validator->validate('supplier_phone', $formData['supplier_phone'] ?? null, 'string');
        $validator->validate('supplier_address', $formData['supplier_address'] ?? null, 'string');
        $validator->validate('latex_weight', $formData['latex_weight'] ?? null, 'numeric|min:0');
        $validator->validate('latex_tsc_grade', $formData['latex_tsc_grade'] ?? null, 'numeric|min:0');
        $validator->validate('scrap_rubber_weight', $formData['scrap_rubber_weight'] ?? null, 'numeric|min:0');
        $validator->validate('scrap_rubber_drc_grade', $formData['scrap_rubber_drc_grade'] ?? null, 'numeric|min:0');
        $validator->validate('cup_lump_weight', $formData['cup_lump_weight'] ?? null, 'numeric|min:0');
        $validator->validate('total_amount', $formData['total_amount'] ?? null, 'numeric|min:0');
        $validator->validate('purchase_date', $formData['purchase_date'] ?? null, 'required|date');
        $validator->validate('notes', $formData['notes'] ?? null, 'string');

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

        $sanitizeRules = [
            'factory_id' => 'integer',
            'supplier_name' => 'string',
            'supplier_phone' => 'string',
            'supplier_address' => 'string',
            'latex_weight' => 'float',
            'latex_tsc_grade' => 'float',
            'scrap_rubber_weight' => 'float',
            'scrap_rubber_drc_grade' => 'float',
            'cup_lump_weight' => 'float',
            'total_amount' => 'float',
            'purchase_date' => 'date',
            'notes' => 'string',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        // Validate factory exists
        $factory = $this->factoryRepository->findFactoryOfId($cleanData['factory_id']);
        if (empty($factory)) {
            throw new HttpBadRequestException($this->request, "Nhà máy không tồn tại");
        }

        // Process plots if provided
        $lands_to_link = null;
        $plots = $formData['plots'] ?? null;
        if ($plots !== null && is_array($plots)) {
            // Delete old linked lands that were created as external
            $existing_lands = $this->externalMaterialRepository->findLandsByExternalMaterialId($external_material->getId());
            foreach ($existing_lands as $existing_land) {
                if (($existing_land['register_type'] ?? '') === 'external') {
                    $this->landRepository->deleteLand((int)$existing_land['plot_id'], $this->auth_data['user_id']);
                }
            }

            $lands_to_link = [];
            foreach ($plots as $index => $plot) {
                $plot_name = htmlspecialchars(trim($plot['plot_name'] ?? ''));
                if (empty($plot_name)) {
                    throw new HttpBadRequestException($this->request, "Vườn #" . ($index + 1) . ": Thiếu tên vườn");
                }

                $coordinates = $plot['coordinates'] ?? [];
                if (empty($coordinates) || !is_array($coordinates)) {
                    throw new HttpBadRequestException($this->request, "Vườn #" . ($index + 1) . " ($plot_name): Thiếu tọa độ");
                }

                foreach ($coordinates as $coord) {
                    if (!isset($coord['lat']) || !isset($coord['lng'])) {
                        throw new HttpBadRequestException($this->request, "Vườn #" . ($index + 1) . " ($plot_name): Tọa độ không hợp lệ");
                    }
                }

                // Check duplicate coordinates
                $is_duplicate = $this->landRepository->checkDuplicateCoordinates($coordinates, 0.000001, 0);
                if ($is_duplicate) {
                    throw new HttpBadRequestException($this->request, "Vườn #" . ($index + 1) . " ($plot_name): Tọa độ đã tồn tại trong hệ thống");
                }

                $land_area = floatval($plot['land_area'] ?? 0);
                $province_id = intval($plot['province_id'] ?? 0);
                $address = htmlspecialchars(trim($plot['address'] ?? ''));
                $harvest_weight = floatval($plot['harvest_weight'] ?? 0);
                $plot_notes = htmlspecialchars(trim($plot['notes'] ?? ''));

                // Create new Land with Unknown owner
                $land_code = $this->landRepository->generateCode();
                $land_data = [
                    "plot_code" => $land_code,
                    "plot_name" => $plot_name,
                    "farmer_user_id" => 0,
                    "farmer_name" => "Unknown - External",
                    "company_id" => $this->auth_data['company_id'] ?? 0,
                    "company_name" => "",
                    "ownership" => "external",
                    "land_records" => json_encode([]),
                    "land_document_detection" => 0,
                    "province_id" => $province_id,
                    "country" => "Vietnam",
                    "coordinate_origin_points" => json_encode([]),
                    "coordinates" => json_encode($coordinates),
                    "land_area" => $land_area,
                    "address" => $address,
                    "altitude_above_sea_level" => 0,
                    "soil" => "",
                    "status" => "active",
                    "maximum_yield" => 0,
                    "classify" => "",
                    "area_24" => 0,
                    "notes" => "Vườn từ nguồn nguyên liệu ngoài",
                    "register_type" => "external",
                    "created_by" => $this->auth_data['user_id'],
                    "created_at" => date("Y-m-d H:i:s"),
                ];

                $land = $this->landRepository->createLand($land_data);
                if (empty($land)) {
                    throw new HttpBadRequestException($this->request, "Vườn #" . ($index + 1) . " ($plot_name): Không thể tạo vườn");
                }

                $lands_to_link[] = [
                    "plot_id" => $land->getId(),
                    "harvest_weight" => $harvest_weight,
                    "notes" => $plot_notes,
                ];
            }
        }

        // Process transport
        $transport_data = null;
        $transport = $formData['transport'] ?? null;
        if ($transport !== null && is_array($transport)) {
            $transport_data = [
                "vehicle_license_plate" => htmlspecialchars(trim($transport['vehicle_license_plate'] ?? '')),
                "driver_name" => htmlspecialchars(trim($transport['driver_name'] ?? '')),
                "driver_phone" => htmlspecialchars(trim($transport['driver_phone'] ?? '')),
                "transport_date" => $transport['transport_date'] ?? null,
                "pickup_time" => $transport['pickup_time'] ?? null,
                "pickup_location" => htmlspecialchars(trim($transport['pickup_location'] ?? '')),
                "delivery_time" => $transport['delivery_time'] ?? null,
                "notes" => htmlspecialchars(trim($transport['notes'] ?? '')),
            ];
        }

        $data_update = [
            "factory_id" => $cleanData['factory_id'],
            "supplier_name" => $cleanData['supplier_name'],
            "supplier_phone" => $cleanData['supplier_phone'] ?? '',
            "supplier_address" => $cleanData['supplier_address'] ?? '',
            "latex_weight" => $cleanData['latex_weight'] ?? 0,
            "latex_tsc_grade" => $cleanData['latex_tsc_grade'] ?? 0,
            "scrap_rubber_weight" => $cleanData['scrap_rubber_weight'] ?? 0,
            "scrap_rubber_drc_grade" => $cleanData['scrap_rubber_drc_grade'] ?? 0,
            "cup_lump_weight" => $cleanData['cup_lump_weight'] ?? 0,
            "total_amount" => $cleanData['total_amount'] ?? 0,
            "purchase_date" => $cleanData['purchase_date'],
            "notes" => $cleanData['notes'] ?? '',
            "updated_by" => $this->auth_data['user_id'],
            "updated_at" => date("Y-m-d H:i:s"),
            "lands" => $lands_to_link,
            "transport" => $transport_data,
        ];

        $external_material = $this->externalMaterialRepository->updateExternalMaterial($external_material->getId(), $data_update);

        // Log action
        $log = [
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'external_material',
            "action" => 'update',
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$external_material->getId(),
        ];
        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['data'] = $external_material->jsonSerialize();

        return $this->respondWithData($res_return);
    }
}
