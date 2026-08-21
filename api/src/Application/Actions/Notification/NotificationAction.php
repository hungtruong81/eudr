<?php

declare(strict_types=1);

namespace App\Application\Actions\Notification;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\User\UserRepository;
use App\Domain\Notification\NotificationRepository;
use Psr\Log\LoggerInterface;

abstract class NotificationAction extends Action
{
    /**
     * @var SettingsInterface
     */
    protected $settings;
    /**
     * @var NotificationRepository
     */
    protected $notificationRepository;
    /**
     * @var UserRepository
     */
    protected $userRepository;
    /**
     * @param LoggerInterface $logger
     * @param NotificationRepository $notificationRepository
     * @param UserRepository $userRepository
     * @param SettingsInterface $settings
     */
    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        UserRepository $userRepository,
        NotificationRepository $notificationRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->notificationRepository = $notificationRepository;
        $this->userRepository = $userRepository;
        $this->settings = $settings;
    }
}
