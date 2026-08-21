<?php

declare(strict_types=1);

namespace App\Application\Actions\Sales;

use App\Application\Actions\Action;
use App\Application\Settings\SettingsInterface;
use App\Domain\User\UserRepository;
use App\Domain\Sales\Customer\SalesCustomerRepository;
use App\Domain\Sales\Contract\SalesContractRepository;
use App\Domain\Sales\Order\SalesOrderRepository;
use App\Domain\Sales\Issue\SalesIssueRepository;
use App\Domain\ProductTank\ProductTankRepository;
use App\Domain\ProductType\ProductTypeRepository;
use App\Domain\ProductLot\ProductLotRepository;
use App\Domain\Connection\ConnectionRepository;
use Psr\Log\LoggerInterface;

abstract class SalesAction extends Action
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
     * @var SalesCustomerRepository
     */
    protected $salesCustomerRepository;
    /**
     * @var SalesContractRepository
     */
    protected $salesContractRepository;
    /**
     * @var SalesOrderRepository
     */
    protected $salesOrderRepository;
    /**
     * @var SalesIssueRepository
     */
    protected $salesIssueRepository;
    /**
     * @var ProductTankRepository
     */
    protected $productTankRepository;
    /**
     * @var ProductTypeRepository
     */
    protected $productTypeRepository;
    /**
     * @var ProductLotRepository
     */
    protected $productLotRepository;
    /**
     * @var ConnectionRepository
     */
    protected $connectionRepository;

    /**
     * @param \MysqliDb $db
     * @param LoggerInterface $logger
     * @param UserRepository $userRepository
     * @param SalesCustomerRepository $salesCustomerRepository
     * @param SalesContractRepository $salesContractRepository
     * @param SalesOrderRepository $salesOrderRepository
     * @param SalesIssueRepository $salesIssueRepository
     * @param ProductTankRepository $productTankRepository
     * @param ProductTypeRepository $productTypeRepository
     * @param ProductLotRepository $productLotRepository
     * @param ConnectionRepository $connectionRepository
     * @param SettingsInterface $settings
     */
    public function __construct(
        \MysqliDb $db,
        LoggerInterface $logger,
        UserRepository $userRepository,
        SalesCustomerRepository $salesCustomerRepository,
        SalesContractRepository $salesContractRepository,
        SalesOrderRepository $salesOrderRepository,
        SalesIssueRepository $salesIssueRepository,
        ProductTankRepository $productTankRepository,
        ProductTypeRepository $productTypeRepository,
        ProductLotRepository $productLotRepository,
        ConnectionRepository $connectionRepository,
        SettingsInterface $settings,
    ) {
        parent::__construct($logger);
        $this->userRepository = $userRepository;
        $this->salesCustomerRepository = $salesCustomerRepository;
        $this->salesContractRepository = $salesContractRepository;
        $this->salesOrderRepository = $salesOrderRepository;
        $this->salesIssueRepository = $salesIssueRepository;
        $this->productTankRepository = $productTankRepository;
        $this->productTypeRepository = $productTypeRepository;
        $this->productLotRepository = $productLotRepository;
        $this->connectionRepository = $connectionRepository;
        $this->settings = $settings;
    }
}
