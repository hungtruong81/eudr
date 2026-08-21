<?php

declare(strict_types=1);

namespace App\Application\Actions\General;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpUnauthorizedException;

class SettingsAction extends GeneralAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $companyId = (int)($this->auth_data['company_id'] ?? 0);
        if ($companyId <= 0) {
            throw new HttpBadRequestException($this->request, 'Không xác định công ty hiện tại');
        }

        $targetCodes = [
            'latex_price_per_tsc_kg',
            'scrap_rubber_price_per_drc_kg',
            'show_e_signature_box_land',
            'show_e_signature_box_plant',
            'show_e_signature_box_import_product_lot',
            'expected_sheet_quantity',
            'expected_cutting_weight_kg',
            'expected_cutting_sheet_quantity',
        ];

        $hardcodedDefaults = [
            'show_e_signature_box_land' => [
                'setting_code' => 'show_e_signature_box_land',
                'comment' => 'An/hien box chu ky dien tu cho module Land',
                'value' => '1',
            ],
            'show_e_signature_box_plant' => [
                'setting_code' => 'show_e_signature_box_plant',
                'comment' => 'An/hien box chu ky dien tu cho module Plant',
                'value' => '1',
            ],
            'show_e_signature_box_import_product_lot' => [
                'setting_code' => 'show_e_signature_box_import_product_lot',
                'comment' => 'An/hien box chu ky dien tu cho module Import Product Lot',
                'value' => '1',
            ],
        ];

        // 1) Ưu tiên lấy cấu hình của công ty hiện tại.
        $this->db->where('setting_code', $targetCodes, 'IN');
        $this->db->where('active', 1);
        $this->db->where('company_id', $companyId);
        $companySettings = $this->db->get('eudr_settings', null, 'setting_code,comment,value');

        $mapped = [];
        foreach ((array)$companySettings as $item) {
            $mapped[$item['setting_code']] = $item;
        }

        // 2) Với mã chưa có cấu hình công ty, fallback về cấu hình global (company_id IS NULL).
        $missingCodes = [];
        foreach ($targetCodes as $code) {
            if (!isset($mapped[$code])) {
                $missingCodes[] = $code;
            }
        }

        if (!empty($missingCodes)) {
            $this->db->where('setting_code', $missingCodes, 'IN');
            $this->db->where('active', 1);
            $this->db->where('company_id', null, 'IS');
            $globalSettings = $this->db->get('eudr_settings', null, 'setting_code,comment,value');
            foreach ((array)$globalSettings as $item) {
                $mapped[$item['setting_code']] = $item;
            }
        }

        // 3) Hardcode default = show (1) cho 3 module e-sign box khi cong ty chua thiet lap.
        foreach ($hardcodedDefaults as $code => $defaultSetting) {
            if (!isset($mapped[$code])) {
                $mapped[$code] = $defaultSetting;
            }
        }

        $settings = [];
        foreach ($targetCodes as $code) {
            if (isset($mapped[$code])) {
                $settings[] = $mapped[$code];
            }
        }

        $data_return = [
            "result" => "success",
            "data" => $settings
        ];

        return $this->respondWithData($data_return);
    }
}
