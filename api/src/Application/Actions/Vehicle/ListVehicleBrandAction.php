<?php

declare(strict_types=1);

namespace App\Application\Actions\Vehicle;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use App\Application\Utility\Utils;

class ListVehicleBrandAction extends VehicleAction
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

        $vehicle_brands = $this->vehicleRepository->findVehicleBrands();

        $res_return = ["result" => "success"];
        $res_return['data'] = $vehicle_brands;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }

}
