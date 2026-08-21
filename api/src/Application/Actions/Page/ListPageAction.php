<?php

declare(strict_types=1);

namespace App\Application\Actions\Page;

use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\Page\PageErrorException;
use App\Application\Utility\Utils;

class ListPageAction extends PageAction
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
            throw new PageErrorException("PERMISSION_DENIED", 113);
        }
        $formData = $this->request->getQueryParams();
        $workspace_code = addslashes(trim($this->resolveArg('code')));

        $limit = isset($formData['limit'])?intval($formData['limit']):10;
        if ($limit<1) {
            $limit = 10;
        }
        $params = ["page_limit" => $limit];
        if (!empty($workspace_code)) {
            $workspace_code = trim($workspace_code);
            $workspace = $this->workspaceRepository->findWorkspaceOfCode($workspace_code, $this->auth_data['user_id']);
            if (empty($workspace)) {
                throw new PageErrorException("WRONG_WORKSPACE", 101);
            }
            $params["workspace_id"] = $workspace->getId();
        } else {
            $params["workspace_id"] = 999999999;
        }
        if (!empty($formData["order_by"])) {
            $params["order_by"] = $formData["order_by"];
            if (!empty($formData["order_type"])) {
                $params["order_type"] = $formData["order_type"];
            }
        }
        if (!empty($formData["search"])) {
            $params["search"] = trim($formData["search"]);
        }

        $data = $this->pageRepository->findAll($params);

        $action = 'list';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'page',
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
