<?php

declare(strict_types=1);

use App\Domain\User\UserRepository;
use App\Infrastructure\Persistence\User\InDatabaseUserRepository;
use App\Domain\File\FileRepository;
use App\Infrastructure\Persistence\File\InDatabaseFileRepository;
use App\Domain\Page\PageRepository;
use App\Infrastructure\Persistence\Page\InDatabasePageRepository;
use App\Domain\Land\LandRepository;
use App\Infrastructure\Persistence\Land\InDatabaseLandRepository;
use App\Domain\Plant\PlantRepository;
use App\Infrastructure\Persistence\Plant\InDatabasePlantRepository;
use App\Domain\Harvest\HarvestRepository;
use App\Infrastructure\Persistence\Harvest\InDatabaseHarvestRepository;
use App\Domain\Production\ProductionRepository;
use App\Infrastructure\Persistence\Production\InDatabaseProductionRepository;
use App\Domain\Mail\MailRepository;
use App\Infrastructure\Persistence\Mail\InDatabaseMailRepository;
use App\Domain\Sms\SmsRepository;
use App\Infrastructure\Persistence\Sms\InDatabaseSmsRepository;
use App\Domain\Vehicle\VehicleRepository;
use App\Infrastructure\Persistence\Vehicle\InDatabaseVehicleRepository;
use App\Domain\VehicleTank\VehicleTankRepository;
use App\Infrastructure\Persistence\VehicleTank\InDatabaseVehicleTankRepository;
// use App\Domain\Driver\DriverRepository;
// use App\Infrastructure\Persistence\Driver\InDatabaseDriverRepository;
use App\Domain\Connection\ConnectionRepository;
use App\Infrastructure\Persistence\Connection\InDatabaseConnectionRepository;
use App\Domain\Notification\NotificationRepository;
use App\Infrastructure\Persistence\Notification\InDatabaseNotificationRepository;
use App\Domain\TransactionTicket\TransactionTicketRepository;
use App\Infrastructure\Persistence\TransactionTicket\InDatabaseTransactionTicketRepository;
use App\Domain\Factory\FactoryRepository;
use App\Infrastructure\Persistence\Factory\InDatabaseFactoryRepository;
use App\Domain\Warehouse\WarehouseRepository;
use App\Infrastructure\Persistence\Warehouse\InDatabaseWarehouseRepository;
use App\Domain\Grade\GradeRepository;
use App\Infrastructure\Persistence\Grade\InDatabaseGradeRepository;
use App\Domain\RawMaterialTank\RawMaterialTankRepository;
use App\Infrastructure\Persistence\RawMaterialTank\InDatabaseRawMaterialTankRepository;
use App\Domain\PurchasingSubTank\PurchasingSubTankRepository;
use App\Infrastructure\Persistence\PurchasingSubTank\InDatabasePurchasingSubTankRepository;
use App\Domain\PurchasingSubTankIntake\PurchasingSubTankIntakeRepository;
use App\Infrastructure\Persistence\PurchasingSubTankIntake\InDatabasePurchasingSubTankIntakeRepository;
use App\Domain\PurchasingOrder\PurchasingOrderRepository;
use App\Infrastructure\Persistence\PurchasingOrder\InDatabasePurchasingOrderRepository;
use App\Domain\PurchasingTransport\PurchasingTransportRepository;
use App\Infrastructure\Persistence\PurchasingTransport\InDatabasePurchasingTransportRepository;
use App\Domain\PurchasingFactoryReceipt\PurchasingFactoryReceiptRepository;
use App\Infrastructure\Persistence\PurchasingFactoryReceipt\InDatabasePurchasingFactoryReceiptRepository;
use App\Domain\Vendor\VendorRepository;
use App\Infrastructure\Persistence\Vendor\InDatabaseVendorRepository;
use App\Domain\VendorLand\VendorLandRepository;
use App\Infrastructure\Persistence\VendorLand\InDatabaseVendorLandRepository;
use App\Domain\ProductTank\ProductTankRepository;
use App\Infrastructure\Persistence\ProductTank\InDatabaseProductTankRepository;
use App\Domain\TransportationRoute\TransportationRouteRepository;
use App\Infrastructure\Persistence\TransportationRoute\InDatabaseTransportationRouteRepository;
use App\Domain\ProductType\ProductTypeRepository;
use App\Infrastructure\Persistence\ProductType\InDatabaseProductTypeRepository;
use App\Domain\Price\PriceRepository;
use App\Infrastructure\Persistence\Price\InDatabasePriceRepository;
use App\Domain\ProductionOrder\ProductionOrderRepository;
use App\Infrastructure\Persistence\ProductionOrder\InDatabaseProductionOrderRepository;
use App\Domain\ProductionChannel\ProductionChannelRepository;
use App\Infrastructure\Persistence\ProductionChannel\InDatabaseProductionChannelRepository;
use App\Domain\ProductionCuttingMachine\ProductionCuttingMachineRepository;
use App\Infrastructure\Persistence\ProductionCuttingMachine\InDatabaseProductionCuttingMachineRepository;
use App\Domain\ProductionRoller\ProductionRollerRepository;
use App\Infrastructure\Persistence\ProductionRoller\InDatabaseProductionRollerRepository;
use App\Domain\ProductionGongCart\ProductionGongCartRepository;
use App\Infrastructure\Persistence\ProductionGongCart\InDatabaseProductionGongCartRepository;
use App\Domain\ProductionOven\ProductionOvenRepository;
use App\Infrastructure\Persistence\ProductionOven\InDatabaseProductionOvenRepository;
use App\Domain\ProductionPressing\ProductionPressingRepository;
use App\Infrastructure\Persistence\ProductionPressing\InDatabaseProductionPressingRepository;
use App\Domain\ProductionSettlingTank\ProductionSettlingTankRepository;
use App\Infrastructure\Persistence\ProductionSettlingTank\InDatabaseProductionSettlingTankRepository;
use App\Domain\ProductionPallet\ProductionPalletRepository;
use App\Infrastructure\Persistence\ProductionPallet\InDatabaseProductionPalletRepository;
use App\Domain\Pallet\PalletRepository;
use App\Infrastructure\Persistence\Pallet\InDatabasePalletRepository;
use App\Domain\RawMaterialRelease\RawMaterialReleaseRepository;
use App\Infrastructure\Persistence\RawMaterialRelease\InDatabaseRawMaterialReleaseRepository;
use App\Domain\FinishedGoodsReceipt\FinishedGoodsReceiptRepository;
use App\Infrastructure\Persistence\FinishedGoodsReceipt\InDatabaseFinishedGoodsReceiptRepository;
use App\Domain\RubberBlock\RubberBlockRepository;
use App\Infrastructure\Persistence\RubberBlock\InDatabaseRubberBlockRepository;
use App\Domain\ProductLot\ProductLotRepository;
use App\Infrastructure\Persistence\ProductLot\InDatabaseProductLotRepository;
use App\Domain\Company\CompanyRepository;
use App\Infrastructure\Persistence\Company\InDatabaseCompanyRepository;
use App\Domain\CompanyMember\CompanyMemberRepository;
use App\Infrastructure\Persistence\CompanyMember\InDatabaseCompanyMemberRepository;
use App\Domain\CompanyGroup\CompanyGroupRepository;
use App\Infrastructure\Persistence\CompanyGroup\InDatabaseCompanyGroupRepository;
use App\Domain\Sales\Customer\SalesCustomerRepository;
use App\Infrastructure\Persistence\Sales\Customer\InDatabaseSalesCustomerRepository;
use App\Domain\Sales\Contract\SalesContractRepository;
use App\Infrastructure\Persistence\Sales\Contract\InDatabaseSalesContractRepository;
use App\Domain\Sales\Order\SalesOrderRepository;
use App\Infrastructure\Persistence\Sales\Order\InDatabaseSalesOrderRepository;
use App\Domain\Sales\Issue\SalesIssueRepository;
use App\Infrastructure\Persistence\Sales\Issue\InDatabaseSalesIssueRepository;
use App\Domain\ExternalMaterial\ExternalMaterialRepository;
use App\Infrastructure\Persistence\ExternalMaterial\InDatabaseExternalMaterialRepository;
use App\Domain\CustomField\CustomFieldRepository;
use App\Infrastructure\Persistence\CustomField\InDatabaseCustomFieldRepository;

use DI\ContainerBuilder;

return function (ContainerBuilder $containerBuilder) {
    // Here we map our UserRepository interface to its in memory implementation
    $containerBuilder->addDefinitions([
        UserRepository::class => \DI\autowire(InDatabaseUserRepository::class),
        PageRepository::class => \DI\autowire(InDatabasePageRepository::class),
        FileRepository::class => \DI\autowire(InDatabaseFileRepository::class),
        LandRepository::class => \DI\autowire(InDatabaseLandRepository::class),
        PlantRepository::class => \DI\autowire(InDatabasePlantRepository::class),
        HarvestRepository::class => \DI\autowire(InDatabaseHarvestRepository::class),
        ProductionRepository::class => \DI\autowire(InDatabaseProductionRepository::class),
        MailRepository::class => \DI\autowire(InDatabaseMailRepository::class),
        SmsRepository::class => \DI\autowire(InDatabaseSmsRepository::class),
        VehicleRepository::class => \DI\autowire(InDatabaseVehicleRepository::class),
        VehicleTankRepository::class => \DI\autowire(InDatabaseVehicleTankRepository::class),
        //DriverRepository::class => \DI\autowire(InDatabaseDriverRepository::class),
        ConnectionRepository::class => \DI\autowire(InDatabaseConnectionRepository::class),
        NotificationRepository::class => \DI\autowire(InDatabaseNotificationRepository::class),
        TransactionTicketRepository::class => \DI\autowire(InDatabaseTransactionTicketRepository::class),
        FactoryRepository::class => \DI\autowire(InDatabaseFactoryRepository::class),
        WarehouseRepository::class => \DI\autowire(InDatabaseWarehouseRepository::class),
        GradeRepository::class => \DI\autowire(InDatabaseGradeRepository::class),
        RawMaterialTankRepository::class => \DI\autowire(InDatabaseRawMaterialTankRepository::class),
        PurchasingSubTankRepository::class => \DI\autowire(InDatabasePurchasingSubTankRepository::class),
        PurchasingSubTankIntakeRepository::class => \DI\autowire(InDatabasePurchasingSubTankIntakeRepository::class),
        PurchasingOrderRepository::class => \DI\autowire(InDatabasePurchasingOrderRepository::class),
        PurchasingTransportRepository::class => \DI\autowire(InDatabasePurchasingTransportRepository::class),
        PurchasingFactoryReceiptRepository::class => \DI\autowire(InDatabasePurchasingFactoryReceiptRepository::class),
        VendorRepository::class => \DI\autowire(InDatabaseVendorRepository::class),
        VendorLandRepository::class => \DI\autowire(InDatabaseVendorLandRepository::class),
        ProductTankRepository::class => \DI\autowire(InDatabaseProductTankRepository::class),
        TransportationRouteRepository::class => \DI\autowire(InDatabaseTransportationRouteRepository::class),
        ProductTypeRepository::class => \DI\autowire(InDatabaseProductTypeRepository::class),
        PriceRepository::class => \DI\autowire(InDatabasePriceRepository::class),
        ProductionOrderRepository::class => \DI\autowire(InDatabaseProductionOrderRepository::class),
        ProductionChannelRepository::class => \DI\autowire(InDatabaseProductionChannelRepository::class),
        ProductionCuttingMachineRepository::class => \DI\autowire(InDatabaseProductionCuttingMachineRepository::class),
        ProductionRollerRepository::class => \DI\autowire(InDatabaseProductionRollerRepository::class),
        ProductionGongCartRepository::class => \DI\autowire(InDatabaseProductionGongCartRepository::class),
        ProductionOvenRepository::class => \DI\autowire(InDatabaseProductionOvenRepository::class),
        ProductionPressingRepository::class => \DI\autowire(InDatabaseProductionPressingRepository::class),
        ProductionSettlingTankRepository::class => \DI\autowire(InDatabaseProductionSettlingTankRepository::class),
        ProductionPalletRepository::class => \DI\autowire(InDatabaseProductionPalletRepository::class),
        PalletRepository::class => \DI\autowire(InDatabasePalletRepository::class),
        RawMaterialReleaseRepository::class => \DI\autowire(InDatabaseRawMaterialReleaseRepository::class),
        FinishedGoodsReceiptRepository::class => \DI\autowire(InDatabaseFinishedGoodsReceiptRepository::class),
        RubberBlockRepository::class => \DI\autowire(InDatabaseRubberBlockRepository::class),
        ProductLotRepository::class => \DI\autowire(InDatabaseProductLotRepository::class),
        CompanyRepository::class => \DI\autowire(InDatabaseCompanyRepository::class),
        CompanyGroupRepository::class => \DI\autowire(InDatabaseCompanyGroupRepository::class),
        CompanyMemberRepository::class => \DI\autowire(InDatabaseCompanyMemberRepository::class),
        SalesCustomerRepository::class => \DI\autowire(InDatabaseSalesCustomerRepository::class),
        SalesContractRepository::class => \DI\autowire(InDatabaseSalesContractRepository::class),
        SalesOrderRepository::class => \DI\autowire(InDatabaseSalesOrderRepository::class),
        SalesIssueRepository::class => \DI\autowire(InDatabaseSalesIssueRepository::class),
        ExternalMaterialRepository::class => \DI\autowire(InDatabaseExternalMaterialRepository::class),
        CustomFieldRepository::class => \DI\autowire(InDatabaseCustomFieldRepository::class),
    ]);
};
