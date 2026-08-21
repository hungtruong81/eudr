<?php

declare(strict_types=1);

namespace App\Application\Actions\Mail;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\Mail\MailRepository;
use App\Domain\User\UserRepository;
use Psr\Log\LoggerInterface;
use PHPMailer\PHPMailer\PHPMailer;

abstract class MailAction extends Action
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
     * @var MailRepository
     */
    protected $mailRepository;
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
     * @param MailRepository $mailRepository
     * @param UserRepository $userRepository
     * @param SettingsInterface $settings
     * @param PHPMailer $phpMailer
     */
    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        MailRepository $mailRepository,
        UserRepository $userRepository,
        SettingsInterface $settings,
        PHPMailer $phpMailer
    ) {
        parent::__construct($logger);
        $this->db = $db;
        $this->mailRepository = $mailRepository;
        $this->userRepository = $userRepository;
        $this->phpMailer = $phpMailer;
        $this->settings = $settings;
    }
}
