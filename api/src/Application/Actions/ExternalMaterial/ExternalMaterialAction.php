<?php

declare(strict_types=1);

namespace App\Application\Actions\ExternalMaterial;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\ExternalMaterial\ExternalMaterialRepository;
use App\Domain\User\UserRepository;
use App\Domain\Factory\FactoryRepository;
use App\Domain\Land\LandRepository;
use Psr\Log\LoggerInterface;

abstract class ExternalMaterialAction extends Action
{
    /**
     * @var ExternalMaterialRepository
     */
    protected $externalMaterialRepository;

    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @var FactoryRepository
     */
    protected $factoryRepository;

    /**
     * @var LandRepository
     */
    protected $landRepository;

    /**
     * @var SettingsInterface
     */
    protected $settings;

    public function __construct(
        LoggerInterface $logger,
        ExternalMaterialRepository $externalMaterialRepository,
        UserRepository $userRepository,
        FactoryRepository $factoryRepository,
        LandRepository $landRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->externalMaterialRepository = $externalMaterialRepository;
        $this->userRepository = $userRepository;
        $this->factoryRepository = $factoryRepository;
        $this->landRepository = $landRepository;
        $this->settings = $settings;
    }
}
