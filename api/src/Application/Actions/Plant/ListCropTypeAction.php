<?php

declare(strict_types=1);

namespace App\Application\Actions\Plant;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use App\Application\Utility\Utils;

class ListCropTypeAction extends PlantAction
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

        $formData = $this->request->getQueryParams();

        $data_crop_types = $this->plantRepository->findAllCropTypes();
        
        $res_return = ["result" => "success"];
        $res_return['data'] = $data_crop_types;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }

}
