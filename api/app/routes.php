<?php

declare(strict_types=1);

use App\Application\Actions\Page;
use App\Application\Actions\File;
use App\Application\Actions\Authentication;
use App\Application\Actions\General;
use App\Application\Settings\SettingsInterface;

// Users
use App\Application\Actions\User;
// Land
use App\Application\Actions\Land;
// Plant
use App\Application\Actions\Plant;
// Harvest
use App\Application\Actions\Harvest;
// Production
use App\Application\Actions\Production;
// Mail
use App\Application\Actions\Mail;
// SMS
use App\Application\Actions\Sms;
// Vehicle
use App\Application\Actions\Vehicle;
// Vehicle Tank
use App\Application\Actions\VehicleTank;
// Driver
// Connection
use App\Application\Actions\Connection;
// Transaction Ticket
use App\Application\Actions\TransactionTicket;
// Notification
use App\Application\Actions\Notification;
// Factory
use App\Application\Actions\Factory;
// Warehouse
use App\Application\Actions\Warehouse;
// Grade
use App\Application\Actions\Grade;
// Raw Material Tank
use App\Application\Actions\RawMaterialTank;
// Purchasing Sub Tank
use App\Application\Actions\PurchasingSubTank;
use App\Application\Actions\PurchasingSubTankIntake;
use App\Application\Actions\PurchasingTransport;
use App\Application\Actions\PurchasingFactoryReceipt\CancelPurchasingFactoryReceiptAction;
use App\Application\Actions\PurchasingFactoryReceipt\CreatePurchasingFactoryReceiptAction;
use App\Application\Actions\PurchasingFactoryReceipt\ListPurchasingFactoryReceiptAction;
use App\Application\Actions\PurchasingFactoryReceipt\PostPurchasingFactoryReceiptAction;
use App\Application\Actions\PurchasingFactoryReceipt\ViewPurchasingFactoryReceiptAction;
// Product Tank
use App\Application\Actions\ProductTank;
// Transportation Route
use App\Application\Actions\TransportationRoute;
// Product Type
use App\Application\Actions\ProductType;
// Price
use App\Application\Actions\Price;
// Production Order
use App\Application\Actions\ProductionOrder;
// Production Channel
use App\Application\Actions\ProductionChannel;
// Production Cutting Machine
use App\Application\Actions\ProductionCuttingMachine;
// Production Roller
use App\Application\Actions\ProductionRoller;
// Production Gong Cart
use App\Application\Actions\ProductionGongCart;
// Production Oven
use App\Application\Actions\ProductionOven;
// Production Pressing
use App\Application\Actions\ProductionPressing;
// Production Settling Tank
use App\Application\Actions\ProductionSettlingTank;
// Production Pallet
use App\Application\Actions\ProductionPallet;
// Pallet
use App\Application\Actions\Pallet;
// Raw Material Release
use App\Application\Actions\RawMaterialRelease;
// Finished Goods Receipt
use App\Application\Actions\FinishedGoodsReceipt;
// Rubber Block
use App\Application\Actions\RubberBlock;
// Product Lot
use App\Application\Actions\ProductLot;
// Company
use App\Application\Actions\Company;
// Sales
use App\Application\Actions\Sales;
// External Material
use App\Application\Actions\ExternalMaterial;
// Custom Field
use App\Application\Actions\CustomField;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use Slim\Exception\HttpNotFoundException;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('Hello world!');
        return $response;
    });

    // Authentication
    $app->group('/v1/auth', function (Group $group) {
        $group->get('/info/', Authentication\UserInfoAction::class);
        $group->get('/qr/', Authentication\UserQrCodeAction::class);
        $group->post('/login/', Authentication\LoginAction::class);
        $group->post('/login/google/', Authentication\LoginGoogleAction::class);
        $group->post('/login/microsoft/', Authentication\LoginMicrosoftAction::class);
        $group->post('/register/', Authentication\RegisterAction::class);
        $group->post('/request-otp/', Authentication\RequestOtpAction::class);
        $group->post('/verify-otp/', Authentication\VerifyOtpAction::class);
    });
    // User
    $app->group('/v1/users', function (Group $group) {
        $group->get('/{code}', User\ViewUserAction::class);
        $group->get('/', User\ListUsersAction::class);
        $group->post('/', User\CreateUserAction::class);
        $group->put('/profile', User\UpdateProfileAction::class);
        $group->put('/{code}', User\UpdateUserAction::class);
        $group->delete('/{code}', User\DeleteUserAction::class);
        $group->put('/approve/{code}', User\ApproveUserAction::class);
        $group->put('/deactivate/{code}', User\DeactivateUserAction::class);
        $group->put('/permission/{code}', User\UpdateUserPermissionAction::class);
        $group->get('/permission/', User\ListPermissionAction::class);
        $group->post('/upgrade/', User\UpgradeAccountAction::class);
    });
    // Worker User - Tá Điền
    /*
    $app->group('/v1/worker', function (Group $group) {
        $group->get('/', User\ListWorkerUsersAction::class);
        $group->post('/', User\CreateWorkerUserAction::class);
        $group->get('/{code}', User\ViewWorkerUserAction::class);
        $group->put('/{code}', User\UpdateWorkerUserAction::class);
        $group->delete('/{code}', User\DeleteWorkerUserAction::class);
    });
    */

    // Page
    $app->group('/v1/pages', function (Group $group) {
        $group->get('/{code}', Page\ListPageAction::class);
        $group->put('/{code}', Page\UpdatePageAction::class);
        $group->delete('/', Page\DeletePageAction::class);
        $group->post('/', Page\CreatePageAction::class);
    });

    // File
    $app->group('/v1/file', function (Group $group) {
        // $group->get('/search/{code}', File\SearchFileAction::class);
        // $group->get('/{code}', File\ViewFileAction::class);
        // $group->get('/', File\ListFileAction::class);
        // $group->delete('/', File\DeleteFileAction::class);
        $group->post('/', File\UploadFileAction::class);
    });

    // Cron
    $app->group('/v1/cron', function (Group $group) {});

    // General
    $app->group('/v1/general', function (Group $group) {
        $group->get('/clear-cache', General\ClearCacheMemcacheAction::class);
        $group->post('/profile', User\UpdateUserAction::class);
        $group->get('/settings', General\SettingsAction::class);
        $group->post('/settings', General\UpsertSettingsAction::class);
        $group->get('/data', General\GetDataAction::class);
        $group->get('/province', General\GetDataProvinceAction::class);
        $group->get('/zone', General\GetDataZoneAction::class);
        $group->get('/generate-qr', General\GenerateQrCodeAction::class);
        $group->get('/company', General\GetDataCompanyAction::class);
        //$group->get('/google-document-extractor', General\GoogleDocumentExtractorAction::class);
        //$group->get('/aws-textract', General\AWSTextractAction::class);
    });

    // Land
    $app->group('/v1/land', function (Group $group) {
        $group->get('/', Land\ListLandAction::class);
        $group->post('/', Land\CreateLandAction::class);
        $group->get('/{code}', Land\ViewLandAction::class);
        $group->put('/{code}', Land\UpdateLandAction::class);
        $group->delete('/{code}', Land\DeleteLandAction::class);
        $group->put('/approve/{code}', Land\ApproveLandAction::class);
        $group->post('/share/', Land\ShareLandAction::class);
        $group->get('/share/', Land\ListSharedLandAction::class);
        $group->get('/share/all/', Land\ListLandSharedAllAction::class);
        $group->get('/{code}/shares/', Land\ListUserSharedLandAction::class);
        $group->get('/my/shared-lands/', Land\MySharedLandAction::class);
        $group->delete('/revoke/share', Land\RevokeShareLandAction::class);
        $group->get('/by-seller/', Land\SellerLandListAction::class);
        $group->get('/by-transaction-ticket/', Land\ListLandByTransactionTicketAction::class);
        // Land Support
        $group->post('/support/', Land\Support\CreateLandAction::class);
        $group->put('/support/{code}', Land\Support\UpdateLandAction::class);
        $group->delete('/support/{code}', Land\Support\DeleteLandAction::class);
        $group->get('/support/{code}', Land\Support\ViewLandAction::class);
        $group->get('/support/', Land\Support\ListLandAction::class);
    });

    // Plant
    $app->group('/v1/plant', function (Group $group) {
        $group->get('/', Plant\ListPlantAction::class);
        $group->post('/', Plant\CreatePlantAction::class);
        $group->get('/{code}', Plant\ViewPlantAction::class);
        $group->put('/{code}', Plant\UpdatePlantAction::class);
        $group->delete('/{code}', Plant\DeletePlantAction::class);
        $group->get('/crop-type/', Plant\ListCropTypeAction::class);
    });

    // Harvest
    $app->group('/v1/harvest', function (Group $group) {
        $group->get('/plan/', Harvest\ListHarvestPlanAction::class);
        $group->post('/plan/', Harvest\CreateHarvestPlanAction::class);
        $group->get('/plan/{code}', Harvest\ViewHarvestPlanAction::class);
        $group->put('/plan/{code}', Harvest\UpdateHarvestPlanAction::class);
        $group->delete('/plan/{code}', Harvest\DeleteHarvestPlanAction::class);
        $group->get('/schedule/', Harvest\ListHarvestScheduleAction::class);
        $group->post('/schedule/', Harvest\CreateHarvestScheduleAction::class);
        $group->get('/schedule/{code}', Harvest\ViewHarvestScheduleAction::class);
        $group->put('/schedule/', Harvest\CreateOrUpdateHarvestScheduleAction::class);
        $group->put('/result/', Harvest\CreateOrUpdateHarvestResultAction::class);
        $group->put('/result/schedule/', Harvest\UpdateHarvestScheduleDatesAction::class);
    });

    // Production
    $app->group('/v1/production', function (Group $group) {
        $group->get('/dds-export', Production\DDSExportAction::class);
    });

    // Mail
    $app->group('/v1/mail', function (Group $group) {
        $group->post('/add/', Mail\AddMailQueueAction::class);
        $group->get('/send/', Mail\SendMailAction::class);
    });

    // SMS
    $app->group('/v1/sms', function (Group $group) {
        $group->post('/add/', Sms\AddSmsQueueAction::class);
        $group->get('/send/', Sms\SendSmsAction::class);
    });

    // Notification
    $app->group('/v1/notification', function (Group $group) {
        $group->get('/', Notification\ListNotificationAction::class);
        $group->get('/{code}', Notification\ViewNotificationAction::class);
        $group->get('/related-type/', Notification\ListRelatedTypeAction::class);
        $group->put('/read/', Notification\MarkAsReadNotificationAction::class);
    });

    // Vehicle
    $app->group('/v1/vehicle', function (Group $group) {
        $group->get('/', Vehicle\ListVehicleAction::class);
        $group->get('/brand/', Vehicle\ListVehicleBrandAction::class);
        $group->post('/', Vehicle\CreateVehicleAction::class);
        $group->get('/{code}', Vehicle\ViewVehicleAction::class);
        $group->put('/{code}', Vehicle\UpdateVehicleAction::class);
        $group->delete('/{code}', Vehicle\DeleteVehicleAction::class);
    });

    // Vehicle Tank
    $app->group('/v1/vehicle-tank', function (Group $group) {
        $group->get('/', VehicleTank\ListVehicleTankAction::class);
        $group->post('/', VehicleTank\CreateVehicleTankAction::class);
        $group->get('/{code}', VehicleTank\ViewVehicleTankAction::class);
        $group->put('/{code}', VehicleTank\UpdateVehicleTankAction::class);
        $group->delete('/{code}', VehicleTank\DeleteVehicleTankAction::class);
    });

    // Driver
    /*
    $app->group('/v1/driver', function (Group $group) {
        $group->get('/', Driver\ListDriverAction::class);
        $group->post('/', Driver\CreateDriverAction::class);
        $group->get('/{code}', Driver\ViewDriverAction::class);
        $group->put('/{code}', Driver\UpdateDriverAction::class);
        $group->delete('/{code}', Driver\DeleteDriverAction::class);
    });
    */

    // Connection
    $app->group('/v1/connection', function (Group $group) {
        $group->get('/search/', Connection\ConnectionSearchAction::class);
        $group->post('/request/', Connection\ConnectionRequestAction::class);
        $group->post('/request/cancel/', Connection\ConnectionRequestCancelAction::class);
        $group->post('/respond/', Connection\ConnectionRequestRespondAction::class);
        $group->post('/manage/', Connection\ManageConnectionAction::class);
        $group->get('/', Connection\ListConnectionAction::class);
    });

    // Transaction Ticket
    $app->group('/v1/transaction-ticket', function (Group $group) {
        $group->get('/', TransactionTicket\ListTransactionTicketAction::class);
        $group->post('/', TransactionTicket\CreateTransactionTicketAction::class);
        $group->get('/contract/{contract_code}', TransactionTicket\ViewTransactionTicketByContractCodeAction::class);
        $group->get('/{code}', TransactionTicket\ViewTransactionTicketAction::class);
        $group->put('/{code}', TransactionTicket\UpdateTransactionTicketAction::class);
        $group->post('/cancel/', TransactionTicket\TransactionTicketCancelAction::class);
        $group->post('/confirm/', TransactionTicket\TransactionTicketConfirmAction::class);
        $group->get('/{code}/purchase-ticket/', TransactionTicket\ListPurchaseTicketsBySaleTicketAction::class);
        $group->get('/purchase-ticket/unrouted/', TransactionTicket\ListPurchaseTicketsUnroutedAction::class);
    });

    // Factory
    $app->group('/v1/factory', function (Group $group) {
        $group->get('/', Factory\ListFactoryAction::class);
        $group->post('/', Factory\CreateFactoryAction::class);
        $group->get('/{code}', Factory\ViewFactoryAction::class);
        $group->put('/{code}', Factory\UpdateFactoryAction::class);
        $group->delete('/{code}', Factory\DeleteFactoryAction::class);
    });

    // Warehouse
    $app->group('/v1/warehouse', function (Group $group) {
        $group->get('/', Warehouse\ListWarehouseAction::class);
        $group->post('/', Warehouse\CreateWarehouseAction::class);
        $group->get('/{code}', Warehouse\ViewWarehouseAction::class);
        $group->put('/{code}', Warehouse\UpdateWarehouseAction::class);
        $group->delete('/{code}', Warehouse\DeleteWarehouseAction::class);
    });

    // Grade
    $app->group('/v1/grade', function (Group $group) {
        $group->get('/', Grade\ListGradeAction::class);
        $group->get('/generate-code/', Grade\GenerateCodeGradeAction::class);
        $group->post('/', Grade\CreateGradeAction::class);
        $group->get('/{code}', Grade\ViewGradeAction::class);
        $group->put('/{code}', Grade\UpdateGradeAction::class);
        $group->delete('/{code}', Grade\DeleteGradeAction::class);
        $group->get('/{code}/prices/current/', Grade\ViewCurrentGradePriceAction::class);
        $group->get('/{code}/prices/history/', Grade\HistoryGradePriceAction::class);
        $group->post('/{code}/prices/', Grade\CreateGradePriceAction::class);
    });

    // Raw Material Tank
    $app->group('/v1/raw-material-tank', function (Group $group) {
        $group->get('/', RawMaterialTank\ListRawMaterialTankAction::class);
        $group->post('/', RawMaterialTank\CreateRawMaterialTankAction::class);
        $group->get('/{code}', RawMaterialTank\ViewRawMaterialTankAction::class);
        $group->get('/{code}/history/', RawMaterialTank\HistoryRawMaterialTankAction::class);
        $group->put('/{code}', RawMaterialTank\UpdateRawMaterialTankAction::class);
        $group->delete('/{code}', RawMaterialTank\DeleteRawMaterialTankAction::class);
    });

    // Purchasing Sub Tank
    $app->group('/v1/purchasing-sub-tank', function (Group $group) {
        $group->get('/', PurchasingSubTank\ListPurchasingSubTankAction::class);
        $group->post('/', PurchasingSubTank\CreatePurchasingSubTankAction::class);
        $group->get('/{code}', PurchasingSubTank\ViewPurchasingSubTankAction::class);
        $group->get('/{code}/history/', PurchasingSubTank\HistoryPurchasingSubTankAction::class);
        $group->put('/{code}', PurchasingSubTank\UpdatePurchasingSubTankAction::class);
        $group->delete('/{code}', PurchasingSubTank\DeletePurchasingSubTankAction::class);
        $group->post('/{tankCode}/intakes/', PurchasingSubTankIntake\CreatePurchasingSubTankIntakeAction::class);
        $group->post('/{tankCode}/stock-receipts/', PurchasingSubTank\CreatePurchasingSubTankStockReceiptAction::class);
        $group->post('/{tankCode}/adjustments/', PurchasingSubTank\CreatePurchasingSubTankAdjustmentAction::class);
        $group->get('/{tankCode}/intakes/', PurchasingSubTankIntake\ListPurchasingSubTankIntakeAction::class);
    });
    $app->group('/v1/purchasing-sub-tank-intakes', function (Group $group) {
        $group->get('/', PurchasingSubTankIntake\ListPurchasingSubTankIntakeAction::class);
        $group->get('/{intakeId}', PurchasingSubTankIntake\ViewPurchasingSubTankIntakeAction::class);
    });

    // Purchasing Transport
    $app->group('/v1/purchasing-transports', function (Group $group) {
        $group->get('/', PurchasingTransport\ListPurchasingTransportAction::class);
        $group->post('/', PurchasingTransport\CreatePurchasingTransportAction::class);
        $group->post('/{transportCode}/lines/', PurchasingTransport\AddPurchasingTransportLineAction::class);
        $group->put('/{transportCode}/lines/{lineId}', PurchasingTransport\UpdatePurchasingTransportLineAction::class);
        $group->delete('/{transportCode}/lines/{lineId}', PurchasingTransport\DeletePurchasingTransportLineAction::class);
        $group->post('/{transportCode}/dispatch/', PurchasingTransport\DispatchPurchasingTransportAction::class);
        $group->post('/{transportCode}/arrive/', PurchasingTransport\ArrivePurchasingTransportAction::class);
        $group->post('/{transportCode}/cancel/', PurchasingTransport\CancelPurchasingTransportAction::class);
        $group->get('/{transportCode}', PurchasingTransport\ViewPurchasingTransportAction::class);
        $group->put('/{transportCode}', PurchasingTransport\UpdatePurchasingTransportAction::class);
    });

    // Purchasing Factory Receipt
    $app->group('/v1/purchasing-factory-receipts', function (Group $group) {
        $group->get('/', ListPurchasingFactoryReceiptAction::class);
        $group->post('/transports/{transportCode}', CreatePurchasingFactoryReceiptAction::class);
        $group->post('/{receiptCode}/post', PostPurchasingFactoryReceiptAction::class);
        $group->post('/{receiptCode}/cancel', CancelPurchasingFactoryReceiptAction::class);
        $group->get('/{receiptCode}', ViewPurchasingFactoryReceiptAction::class);
    });

    // Vendor
    $app->group('/v1/vendor', function (Group $group) {
        $group->get('/', \App\Application\Actions\Vendor\ListVendorAction::class);
        $group->post('/', \App\Application\Actions\Vendor\CreateVendorAction::class);
        $group->get('/{code}', \App\Application\Actions\Vendor\ViewVendorAction::class);
        $group->put('/{code}', \App\Application\Actions\Vendor\UpdateVendorAction::class);
        $group->delete('/{code}', \App\Application\Actions\Vendor\DeleteVendorAction::class);
        $group->get('/{vendor_id}/lands/', \App\Application\Actions\VendorLand\ListVendorLandAction::class);
        $group->post('/{vendor_code}/lands/', \App\Application\Actions\VendorLand\CreateVendorLandAction::class);
        $group->get('/{vendor_code}/lands/{vendor_land_id}/', \App\Application\Actions\VendorLand\ViewVendorLandAction::class);
        $group->put('/{vendor_code}/lands/{vendor_land_id}/', \App\Application\Actions\VendorLand\UpdateVendorLandAction::class);
        $group->delete('/{vendor_code}/lands/{vendor_land_id}/', \App\Application\Actions\VendorLand\DeleteVendorLandAction::class);
    });

    // Purchasing Order
    $app->group('/v1/purchasing/orders', function (Group $group) {
        $group->get('/', \App\Application\Actions\PurchasingOrder\Order\ListPurchasingOrderAction::class);
        $group->post('/', \App\Application\Actions\PurchasingOrder\Order\CreatePurchasingOrderAction::class);
        $group->get('/{code}', \App\Application\Actions\PurchasingOrder\Order\ViewPurchasingOrderAction::class);
        $group->get('/{code}/status-history', \App\Application\Actions\PurchasingOrder\Order\StatusHistoryPurchasingOrderAction::class);
        $group->get('/{code}/reconciliation', \App\Application\Actions\PurchasingOrder\Order\ReconciliationPurchasingOrderAction::class);
        $group->put('/{code}', \App\Application\Actions\PurchasingOrder\Order\UpdatePurchasingOrderAction::class);
        $group->delete('/{code}', \App\Application\Actions\PurchasingOrder\Order\DeletePurchasingOrderAction::class);
        // Items
        $group->get('/{code}/items', \App\Application\Actions\PurchasingOrder\Item\ListPurchasingOrderItemAction::class);
        $group->post('/{code}/items', \App\Application\Actions\PurchasingOrder\Item\AddPurchasingOrderItemAction::class);
        $group->put('/{code}/items/{item_id}', \App\Application\Actions\PurchasingOrder\Item\UpdatePurchasingOrderItemAction::class);
        $group->delete('/{code}/items/{item_id}', \App\Application\Actions\PurchasingOrder\Item\DeletePurchasingOrderItemAction::class);
        // Lands
        $group->get('/{code}/lands', \App\Application\Actions\PurchasingOrder\OrderLand\ListPurchasingOrderLandAction::class);
        $group->post('/{code}/lands', \App\Application\Actions\PurchasingOrder\OrderLand\CreatePurchasingOrderLandAction::class);
        $group->put('/{code}/lands/{id}', \App\Application\Actions\PurchasingOrder\OrderLand\UpdatePurchasingOrderLandAction::class);
        $group->delete('/{code}/lands/{id}', \App\Application\Actions\PurchasingOrder\OrderLand\DeletePurchasingOrderLandAction::class);
        // Lifecycle
        $group->post('/{code}/send', \App\Application\Actions\PurchasingOrder\Lifecycle\SendPurchasingOrderAction::class);
        $group->post('/{code}/cancel', \App\Application\Actions\PurchasingOrder\Lifecycle\CancelDraftPurchasingOrderAction::class);
        $group->post('/{code}/seller-confirm', \App\Application\Actions\PurchasingOrder\Lifecycle\SellerConfirmPurchasingOrderAction::class);
        $group->post('/{code}/buyer-reconfirm', \App\Application\Actions\PurchasingOrder\Lifecycle\BuyerReconfirmPurchasingOrderAction::class);
        // Seller Sub Tanks
        $group->get('/{code}/seller-sub-tanks', \App\Application\Actions\PurchasingOrder\SellerSubTank\ListSellerSubTanksPurchasingOrderAction::class);
        $group->post('/{code}/seller-sub-tanks', \App\Application\Actions\PurchasingOrder\SellerSubTank\CreateSellerSubTankPurchasingOrderAction::class);
        $group->put('/{code}/seller-sub-tanks/{id}', \App\Application\Actions\PurchasingOrder\SellerSubTank\UpdateSellerSubTankPurchasingOrderAction::class);
        $group->delete('/{code}/seller-sub-tanks/{id}', \App\Application\Actions\PurchasingOrder\SellerSubTank\DeleteSellerSubTankPurchasingOrderAction::class);
        // Buyer Sub Tanks
        $group->get('/{code}/buyer-sub-tanks', \App\Application\Actions\PurchasingOrder\BuyerSubTank\ListBuyerSubTanksPurchasingOrderAction::class);
        $group->post('/{code}/buyer-sub-tanks', \App\Application\Actions\PurchasingOrder\BuyerSubTank\CreateBuyerSubTankPurchasingOrderAction::class);
        $group->put('/{code}/buyer-sub-tanks/{id}', \App\Application\Actions\PurchasingOrder\BuyerSubTank\UpdateBuyerSubTankPurchasingOrderAction::class);
        $group->delete('/{code}/buyer-sub-tanks/{id}', \App\Application\Actions\PurchasingOrder\BuyerSubTank\DeleteBuyerSubTankPurchasingOrderAction::class);
    });

    // Product Tank
    $app->group('/v1/product-tank', function (Group $group) {
        $group->get('/', ProductTank\ListProductTankAction::class);
        $group->post('/', ProductTank\CreateProductTankAction::class);
        $group->get('/{code}', ProductTank\ViewProductTankAction::class);
        $group->put('/{code}', ProductTank\UpdateProductTankAction::class);
        $group->delete('/{code}', ProductTank\DeleteProductTankAction::class);
        $group->get('/{code}/history/', ProductTank\HistoryProductTankAction::class);
    });

    // Transportation Route
    $app->group('/v1/transportation-route', function (Group $group) {
        $group->get('/', TransportationRoute\ListTransportationRouteAction::class);
        $group->post('/', TransportationRoute\CreateTransportationRouteAction::class);
        $group->get('/{code}', TransportationRoute\ViewTransportationRouteAction::class);
        $group->put('/{code}', TransportationRoute\UpdateTransportationRouteAction::class);
        $group->delete('/{code}', TransportationRoute\DeleteTransportationRouteAction::class);
        $group->put('/{code}/cancel/', TransportationRoute\CancelTransportationRouteAction::class);
        $group->put('/{code}/arrive/', TransportationRoute\ArriveTransportationRouteAction::class);
        $group->post('/{code}/unload/', TransportationRoute\UnloadTransportationRouteAction::class);
    });

    // Product Type
    $app->group('/v1/product-type', function (Group $group) {
        $group->get('/', ProductType\ListProductTypeAction::class);
        $group->post('/', ProductType\CreateProductTypeAction::class);
        $group->put('/{code}', ProductType\UpdateProductTypeAction::class);
        $group->delete('/{code}', ProductType\DeleteProductTypeAction::class);
    });

    // Price
    $app->group('/v1/price', function (Group $group) {
        $group->get('/', Price\ListPriceAction::class);
        $group->post('/', Price\CreatePriceAction::class);
        $group->put('/{code}', Price\UpdatePriceAction::class);
        $group->delete('/{code}', Price\DeletePriceAction::class);
    });

    // Production Order
    $app->group('/v1/production-order', function (Group $group) {
        $group->get('/', ProductionOrder\ListProductionOrderAction::class);
        $group->get('/setup-change-requests/', ProductionOrder\ListSetupChangeRequestsProductionOrderAction::class);
        $group->get('/{code}', ProductionOrder\ViewProductionOrderAction::class);
        $group->post('/', ProductionOrder\CreateProductionOrderAction::class);
        $group->put('/{code}', ProductionOrder\UpdateProductionOrderAction::class);
        $group->delete('/{code}', ProductionOrder\DeleteProductionOrderAction::class);
        $group->get('/generate-code/', ProductionOrder\GenerateCodeProductionOrderAction::class);
        $group->post('/{code}/setup-raw-tank/', ProductionOrder\SetupRawTankProductionOrderAction::class);
        $group->post('/{code}/setup-settling-tank/', ProductionOrder\SetupSettlingTankProductionOrderAction::class);
        $group->post('/{code}/setup-channel/', ProductionOrder\SetupChannelProductionOrderAction::class);
        $group->post('/{code}/setup-cutting-machine/', ProductionOrder\SetupCuttingMachineProductionOrderAction::class);
        $group->post('/{code}/setup-roller-by-quality/', ProductionOrder\SetupRollerByQualityProductionOrderAction::class);
        $group->post('/{code}/setup-hanging/', ProductionOrder\SetupHangingProductionOrderAction::class);
        $group->post('/{code}/setup-drying/', ProductionOrder\SetupDryingProductionOrderAction::class);
        $group->post('/{code}/setup-pressing/', ProductionOrder\SetupPressingProductionOrderAction::class);
        $group->post('/{code}/setup-pallet/', ProductionOrder\SetupPalletProductionOrderAction::class);
        $group->post('/{code}/setup-change-request/', ProductionOrder\CreateSetupChangeRequestProductionOrderAction::class);
        $group->post('/{code}/setup-change-request/{change_request_id}/approve/', ProductionOrder\ApproveSetupChangeRequestProductionOrderAction::class);
        $group->post('/{code}/setup-change-request/{change_request_id}/reject/', ProductionOrder\RejectSetupChangeRequestProductionOrderAction::class);
        $group->get('/{code}/setup/', ProductionOrder\ViewSetupProductionOrderAction::class);
        $group->get('/{code}/execution/', ProductionOrder\ViewExecutionProductionOrderAction::class);
    });

    // Production Channel
    $app->group('/v1/production-channel', function (Group $group) {
        $group->get('/', ProductionChannel\ListProductionChannelAction::class);
        $group->get('/runs/', ProductionChannel\ListProductionChannelRunsAction::class);
        $group->get('/runs/{channel_run_id}/', ProductionChannel\ViewChannelRunAction::class);
        $group->get('/{code}', ProductionChannel\ViewProductionChannelAction::class);
        $group->post('/', ProductionChannel\CreateProductionChannelAction::class);
        $group->post('/pour-raw-material/', ProductionChannel\PourRawMaterialToChannelsAction::class);
        $group->post('/settling-tank-output/', ProductionChannel\RecordSettlingTankOutputAction::class);
        $group->post('/transfer-to-cutting-machine/', ProductionChannel\TransferChannelToCuttingMachineAction::class);
        $group->put('/{code}', ProductionChannel\UpdateProductionChannelAction::class);
        $group->delete('/{code}', ProductionChannel\DeleteProductionChannelAction::class);
        $group->get('/generate-code/', ProductionChannel\GenerateCodeProductionChannelAction::class);
    });

    // Production Cutting Machine
    $app->group('/v1/production-cutting-machine', function (Group $group) {
        $group->get('/', ProductionCuttingMachine\ListProductionCuttingMachineAction::class);
        $group->get('/runs/', ProductionCuttingMachine\ListProductionCuttingRunsAction::class);
        $group->get('/runs/{cutting_run_id}/', ProductionCuttingMachine\ViewCuttingRunAction::class);
        $group->get('/{code}', ProductionCuttingMachine\ViewProductionCuttingMachineAction::class);
        $group->post('/', ProductionCuttingMachine\CreateProductionCuttingMachineAction::class);
        $group->post('/cutting-quality-outputs/', ProductionCuttingMachine\UpdateCuttingRunQualityOutputsAction::class);
        $group->post('/transfer-to-roller/', ProductionCuttingMachine\TransferCuttingToRollerAction::class);
        $group->put('/{code}', ProductionCuttingMachine\UpdateProductionCuttingMachineAction::class);
        $group->delete('/{code}', ProductionCuttingMachine\DeleteProductionCuttingMachineAction::class);
        $group->get('/generate-code/', ProductionCuttingMachine\GenerateCodeProductionCuttingMachineAction::class);
    });

    // Production Roller
    $app->group('/v1/production-roller', function (Group $group) {
        $group->get('/', ProductionRoller\ListProductionRollerAction::class);
        $group->get('/runs/', ProductionRoller\ListRollingRunsAction::class);
        $group->get('/runs/{rolling_run_id}/', ProductionRoller\ViewRollingRunAction::class);
        $group->get('/{code}', ProductionRoller\ViewProductionRollerAction::class);
        $group->post('/', ProductionRoller\CreateProductionRollerAction::class);
        $group->post('/rolling-quality-details/', ProductionRoller\UpdateRollingRunQualityDetailsAction::class);
        $group->put('/{code}', ProductionRoller\UpdateProductionRollerAction::class);
        $group->delete('/{code}', ProductionRoller\DeleteProductionRollerAction::class);
        $group->get('/generate-code/', ProductionRoller\GenerateCodeProductionRollerAction::class);
    });

    // Production Gong Cart
    $app->group('/v1/production-gong-cart', function (Group $group) {
        $group->get('/', ProductionGongCart\ListProductionGongCartAction::class);
        $group->get('/runs/', ProductionGongCart\ListHangingRunsAction::class);
        $group->get('/runs/{hanging_run_id}/', ProductionGongCart\ViewHangingRunAction::class);
        $group->get('/{code}', ProductionGongCart\ViewProductionGongCartAction::class);
        $group->post('/', ProductionGongCart\CreateProductionGongCartAction::class);
        $group->post('/hang-rolling-sheets/', ProductionGongCart\HangRollingSheetsToGongPolesAction::class);
        $group->post('/hanging-quality-details/', ProductionGongCart\CompleteHangingRunQualityDetailsAction::class);
        $group->put('/{code}', ProductionGongCart\UpdateProductionGongCartAction::class);
        $group->delete('/{code}', ProductionGongCart\DeleteProductionGongCartAction::class);
        $group->get('/generate-code/', ProductionGongCart\GenerateCodeProductionGongCartAction::class);
    });

    // Production Oven
    $app->group('/v1/production-oven', function (Group $group) {
        $group->get('/', ProductionOven\ListProductionOvenAction::class);
        $group->get('/runs/', ProductionOven\ListDryingRunsAction::class);
        $group->get('/runs/{drying_run_id}/', ProductionOven\ViewDryingRunAction::class);
        $group->get('/{code}', ProductionOven\ViewProductionOvenAction::class);
        $group->post('/', ProductionOven\CreateProductionOvenAction::class);
        $group->post('/transfer-from-hanging/', ProductionOven\TransferHangingToDryingAction::class);
        $group->post('/drying-quality-details/', ProductionOven\UpdateDryingRunQualityDetailsAction::class);
        $group->put('/{code}', ProductionOven\UpdateProductionOvenAction::class);
        $group->delete('/{code}', ProductionOven\DeleteProductionOvenAction::class);
        $group->get('/generate-code/', ProductionOven\GenerateCodeProductionOvenAction::class);
    });

    // Production Pressing
    $app->group('/v1/production-pressing', function (Group $group) {
        $group->get('/runs/', ProductionPressing\ListPressingRunsAction::class);
        $group->get('/runs/{pressing_run_id}/', ProductionPressing\ViewPressingRunAction::class);
        $group->post('/transfer-from-drying/', ProductionPressing\TransferDryingToPressingAction::class);
        $group->post('/pressing-quality-details/', ProductionPressing\UpdatePressingRunQualityDetailsAction::class);
    });

    // Production Settling Tank
    $app->group('/v1/production-settling-tank', function (Group $group) {
        $group->get('/', ProductionSettlingTank\ListProductionSettlingTankAction::class);
        $group->get('/{code}', ProductionSettlingTank\ViewProductionSettlingTankAction::class);
        $group->post('/', ProductionSettlingTank\CreateProductionSettlingTankAction::class);
        $group->put('/{code}', ProductionSettlingTank\UpdateProductionSettlingTankAction::class);
        $group->delete('/{code}', ProductionSettlingTank\DeleteProductionSettlingTankAction::class);
    });

    // Production Pallet
    $app->group('/v1/production-pallet', function (Group $group) {
        $group->get('/runs/', ProductionPallet\ListPalletRunsAction::class);
        $group->get('/runs/{pallet_run_id}/', ProductionPallet\ViewPalletRunAction::class);
        $group->get('/pallets/', ProductionPallet\ListPalletsAction::class);
        $group->get('/pallets/{code}/', ProductionPallet\ViewPalletAction::class);
        $group->get('/bales/', ProductionPallet\ListBalesAction::class);
        $group->get('/bales/{bale_id}/', ProductionPallet\ViewBaleAction::class);
        $group->post('/transfer-from-pressing/', ProductionPallet\TransferPressingToPalletAction::class);
        $group->post('/pallets/', ProductionPallet\CreatePalletWithBalesAction::class);
        $group->put('/pallet-items/', ProductionPallet\UpdatePalletItemAction::class);
        $group->delete('/pallet-items/{pallet_item_id}/', ProductionPallet\DeletePalletItemAction::class);
        $group->post('/complete-run/', ProductionPallet\CompletePalletRunAction::class);
    });

    // Pallet
    $app->group('/v1/pallet', function (Group $group) {
        $group->get('/', Pallet\ListPalletAction::class);
        $group->get('/generate-code/', Pallet\GenerateCodePalletAction::class);
        $group->post('/', Pallet\CreatePalletAction::class);
        $group->get('/{code}', Pallet\ViewPalletAction::class);
        $group->put('/{code}', Pallet\UpdatePalletAction::class);
        $group->delete('/{code}', Pallet\DeletePalletAction::class);

        $group->get('/{code}/items/', Pallet\ListPalletItemsAction::class);
        $group->post('/{code}/items/', Pallet\AddPalletItemsAction::class);
        $group->delete('/{code}/items/{pallet_item_id}', Pallet\DeletePalletItemAction::class);

        $group->put('/{code}/pack/', Pallet\PackPalletAction::class);
        $group->put('/{code}/ship/', Pallet\ShipPalletAction::class);
        $group->put('/{code}/cancel/', Pallet\CancelPalletAction::class);
    });

    // Raw Material Release
    $app->group('/v1/raw-material-release', function (Group $group) {
        $group->get('/', RawMaterialRelease\ListRawMaterialReleaseAction::class);
        $group->get('/{code}', RawMaterialRelease\ViewRawMaterialReleaseAction::class);
        $group->post('/', RawMaterialRelease\CreateRawMaterialReleaseAction::class);
        // $group->put('/{code}', RawMaterialRelease\UpdateRawMaterialReleaseAction::class);
        $group->delete('/{code}', RawMaterialRelease\DeleteRawMaterialReleaseAction::class);
        $group->get('/generate-code/', RawMaterialRelease\GenerateCodeRawMaterialReleaseAction::class);
    });

    // Finished Goods Receipt
    $app->group('/v1/finished-goods-receipt', function (Group $group) {
        $group->get('/', FinishedGoodsReceipt\ListFinishedGoodsReceiptAction::class);
        $group->get('/{code}', FinishedGoodsReceipt\ViewFinishedGoodsReceiptAction::class);
        $group->post('/', FinishedGoodsReceipt\CreateFinishedGoodsReceiptAction::class);
        $group->put('/{code}', FinishedGoodsReceipt\UpdateFinishedGoodsReceiptAction::class);
        $group->delete('/{code}', FinishedGoodsReceipt\DeleteFinishedGoodsReceiptAction::class);
        $group->get('/generate-code/', FinishedGoodsReceipt\GenerateCodeFinishedGoodsReceiptAction::class);
        $group->get('/summary/', FinishedGoodsReceipt\SummaryFinishedGoodsReceiptAction::class);
    });

    // Rubber Block
    $app->group('/v1/rubber-blocks', function (Group $group) {
        $group->get('/', RubberBlock\ListRubberBlockAction::class);
        $group->get('/{code}', RubberBlock\ViewRubberBlockAction::class);
    });

    // Product Lot
    $app->group('/v1/product-lots', function (Group $group) {
        $group->get('/', ProductLot\ListProductLotAction::class);
        $group->get('/inventory/', ProductLot\ListInventoryProductLotAction::class);
        $group->post('/', ProductLot\CreateProductLotAction::class);

        // External product lots (must be before /{code} to avoid wildcard match)
        $group->post('/import', ProductLot\ImportProductLotAction::class);
        $group->post('/import/non-eudr', ProductLot\ImportNonEudrProductLotAction::class);
        $group->post('/external', ProductLot\CreateExternalProductLotAction::class);
        $group->put('/external/{code}', ProductLot\UpdateExternalProductLotAction::class);
        $group->delete('/external/{code}', ProductLot\DeleteExternalProductLotAction::class);
        $group->put('/external/{code}/confirm', ProductLot\ConfirmExternalProductLotAction::class);
        $group->put('/external/{code}/cancel', ProductLot\CancelExternalProductLotAction::class);

        $group->get('/{code}', ProductLot\ViewProductLotAction::class);
        $group->put('/{code}', ProductLot\UpdateProductLotAction::class);
        $group->get('/{code}/traceability/', ProductLot\TraceProductLotAction::class);
        $group->get('/{code}/export/', ProductLot\ExportProductLotAction::class);
    });

    // Company
    // Sales - Customers
    $app->group('/v1/sales/customers', function (Group $group) {
        $group->get('/', Sales\Customers\ListCustomerAction::class);
        $group->post('/', Sales\Customers\CreateCustomerAction::class);
        $group->get('/{code}', Sales\Customers\ViewCustomerAction::class);
        $group->put('/{code}', Sales\Customers\UpdateCustomerAction::class);
        $group->delete('/{code}', Sales\Customers\DeleteCustomerAction::class);
        $group->get('/generate-code/', Sales\Customers\GenerateCodeCustomerAction::class);
    });

    // Sales - Contracts
    $app->group('/v1/sales/contracts', function (Group $group) {
        $group->get('/', Sales\Contracts\ListContractAction::class);
        $group->post('/', Sales\Contracts\CreateContractAction::class);
        $group->get('/{code}', Sales\Contracts\ViewContractAction::class);
        $group->put('/{code}', Sales\Contracts\UpdateContractAction::class);
        $group->get('/generate-code/', Sales\Contracts\GenerateCodeContractAction::class);
    });

    // Sales - Orders
    $app->group('/v1/sales/orders', function (Group $group) {
        $group->get('/', Sales\Orders\ListOrderAction::class);
        $group->post('/', Sales\Orders\CreateOrderAction::class);
        $group->get('/purchases/', Sales\Orders\ListPurchaseOrderAction::class);
        $group->get('/product-lot-chart/', Sales\Orders\ProductLotChartAction::class);
        $group->get('/{code}', Sales\Orders\ViewOrderAction::class);
        $group->put('/{code}', Sales\Orders\UpdateOrderAction::class);
        $group->delete('/{code}', Sales\Orders\DeleteOrderAction::class);
        $group->put('/{code}/approve', Sales\Orders\ApproveOrderAction::class);
        $group->put('/{code}/cancel', Sales\Orders\CancelOrderAction::class);
        $group->get('/generate-code/', Sales\Orders\GenerateCodeOrderAction::class);
    });

    // Sales - Issues (Goods Issue)
    $app->group('/v1/sales/issues', function (Group $group) {
        $group->get('/', Sales\Issues\ListIssueAction::class);
        $group->post('/', Sales\Issues\CreateIssueAction::class);
        $group->get('/{code}', Sales\Issues\ViewIssueAction::class);
        $group->put('/{code}', Sales\Issues\UpdateIssueAction::class);
        $group->post('/{code}/issue', Sales\Issues\ConfirmIssueAction::class);
        $group->post('/{code}/cancel', Sales\Issues\CancelIssueAction::class);
        $group->delete('/{code}', Sales\Issues\DeleteIssueAction::class);
        $group->get('/generate-code/', Sales\Issues\GenerateCodeIssueAction::class);
    });

    // External Material
    $app->group('/v1/external-material', function (Group $group) {
        $group->get('/', ExternalMaterial\ListExternalMaterialAction::class);
        $group->post('/', ExternalMaterial\CreateExternalMaterialAction::class);
        $group->get('/{code}', ExternalMaterial\ViewExternalMaterialAction::class);
        $group->put('/{code}', ExternalMaterial\UpdateExternalMaterialAction::class);
        $group->delete('/{code}', ExternalMaterial\DeleteExternalMaterialAction::class);
        $group->put('/{code}/confirm', ExternalMaterial\ConfirmExternalMaterialAction::class);
        $group->put('/{code}/cancel', ExternalMaterial\CancelExternalMaterialAction::class);
    });

    $app->group('/v1/company', function (Group $group) {
        $group->get('/', Company\ListCompanyAction::class);
        $group->post('/', Company\CreateCompanyAction::class);
        $group->get('/{code}', Company\ViewCompanyAction::class);
        $group->put('/{code}', Company\UpdateCompanyAction::class);
        $group->delete('/{code}', Company\DeleteCompanyAction::class);
    });

    // Company Groups
    $app->group('/v1/company-group', function (Group $group) {
        $group->get('/', User\CompanyGroups\ListGroupAction::class);
        $group->post('/', User\CompanyGroups\CreateGroupAction::class);
        $group->put('/{code}', User\CompanyGroups\UpdateGroupAction::class);
        $group->delete('/{code}', User\CompanyGroups\DeleteGroupAction::class);
        $group->get('/{code}', User\CompanyGroups\ViewGroupAction::class);
        $group->get('/{code}/members/', User\CompanyGroups\ListGroupMembersAction::class);
        $group->put('/{code}/set-permissions/', User\CompanyGroups\SetGroupPermissionAction::class);
        $group->put('/{code}/assign-members/', User\CompanyGroups\AssignMemberToGroupAction::class);
    });

    // Roles
    $app->group('/v1/roles', function (Group $group) {
        $group->get('/', User\Roles\ListRoleAction::class);
        $group->get('/{role_id}/permissions/', User\Roles\ViewRolePermissionsAction::class);
        $group->put('/{role_id}/permissions/', User\Roles\SetRolePermissionsAction::class);
    });

    // Company Member
    $app->group('/v1/company-member', function (Group $group) {
        $group->get('/', User\CompanyMembers\ListMemberAction::class);
        $group->post('/', User\CompanyMembers\CreateMemberAction::class);
        $group->put('/{code}', User\CompanyMembers\UpdateMemberAction::class);
        $group->get('/{code}', User\CompanyMembers\ViewMemberAction::class);
        $group->delete('/{code}', User\CompanyMembers\DeleteMemberAction::class);
    });

    $app->add(function ($request, $handler) {
        $url = $this->get(SettingsInterface::class)->get('url_web');
        $parsedUrl = parse_url($url);
        $origin = $parsedUrl['host'];
        if (isset($parsedUrl['port'])) {
            $origin = $parsedUrl['host'] . ':' . $parsedUrl['port'];
        }

        $response = $handler->handle($request);
        return $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization, Cache-Control, Pragma, Expires')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
    });
    // Custom Fields
    $app->group('/v1/custom-fields', function (Group $group) {
        // Definitions (meta-schema)
        $group->get('/definitions/',          CustomField\ListDefinitionsAction::class);
        $group->post('/definitions/',         CustomField\CreateDefinitionAction::class);
        $group->get('/definitions/{code}',      CustomField\ViewDefinitionAction::class);
        $group->put('/definitions/{code}',      CustomField\UpdateDefinitionAction::class);
        $group->delete('/definitions/{code}',   CustomField\DeleteDefinitionAction::class);
        // Schema by entity type (for rendering forms)
        $group->get('/schema/{entity_type}',  CustomField\GetSchemaAction::class);
        // Values
        $group->get('/values/{entity_type}/{entity_id}',  CustomField\GetEntityValuesAction::class);
        $group->post('/values/{entity_type}/{entity_id}', CustomField\SetEntityValuesAction::class);
    });

    /**
     * Catch-all route to serve a 404 Not Found page if none of the routes match
     * NOTE: make sure this route is defined last
     */
    $app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
        throw new HttpNotFoundException($request);
    });
};
