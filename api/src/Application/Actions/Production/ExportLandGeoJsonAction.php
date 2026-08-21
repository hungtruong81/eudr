<?php

declare(strict_types=1);

namespace App\Application\Actions\Production;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use App\Application\Utility\Utils;

class ExportLandGeoJsonAction extends ProductionAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        // Check user authentication
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        // Check permission to export land GeoJSON
        /*
        $scope = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'production', 'dds_export');
        if (empty($scope)) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }
        */
        $formData = $this->request->getQueryParams();

        $sale_contract_id = $formData['sale_contract_id'] ?? null;
        $product_lot_id = $formData['product_lot_id'] ?? null;
        $plot_id = $formData['plot_id'] ?? null;

        // Lấy record theo plot_id
        $this->db->where("plot_id", $plot_id);
        $land = $this->db->getOne("eudr_lands", ["plot_id", "plot_code", "plot_name", "land_area", "coordinates"]);

        if (!$land) {
            http_response_code(404);
            echo json_encode(["error" => "Land not found"]);
            exit;
        }

        $coords = json_decode($land['coordinates'], true) ?: [];

        $polygon = [];
        foreach ($coords as $c) {
            $lng = (float)$c['lng'];
            $lat = (float)$c['lat'];
            $polygon[] = [$lng, $lat];
        }

        // Đảm bảo polygon đóng kín
        if (count($polygon) > 2) {
            $first = $polygon[0];
            $last  = end($polygon);
            if ($first !== $last) {
                $polygon[] = $first;
            }
        }

        // Build GeoJSON
        $geoJson = [
            "type" => "FeatureCollection",
            "features" => [[
                "type" => "Feature",
                "properties" => [
                    "ProducerName"       => "DONARUCO",
                    "ProducerCountry"    => "VN",
                    "ProductionPlace"    => $land['plot_code'],
                    "Plantation"         => $land['plot_name'],
                    "Plot"               => $land['plot_id'],
                    "Planting_year"      => null,
                    "Area_hectare"       => (float)$land['land_area'],
                    "Start_tapping_Year" => null,
                    "Find"               => $land['plot_code'] . " " . $land['plot_name']
                ],
                "id" => $land['plot_id'],
                "geometry" => [
                    "type" => "Polygon",
                    "coordinates" => [$polygon]
                ]
            ]]
        ];

        // Xuất file JSON ra trình duyệt
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="land_' . $land['plot_id'] . '.json"');
        echo json_encode($geoJson, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;

        return $this->respondWithData($formData);
    }
}
