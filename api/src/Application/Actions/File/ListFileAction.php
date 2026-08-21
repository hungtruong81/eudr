<?php

declare(strict_types=1);

namespace App\Application\Actions\File;

use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\File\FileErrorException;
use App\Application\Utility\Utils;

class ListFileAction extends FileAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        // Validate API type
        if (empty($this->auth_data['user_id'])) {
            throw new FileErrorException("PERMISSION_DENIED", 113);
        }
        $formData = $this->request->getQueryParams();

        // Validate data fields
        /* $required_fields = [];
        $missing_fields = Utils::validFields($required_fields,$formData);
        if (!empty($missing_fields)) {
            throw new FileErrorException("MISSING ".implode(", ",$missing_fields), 101);
        } */

        $params = ["page_limit" => 100];
        $params["user_id"] = $this->auth_data['user_id'];

        if (!empty($formData["order_by"])) {
            $params["order_by"] = $formData["order_by"];
            if (!empty($formData["order_type"])) {
                $params["order_type"] = $formData["order_type"];
            }
        }

        $data = $this->fileRepository->findAll($params);

        $action = 'list';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'file',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => '',
        );
        Utils::save_log($this->logger,$log);

        $res_return = ["result" => "success"];
        $res_return['data'] = $data;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }
}
