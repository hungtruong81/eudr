<?php

declare(strict_types=1);

namespace App\Application\Actions\Grade;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\User\UserRepository;
use App\Domain\Grade\GradeRepository;
use Psr\Log\LoggerInterface;

abstract class GradeAction extends Action
{
    /**
     * @var SettingsInterface
     */
    protected $settings;
    /**
     * @var GradeRepository
     */
    protected $gradeRepository;
    /**
     * @var UserRepository
     */
    protected $userRepository;
    /**
     * @param LoggerInterface $logger
     * @param GradeRepository $gradeRepository
     * @param UserRepository $userRepository
     * @param SettingsInterface $settings
     */
    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        UserRepository $userRepository,
        GradeRepository $gradeRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->gradeRepository = $gradeRepository;
        $this->userRepository = $userRepository;
        $this->settings = $settings;
    }
}
