<?php

declare(strict_types=1);

namespace App\Application\Actions\General;

use Psr\Http\Message\ResponseInterface as Response;

class GetDataZoneAction extends GeneralAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        
        $zones = $this->db->get("eudr_general_vn2000_zones", NULL, "zone_id,zone_name,value");

        $data_return = [
            "result" => "success",
            "zones" => $zones,
        ];

        return $this->respondWithData($data_return);
    }
}
