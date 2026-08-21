<?php

declare(strict_types=1);

namespace App\Application\Actions\General;

use Psr\Http\Message\ResponseInterface as Response;

class GetDataProvinceAction extends GeneralAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        /*
        $cached = false;
        $cache_key = 'general_provinces_'.md5(json_encode(['all']));
        
        $json = $this->memcached->get($cache_key);

        if (!empty($json) && empty($formData["no_cache"])) {
            $provinces = json_decode($json, true);
            $cached = true;
        } else {
            $provinces = $this->db->get("eudr_general_provinces", NULL, "province_id,code,province_name,type");
            $this->memcached->set($cache_key, json_encode($provinces), 5*60);
        }
        */

        $provinces = $this->db->get("eudr_general_provinces", NULL, "province_id,code,province_name,type");

        $data_return = [
            "result" => "success",
            "provinces" => $provinces,
            //"cached" => $cached
        ];

        return $this->respondWithData($data_return);
    }
}
