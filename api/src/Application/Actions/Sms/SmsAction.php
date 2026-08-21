<?php

declare(strict_types=1);

namespace App\Application\Actions\Sms;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\Sms\SmsRepository;
use App\Domain\User\UserRepository;
use Psr\Log\LoggerInterface;

abstract class SmsAction extends Action
{
    /**
     * @var db
     */
    protected $db;
    /**
     * @var SettingsInterface
     */
    protected $settings;
    /**
     * @var SmsRepository
     */
    protected $smsRepository;
    /**
     * @var UserRepository
     */
    protected $userRepository;
    /**
     * @var $phpMailer
     */
    protected $phpMailer;
    /**
     * @param LoggerInterface $logger
     * @param SmsRepository $smsRepository
     * @param UserRepository $userRepository
     * @param SettingsInterface $settings
     */
    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        SmsRepository $smsRepository,
        UserRepository $userRepository,
        SettingsInterface $settings
    ) {
        parent::__construct($logger);
        $this->db = $db;
        $this->smsRepository = $smsRepository;
        $this->userRepository = $userRepository;
        $this->settings = $settings;
    }
}
