<?php
declare(strict_types=1);

namespace App\Application\Actions\User;

use App\Application\Settings\SettingsInterface;
use App\Application\Actions\Action;
use App\Domain\User\UserRepository;

use Psr\Log\LoggerInterface;

abstract class UserAction extends Action
{
    /**
     * @var UserRepository
     */
    protected $userRepository;
    
    /**
     * @var SettingsInterface
     */
    protected $settings;
    /**
     * @param LoggerInterface $logger
     * @param UserRepository $userRepository
     */
    public function __construct(LoggerInterface $logger,
                                SettingsInterface $settings,
                                UserRepository $userRepository,
    ) {
        parent::__construct($logger);
        $this->settings = $settings;
        $this->userRepository = $userRepository;
    }
}
