<?php

declare(strict_types=1);

namespace App\Application\Actions\Connection;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\User\UserRepository;
use App\Domain\Connection\ConnectionRepository;
use App\Domain\Notification\NotificationRepository;
use Psr\Log\LoggerInterface;

abstract class ConnectionAction extends Action
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
     * @var ConnectionRepository
     */
    protected $connectionRepository;
    /**
     * @var UserRepository
     */
    protected $userRepository;
    /**
     * @var NotificationRepository
     */
    protected $notificationRepository;
    /**
     * @param LoggerInterface $logger
     * @param ConnectionRepository $connectionRepository
     * @param UserRepository $userRepository
     * @param NotificationRepository $notificationRepository
     * @param SettingsInterface $settings
     */
    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        ConnectionRepository $connectionRepository,
        UserRepository $userRepository,
        NotificationRepository $notificationRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->db = $db;
        $this->connectionRepository = $connectionRepository;
        $this->userRepository = $userRepository;
        $this->notificationRepository = $notificationRepository;
        $this->settings = $settings;
    }
}
