<?php

declare(strict_types=1);

namespace App\Application\Actions\RawMaterialTank;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\User\UserRepository;
use App\Domain\RawMaterialTank\RawMaterialTankRepository;
use App\Domain\Factory\FactoryRepository;
use Psr\Log\LoggerInterface;

abstract class RawMaterialTankAction extends Action
{
    /**
     * @var SettingsInterface
     */
    protected $settings;
    /**
     * @var FactoryRepository
     */
    protected $factoryRepository;
    /**
     * @var RawMaterialTankRepository
     */
    protected $rawMaterialTankRepository;
    /**
     * @var UserRepository
     */
    protected $userRepository;
    /**
     * @param LoggerInterface $logger
     * @param RawMaterialTankRepository $rawMaterialTankRepository
     * @param FactoryRepository $factoryRepository
     * @param UserRepository $userRepository
     * @param SettingsInterface $settings
     */
    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        UserRepository $userRepository,
        RawMaterialTankRepository $rawMaterialTankRepository,
        FactoryRepository $factoryRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->rawMaterialTankRepository = $rawMaterialTankRepository;
        $this->userRepository = $userRepository;
        $this->factoryRepository = $factoryRepository;
        $this->settings = $settings;
    }
}
