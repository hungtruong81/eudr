<?php

declare(strict_types=1);

namespace App\Application\Actions\Pallet;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\Pallet\PalletRepository;
use App\Domain\RubberBlock\RubberBlockRepository;
use App\Domain\User\UserRepository;
use Psr\Log\LoggerInterface;

abstract class PalletAction extends Action
{
    protected $settings;
    protected $palletRepository;
    protected $userRepository;
    protected $rubberBlockRepository;

    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        UserRepository $userRepository,
        PalletRepository $palletRepository,
        RubberBlockRepository $rubberBlockRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->palletRepository = $palletRepository;
        $this->userRepository = $userRepository;
        $this->rubberBlockRepository = $rubberBlockRepository;
        $this->settings = $settings;
    }
}
