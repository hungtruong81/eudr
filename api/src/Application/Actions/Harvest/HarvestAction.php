<?php

declare(strict_types=1);

namespace App\Application\Actions\Harvest;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\Harvest\HarvestRepository;
use App\Domain\Land\LandRepository;
use App\Domain\User\UserRepository;
use App\Domain\TransactionTicket\TransactionTicketRepository;
use Psr\Log\LoggerInterface;

abstract class HarvestAction extends Action
{
    /**
     * @var SettingsInterface
     */
    protected $settings;
    /**
     * @var HarvestRepository
     */
    protected $harvestRepository;
    /**
     * @var LandRepository
     */
    protected $landRepository;
    /**
     * @var UserRepository
     */
    protected $userRepository;
    /**
     * @var TransactionTicketRepository
     */
    protected $transactionTicketRepository;
    /**
     * @param LoggerInterface $logger
     * @param HarvestRepository $harvestRepository
     * @param LandRepository $landRepository
     * @param UserRepository $userRepository
     * @param TransactionTicketRepository $transactionTicketRepository
     * @param SettingsInterface $settings
     */
    public function __construct(
        LoggerInterface $logger,
        HarvestRepository $harvestRepository,
        LandRepository $landRepository,
        UserRepository $userRepository,
        TransactionTicketRepository $transactionTicketRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->harvestRepository = $harvestRepository;
        $this->landRepository = $landRepository;
        $this->userRepository = $userRepository;
        $this->transactionTicketRepository = $transactionTicketRepository;
        $this->settings = $settings;
    }
}
