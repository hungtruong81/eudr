<?php

declare(strict_types=1);

namespace App\Application\Actions\Price;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\Price\PriceRepository;
use App\Domain\User\UserRepository;
use Psr\Log\LoggerInterface;

abstract class PriceAction extends Action
{
    /**
     * @var SettingsInterface
     */
    protected $settings;

    /**
     * @var PriceRepository
     */
    protected $priceRepository;

    /**
     * @var UserRepository
     */
    protected $userRepository;

    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        UserRepository $userRepository,
        PriceRepository $priceRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->priceRepository = $priceRepository;
        $this->userRepository = $userRepository;
        $this->settings = $settings;
    }
}
