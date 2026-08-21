<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductLot;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\User\UserRepository;
use App\Domain\ProductLot\ProductLotRepository;
use App\Domain\RubberBlock\RubberBlockRepository;
use App\Domain\Factory\FactoryRepository;
use App\Domain\Land\LandRepository;
use App\Domain\File\FileRepository;
use App\Domain\Grade\GradeRepository;
use Psr\Log\LoggerInterface;

abstract class ProductLotAction extends Action
{
    /**
     * @var SettingsInterface
     */
    protected $settings;
    /**
     * @var UserRepository
     */
    protected $userRepository;
    /**
     * @var ProductLotRepository
     */
    protected $productLotRepository;
    /**
     * @var RubberBlockRepository
     */
    protected $rubberBlockRepository;
    /**
     * @var FactoryRepository
     */
    protected $factoryRepository;
    /**
     * @var LandRepository
     */
    protected $landRepository;
    /**
     * @var FileRepository
     */
    protected $fileRepository;
    /**
     * @var GradeRepository
     */
    protected $gradeRepository;

    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        UserRepository $userRepository,
        ProductLotRepository $productLotRepository,
        RubberBlockRepository $rubberBlockRepository,
        FactoryRepository $factoryRepository,
        LandRepository $landRepository,
        FileRepository $fileRepository,
        GradeRepository $gradeRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->userRepository = $userRepository;
        $this->productLotRepository = $productLotRepository;
        $this->rubberBlockRepository = $rubberBlockRepository;
        $this->factoryRepository = $factoryRepository;
        $this->landRepository = $landRepository;
        $this->fileRepository = $fileRepository;
        $this->gradeRepository = $gradeRepository;
        $this->settings = $settings;
    }
}
