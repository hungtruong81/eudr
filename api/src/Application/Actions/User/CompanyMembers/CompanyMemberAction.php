<?php

declare(strict_types=1);

namespace App\Application\Actions\User\CompanyMembers;

use App\Application\Actions\User\UserAction;
use App\Application\Settings\SettingsInterface;
use App\Domain\User\UserRepository;
use App\Domain\CompanyMember\CompanyMemberRepository;
use Psr\Log\LoggerInterface;

abstract class CompanyMemberAction extends UserAction
{
    /**
     * @var CompanyMemberRepository
     */
    protected $companyMemberRepository;

    /**
     * @param LoggerInterface      $logger
     * @param SettingsInterface    $settings
     * @param UserRepository       $userRepository
     * @param CompanyMemberRepository $companyMemberRepository
     */
    public function __construct(
        LoggerInterface $logger,
        SettingsInterface $settings,
        UserRepository $userRepository,
        CompanyMemberRepository $companyMemberRepository,
    ) {
        parent::__construct($logger, $settings, $userRepository);
        $this->companyMemberRepository = $companyMemberRepository;
    }
}
