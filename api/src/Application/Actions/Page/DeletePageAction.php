<?php

declare(strict_types=1);

namespace App\Application\Actions\Page;

use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\Page\PageErrorException;

use App\Application\Utility\Utils;

class DeletePageAction extends PageAction
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

        $formData = $this->getFormData();

        // Validate data fields
        $required_fields = ['workspace_code','page_code'];
        $missing_fields = Utils::validFields($required_fields,$formData);
        if (!empty($missing_fields)) {
            throw new PageErrorException("MISSING ".implode(", ",$missing_fields), 101);
        }

        // Check Workspace
        $workspace_code = trim($formData['workspace_code']);
        $workspace = $this->workspaceRepository->findWorkspaceOfCode($workspace_code, $this->auth_data['user_id']);
        if (empty($workspace)) {
            throw new PageErrorException("WRONG_WORKSPACE", 101);
        }

        $page_code = addslashes(trim($formData['page_code']));
        $page = $this->pageRepository->findPageOfCode($page_code, $workspace->getId());
        if (empty($page)) {
            throw new PageErrorException("APP_NOT_FOUND", 101);
        }

        $this->pageRepository->deletePage($page->getId());

        $action = 'delete';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'page',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$page->getId(),
        );
        Utils::save_log($this->logger,$log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }
}
