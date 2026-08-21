<?php

declare(strict_types=1);

namespace App\Application\Actions\Page;

use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\Page\PageErrorException;
use App\Domain\Workspace\WorkspaceErrorException;

use App\Application\Utility\Utils;

class UpdatePageAction extends PageAction
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

        $page_code = addslashes(trim($this->resolveArg('code')));

        // Validate data fields
        $required_fields = ['workspace_code'];
        $missing_fields = Utils::validFields($required_fields,$formData);
        if (!empty($missing_fields)) {
            throw new PageErrorException("MISSING ".implode(", ",$missing_fields), 101);
        }
        // Check Workspace
        $workspace_code = trim($formData['workspace_code']);
        $workspace_code = trim($workspace_code);
        $workspace = $this->workspaceRepository->findWorkspaceOfCode($workspace_code, $this->auth_data['user_id']);
        if (empty($workspace)) {
            throw new WorkspaceErrorException("WRONG_WORKSPACE", 101);
        }

        $page = $this->pageRepository->findPageOfCode($page_code, $workspace->getId());
        if (empty($page)) {
            throw new PageErrorException("Page not found", 101);
        }

        if (!empty($formData['page_id'])) {
            $check_exits_app = $this->pageRepository->findPageOfPageId($formData['page_id'], $workspace->getId());
            if ($check_exits_app && $check_exits_app->getId() != $page->getId()) {
                throw new PageErrorException("Page already exists", 101);
            }
        }

        $data_update = [];

        if (!empty($formData['name'])) {
            $data_update['name'] = addslashes(trim($formData['name']));
        }
        if (!empty($formData['url'])) {
            $data_update['url'] = addslashes(trim($formData['url']));

            $logo_url = Utils::getFacebookPageLogo($formData['url']);
            $data_update['logo_url'] = $logo_url;
        }
        if (!empty($formData['page_id'])) {
            $data_update['fb_page_id'] = addslashes(trim($formData['page_id']));
        }

        $page = $this->pageRepository->updatePage($page->getId(), $data_update);

        $action = 'update';
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
