<?php

declare(strict_types=1);

namespace App\Application\Actions\Land;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\Land\LandRepository;
use App\Domain\User\UserRepository;
use App\Domain\File\FileRepository;
use App\Domain\Connection\ConnectionRepository;
use App\Domain\TransactionTicket\TransactionTicketRepository;

use Psr\Log\LoggerInterface;

abstract class LandAction extends Action
{
    /**
     * @var SettingsInterface
     */
    protected $settings;
    /**
     * @var LandRepository
     */
    protected $landRepository;
    /**
     * @var UserRepository
     */
    protected $userRepository;
    /**
     * @var FileRepository
     */
    protected $fileRepository;
    /**
     * @var ConnectionRepository
     */
    protected $connectionRepository;
    /**
     * @var TransactionTicketRepository
     */
    protected $transactionTicketRepository;
    /**
     * @param LoggerInterface $logger
     * @param LandRepository $landRepository
     * @param UserRepository $userRepository
     * @param FileRepository $fileRepository
     * @param ConnectionRepository $connectionRepository
     * @param TransactionTicketRepository $transactionTicketRepository
     * @param SettingsInterface $settings
     */
    public function __construct(
        LoggerInterface $logger,
        LandRepository $landRepository,
        UserRepository $userRepository,
        FileRepository $fileRepository,
        ConnectionRepository $connectionRepository,
        TransactionTicketRepository $transactionTicketRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->landRepository = $landRepository;
        $this->userRepository = $userRepository;
        $this->fileRepository = $fileRepository;
        $this->connectionRepository = $connectionRepository;
        $this->transactionTicketRepository = $transactionTicketRepository;
        $this->settings = $settings;
    }
}
