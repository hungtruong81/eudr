<?php

declare(strict_types=1);

namespace App\Application\Actions\User\CompanyGroups;

use App\Application\Actions\User\UserAction;
use App\Application\Settings\SettingsInterface;
use App\Domain\User\UserRepository;
use App\Domain\CompanyGroup\CompanyGroupRepository;
use Psr\Log\LoggerInterface;

abstract class CompanyGroupAction extends UserAction
{
    /**
     * @var CompanyGroupRepository
     */
    protected $companyGroupRepository;

    /**
     * @param LoggerInterface      $logger
     * @param SettingsInterface    $settings
     * @param UserRepository       $userRepository
     * @param CompanyGroupRepository $companyGroupRepository
     */
    public function __construct(
        LoggerInterface $logger,
        SettingsInterface $settings,
        UserRepository $userRepository,
        CompanyGroupRepository $companyGroupRepository,
    ) {
        parent::__construct($logger, $settings, $userRepository);
        $this->companyGroupRepository = $companyGroupRepository;
    }
}
