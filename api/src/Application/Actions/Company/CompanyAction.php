<?php

declare(strict_types=1);

namespace App\Application\Actions\Company;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\User\UserRepository;
use App\Domain\Company\CompanyRepository;
use App\Domain\CompanyGroup\CompanyGroupRepository;
use Psr\Log\LoggerInterface;

abstract class CompanyAction extends Action
{
    /**
     * @var SettingsInterface
     */
    protected $settings;
    /**
     * @var CompanyRepository
     */
    protected $companyRepository;
    /**
     * @var CompanyGroupRepository
     */
    protected $companyGroupRepository;
    /**
     * @var UserRepository
     */
    protected $userRepository;
    /**
     * @param LoggerInterface $logger
     * @param CompanyRepository $companyRepository
     * @param UserRepository $userRepository
     * @param CompanyGroupRepository $companyGroupRepository
     * @param SettingsInterface $settings
     */
    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        UserRepository $userRepository,
        CompanyRepository $companyRepository,
        CompanyGroupRepository $companyGroupRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->companyRepository = $companyRepository;
        $this->userRepository = $userRepository;
        $this->companyGroupRepository = $companyGroupRepository;
        $this->settings = $settings;
    }
}
