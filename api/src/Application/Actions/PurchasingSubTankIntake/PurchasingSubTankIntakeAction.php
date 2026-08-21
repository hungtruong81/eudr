<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingSubTankIntake;

use App\Application\Actions\Action;
use App\Domain\PurchasingOrder\PurchasingOrderRepository;
use App\Domain\PurchasingSubTank\PurchasingSubTankRepository;
use App\Domain\PurchasingSubTankIntake\PurchasingSubTankIntakeRepository;
use App\Domain\User\UserRepository;
use Psr\Log\LoggerInterface;

abstract class PurchasingSubTankIntakeAction extends Action
{

    protected UserRepository $userRepository;
    protected PurchasingSubTankRepository $subTankRepository;
    protected PurchasingSubTankIntakeRepository $intakeRepository;
    protected PurchasingOrderRepository $purchasingOrderRepository;

    public function __construct(
        LoggerInterface $logger,
        UserRepository $userRepository,
        PurchasingSubTankRepository $subTankRepository,
        PurchasingSubTankIntakeRepository $intakeRepository,
        PurchasingOrderRepository $purchasingOrderRepository,
    ) {
        parent::__construct($logger);
        $this->userRepository = $userRepository;
        $this->subTankRepository = $subTankRepository;
        $this->intakeRepository = $intakeRepository;
        $this->purchasingOrderRepository = $purchasingOrderRepository;
    }
}
