<?php

declare(strict_types=1);

namespace App\Application\Actions\Plant;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\Plant\PlantRepository;
use App\Domain\Land\LandRepository;
use App\Domain\User\UserRepository;
use Psr\Log\LoggerInterface;

abstract class PlantAction extends Action
{
    /**
     * @var SettingsInterface
     */
    protected $settings;
    /**
     * @var PlantRepository
     */
    protected $plantRepository;
    /**
     * @var UserRepository
     */
    protected $userRepository;
    /**
     * @var LandRepository
     */
    protected $landRepository;
    /**
     * @param LoggerInterface $logger
     * @param PlantRepository $plantRepository
     * @param UserRepository $userRepository
     * @param LandRepository $landRepository
     * @param SettingsInterface $settings
     */
    public function __construct(
        LoggerInterface $logger,
        PlantRepository $plantRepository,
        UserRepository $userRepository,
        LandRepository $landRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->plantRepository = $plantRepository;
        $this->userRepository = $userRepository;
        $this->landRepository = $landRepository;
        $this->settings = $settings;
    }
}
