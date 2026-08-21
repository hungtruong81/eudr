<?php

declare(strict_types=1);

namespace App\Application\Actions\Page;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\Page\PageRepository;
use App\Domain\Workspace\WorkspaceRepository;

use Psr\Log\LoggerInterface;

abstract class PageAction extends Action
{
    /**
     * @var SettingsInterface
     */
    protected $settings;
    /**
     * @var PageRepository
     */
    protected $pageRepository;
    /**
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @param LoggerInterface $logger
     * @param PageRepository $pageRepository
     * @param SettingsInterface $settings
     * @param WorkspaceRepository $workspaceRepository
     */
    public function __construct(
        LoggerInterface $logger,
        PageRepository $pageRepository,
        SettingsInterface $settings,
        WorkspaceRepository $workspaceRepository
    ) {
        parent::__construct($logger);
        $this->pageRepository = $pageRepository;
        $this->settings = $settings;
        $this->workspaceRepository = $workspaceRepository;
    }
}
