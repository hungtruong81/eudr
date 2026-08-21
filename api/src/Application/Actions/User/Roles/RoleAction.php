<?php

declare(strict_types=1);

namespace App\Application\Actions\User\Roles;

use App\Application\Actions\User\UserAction;
use App\Application\Settings\SettingsInterface;
use App\Domain\User\UserRepository;
use Psr\Log\LoggerInterface;

abstract class RoleAction extends UserAction
{
    public function __construct(
        LoggerInterface $logger,
        SettingsInterface $settings,
        UserRepository $userRepository,
    ) {
        parent::__construct($logger, $settings, $userRepository);
    }
}
