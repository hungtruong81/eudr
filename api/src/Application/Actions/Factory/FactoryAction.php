<?php

declare(strict_types=1);

namespace App\Application\Actions\Factory;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\User\UserRepository;
use App\Domain\Factory\FactoryRepository;
use Psr\Log\LoggerInterface;

abstract class FactoryAction extends Action
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
     * @var UserRepository
     */
    protected $userRepository;
    /**
     * @param LoggerInterface $logger
     * @param FactoryRepository $factoryRepository
     * @param UserRepository $userRepository
     * @param SettingsInterface $settings
     */
    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        UserRepository $userRepository,
        FactoryRepository $factoryRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->factoryRepository = $factoryRepository;
        $this->userRepository = $userRepository;
        $this->settings = $settings;
    }
}
