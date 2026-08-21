<?php

declare(strict_types=1);

namespace App\Application\Actions\TransactionTicket;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\TransactionTicket\TransactionTicketRepository;
use App\Domain\User\UserRepository;
use App\Domain\Notification\NotificationRepository;
use App\Domain\Connection\ConnectionRepository;
use App\Domain\Land\LandRepository;
use Psr\Log\LoggerInterface;

abstract class TransactionTicketAction extends Action
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
     * @var TransactionTicketRepository
     */
    protected $transactionTicketRepository;
    /**
     * @var UserRepository
     */
    protected $userRepository;
    /**
     * @var NotificationRepository
     */
    protected $notificationRepository;
    /**
     * @var ConnectionRepository
     */
    protected $connectionRepository;
    /**
     * @var LandRepository
     */
    protected $landRepository;
    /**
     * @param LoggerInterface $logger
     * @param SettingRepository $settingRepository
     * @param UserRepository $userRepository
     * @param TransactionTicketRepository $transactionTicketRepository
     * @param NotificationRepository $notificationRepository
     * @param ConnectionRepository $connectionRepository
     * @param LandRepository $landRepository
     * @param SettingsInterface $settings
     */
    public function __construct(
        //\MysqliDb $db,
        LoggerInterface $logger,
        TransactionTicketRepository $transactionTicketRepository,
        UserRepository $userRepository,
        NotificationRepository $notificationRepository,
        ConnectionRepository $connectionRepository,
        LandRepository $landRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        //$this->db = $db;
        $this->transactionTicketRepository = $transactionTicketRepository;
        $this->userRepository = $userRepository;
        $this->notificationRepository = $notificationRepository;
        $this->connectionRepository = $connectionRepository;
        $this->landRepository = $landRepository;
        $this->settings = $settings;
    }
}
