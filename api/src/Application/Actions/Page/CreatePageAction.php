<?php

declare(strict_types=1);

namespace App\Application\Actions\Page;

use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\Page\PageErrorException;
use App\Application\Utility\Utils;
use App\Domain\Workspace\WorkspaceErrorException;

class CreatePageAction extends PageAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new PageErrorException("PERMISSION_DENIED", 113);
        }

        $formData = $this->getFormData();

        // Validate data fields
        $required_fields = ['workspace_code','name','url','page_id','instagram_user_id'];
        $missing_fields = Utils::validFields($required_fields,$formData);
        if (!empty($missing_fields)) {
            throw new PageErrorException("MISSING ".implode(", ",$missing_fields), 101);
        }

        // check platform
        $url = $formData['url'];
        $logo_url = '';
        if ($url) {
            $logo_url = Utils::getFacebookPageLogo($url);
        }

        // Check Workspace
        $workspace_code = trim($formData['workspace_code']);
        $workspace = $this->workspaceRepository->findWorkspaceOfCode($workspace_code, $this->auth_data['user_id']);
        if (empty($workspace)) {
            throw new WorkspaceErrorException("WRONG_WORKSPACE", 101);
        }

        $page = $this->pageRepository->findPageOfPageId($formData['page_id'], $workspace->getId());
        if (!empty($page)) {
            throw new PageErrorException("Page already exists", 101);
        }

        // Create code
        $page_code = $this->pageRepository->generateCode();

        // Data Page
        $data_update = [
            "page_code" => $page_code,
            "workspace_id" => $workspace->getId(),
            "name" => $formData['name'],
            "fb_page_id" => $formData['page_id'],
            "instagram_user_id" => $formData['instagram_user_id'],
            "url" => $formData['url'],
            "logo_url" => $logo_url
        ];

        $page = $this->pageRepository->createPage($data_update);

        $action = 'create';
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
        $res_return['data']['page'] = $page;

        return $this->respondWithData($res_return);
    }
}
