<?php

declare(strict_types=1);

namespace App\Application\Actions\General;

use Psr\Http\Message\ResponseInterface as Response;

class GetDataCompanyAction extends GeneralAction
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
        $this->db->where("status", "active");
        $companies = $this->db->get("eudr_companies", NULL, "company_code,company_name,short_name,address");

        $data_return = [
            "result" => "success",
            "companies" => $companies,
            //"cached" => $cached
        ];

        return $this->respondWithData($data_return);
    }
}
