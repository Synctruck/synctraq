<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\{ AssignedController, CellarController, ClientController, CommentsController, CompanyController, ConfigurationController, ChargeCompanyController, ChargeCompanyAdjustmentController, DriverController, IndexController, InventoryToolController, OrderController, PackageAgeController, PackageBlockedController, PackageController, PackageCheckController, PackageDeliveryController, PackageDispatchController, PackageDispatchDriverController, PackageFailedController, PackageHighPriorityController, PackageLmCarrierController, PackageInboundController, PalletDispatchController, PackageNeedMoreInformationController, PackageMiddleMileScanController, PackageMassQueryController, PackageTerminalController, PalletRtsController, PackageLostController,  PackageManifestController, PackageNotExistsController, PackagePreDispatchController, PackageWarehouseController,  PackageReturnCompanyController, PaymentDeliveryTeamController, RangePriceCompanyController, RangePriceTeamRouteCompanyController, ReportController, RoleController, RoutesController, StateController, StoreController, TeamController, Trackcontroller, UnassignedController, UserController, ViewerController,ValidatorController, RangePaymentTeamController, ToReversePackagesController, RangePaymentTeamByRouteController, RangePaymentTeamByCompanyController, PaymentTeamController, PaymentTeamAdjustmentController, ReportInvoiceController, PackageDispatchToMiddleMileController, ToDeductLostPackagesController, TrackpackageController};

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/home/public', [IndexController::class, 'IndexPublic']);
Route::get('/track', [Trackcontroller::class, 'Index']);
Route::get('/track/detail/{package_id}', [Trackcontroller::class, 'trackDetail']);
Route::get('/track-detail', [Trackcontroller::class, 'Index']);

//trackpackage that will be used as iFrame
Route::get('/trackpackage', [TrackpackageController::class, 'Index']);
Route::get('/trackpackage/detail/{package_id}', [TrackpackageController::class, 'trackDetail']);
Route::get('/trackpackage-detail', [TrackpackageController::class, 'Index']);

//============ User Login - Logout
Route::get('/', [UserController::class, 'Login']);
Route::post('/user/login', [UserController::class, 'ValidationLogin']);

Route::get('routes/getAll', [RoutesController::class, 'GetAll']);

Route::get('/package-history/search/{PACKAGE_ID}', [PackageController::class, 'Search']);
Route::get('/package-history/search-task/{TASK}', [PackageController::class, 'SearchTask']);
Route::post('/package-history/search-by-filters', [PackageController::class, 'SearchByFilters']);

Route::get('/package/all-delete/clear-package', [PackageController::class, 'DeleteClearPackage']);
Route::get('/package/all-change-to-delivery', [PackageController::class, 'ChangePackageToDispatch']);

Route::get('package-deliveries-dashboard', [PackageDeliveryController::class, 'DashboardIndex']);
Route::get('package-deliveries-dashboard/{dateRange}/{idTeam}/{idDriver}', [PackageDeliveryController::class, 'GetDeliveriesDashboard']);
Route::get('package-deliveries-dashboard/{startDate}/{endDate}/{idTeam}/{idDriver}', [PackageDeliveryController::class, 'GetDeliveriesDashboardByDates']);

Route::get('/package-manifest/update-height', [PackageManifestController::class, 'UpdateHeight']);

Route::group(['middleware' => 'auth'], function() {

	Route::get('errors/maintenance', function(){

		return view('errors.maintenance');
	});

    Route::get('package-blocked', [PackageBlockedController::class, 'Index'])->middleware('permission:packageBlocked.index');
    Route::get('package-blocked/list', [PackageBlockedController::class, 'List']);
    Route::post('package-blocked/insert', [PackageBlockedController::class, 'Insert']);
    Route::get('package-blocked/delete/{Reference}', [PackageBlockedController::class, 'Delete']);

	Route::get('/home', [IndexController::class, 'Index']);

	Route::get('/dashboard', [IndexController::class, 'Dashboard'])->middleware('permission:dashboard.index');
	Route::get('/dashboard/getallquantity/{startDate}/{endDate}', [IndexController::class, 'GetAllQuantity']);
	Route::get('/dashboard/getDataPerDate/{startDate}/{endDate}', [IndexController::class, 'GetDataPerDate']);

	Route::get('/package/insert-inland/{Reference}', [PackageController::class, 'InsertInland']);
	Route::post('/package-history/update', [PackageController::class, 'Update']);

	//============ Assigned
	Route::get('assigned', [AssignedController::class, 'Index']);
	Route::get('/assigned/list/{dataView}/{idTeam}', [AssignedController::class, 'List']);
	Route::post('/assigned/insert', [AssignedController::class, 'Insert']);

	//============ Assigned Team
	Route::get('assignedTeam', [AssignedController::class, 'IndexTeam'])->middleware('permission:assigned.index');
	Route::get('/assignedTeam/list/{dataView}/{idTeam}', [AssignedController::class, 'ListAssignedTeam']);
	Route::post('/assignedTeam/insert', [AssignedController::class, 'InsertDriver']);

	//============ Assigned Team
	Route::get('client/list', [ClientController::class, 'List']);
	Route::get('client/getAll', [ClientController::class, 'GetAll']);
	Route::post('client/insert', [ClientController::class, 'Insert']);
	Route::get('client/get/{id}', [ClientController::class, 'Get']);
	Route::post('client/update/{id}', [ClientController::class, 'Update']);
	Route::get('client/delete/{id}', [ClientController::class, 'Delete']);

	//============ Unassigned
	Route::get('/unassigned/', [UnassignedController::class, 'UnassignedIndex']);
	Route::get('/unassigned/list/{dataView}/{idTeam}', [UnassignedController::class, 'List']);
	Route::post('/unassigned/insert', [UnassignedController::class, 'Insert']);

	//============ Unassigned Team
	Route::get('/unassignedTeam/', [UnassignedController::class, 'IndexTeam'])->middleware('permission:unssigned.index');
	Route::get('/unassignedTeam/list/{dataView}/{idDriver}', [UnassignedController::class, 'ListUnassignedTeam']);
	Route::post('/unassignedTeam/insert', [UnassignedController::class, 'RemoveDriver']);

	//============ Maintenance package manifest
	Route::get('/package-manifest', [PackageManifestController::class, 'Index'])->middleware('permission:manifest.index');
	Route::get('/package-manifest/search/{PACKAGE_ID}', [PackageController::class, 'Search']);
	Route::get('/package-manifest/list/{status}/{idCompany}/{routes}/{states}', [PackageManifestController::class, 'List']);
	Route::get('/package-manifest/export/{status}/{idCompany}/{routes}/{states}/{type}', [PackageManifestController::class, 'Export']);
	Route::post('/package-manifest/insert', [PackageManifestController::class, 'Insert']);
	Route::get('/package-manifest/get/{PACKAGE_ID}', [PackageManifestController::class, 'Get']);
	Route::post('/package-manifest/update', [PackageManifestController::class, 'Update']);
	Route::post('/package-manifest/filter-check', [PackageManifestController::class, 'CheckFilter']);
	Route::post('/package-manifest/update/filter', [PackageManifestController::class, 'UpdateFilter']);
	Route::post('/package-manifest/import', [PackageManifestController::class, 'Import']);
	Route::get('/package-manifest/delete-duplicate', [PackageManifestController::class, 'DeleteDuplicate']);
	

	//============ Validation lost
	Route::get('/package-lost', [PackageLostController::class, 'Index'])->middleware('permission:lost.index');
	Route::get('/package-lost/list/{idCompany}/{dateStart}/{dateEnd}/{route}/{state}', [PackageLostController::class, 'List']);
	Route::get('/package-lost/export/{idCompany}/{dateStart}/{dateEnd}/{route}/{state}/{type}', [PackageLostController::class, 'Export']);
	Route::post('/package-lost/insert', [PackageLostController::class, 'Insert']);
	Route::post('/package-lost/import', [PackageLostController::class, 'Import']);
	Route::get('/package-lost/move-to-warehouse/{PACKAGE_ID}', [PackageLostController::class, 'MoveToWarehouse']);

	//============ Validation inbound
	Route::get('/package-inbound', [PackageInboundController::class, 'Index'])->middleware('permission:inbound.index');
	Route::get('/package-inbound/list/{idCompany}/{dateStart}/{dateEnd}/{route}/{state}', [PackageInboundController::class, 'List']);
	Route::get('/package-inbound/export/{idCompany}/{dateStart}/{dateEnd}/{route}/{state}/{type}', [PackageInboundController::class, 'Export']);
	Route::post('/package-inbound/insert', [PackageInboundController::class, 'Insert']);
	Route::get('/package-inbound/get/{PACKAGE_ID}', [PackageInboundController::class, 'Get']);
	Route::post('/package-inbound/update', [PackageInboundController::class, 'Update']);
	Route::post('/package-inbound/import', [PackageInboundController::class, 'Import']);
	Route::get('/package-inbound/pdf-label/{Reference}', [PackageInboundController::class, 'PdfLabel']);
	Route::get('/package-inbound/download/roadwarrior/{idCompany}/{StateSearch}/{RouteSearch}/{initDate}/{endDate}', [PackageInboundController::class, 'DownloadRoadWarrior']);
	Route::get('/package-inbound/delete-in-delivery', [PackageInboundController::class, 'DeleteInDelivery']);

	//============ Validation INVENTORY TOOL
	Route::get('/inventory-tool', [InventoryToolController::class, 'Index'])->middleware('permission:inventory-tool.index');
	Route::get('/inventory-tool/list/{dateStart}/{dateEnd}', [InventoryToolController::class, 'List']);
	Route::post('/inventory-tool/insert', [InventoryToolController::class, 'Insert']);
	Route::get('/inventory-tool/finish/{idInventory}', [InventoryToolController::class, 'Finish']);
	Route::get('/inventory-tool/download/{idInventory}', [InventoryToolController::class, 'Download']);
	Route::get('/inventory-tool/list-detail/{idInventory}', [InventoryToolController::class, 'ListInventoryDetail']);
	Route::post('/inventory-tool/insert-package', [InventoryToolController::class, 'InsertPackage']);
	Route::get('/inventory-tool/export/{dateStart}/{dateEnd}', [InventoryToolController::class, 'Export']);
	Route::post('/inventory-tool/send-pallet', [InventoryToolController::class, 'SendPallet']);

	//============ PACKAGE NMI
	Route::get('/package-nmi', [PackageNeedMoreInformationController::class, 'Index'])->middleware('permission:nmi.index');
	Route::get('/package-nmi/list/{idCompany}/{dateStart}/{dateEnd}/{route}/{state}', [PackageNeedMoreInformationController::class, 'List']);
	Route::post('/package-nmi/insert', [PackageNeedMoreInformationController::class, 'Insert']);
	Route::get('/package-nmi/get/{PACKAGE_ID}', [PackageNeedMoreInformationController::class, 'Get']);
	Route::post('/package-nmi/update', [PackageNeedMoreInformationController::class, 'Update']);
	Route::get('/package-nmi/export/{idCompany}/{dateStart}/{dateEnd}/{route}/{state}/{type}', [PackageNeedMoreInformationController::class, 'Export']);
	Route::get('/package-nmi/move-to-warehouse/{PACKAGE_ID}', [PackageNeedMoreInformationController::class, 'MoveToWarehouse']);

	//============ Dispatch package
	Route::get('/package-dispatch', [PackageDispatchController::class, 'Index'])->middleware('permission:dispatch.index');
	Route::get('/package-dispatch/list/{idCompany}/{dateStart}/{dateEnd}/{idTeam}/{idDriver}/{states}/{routes}/{idCellar}', [PackageDispatchController::class, 'List']);
	Route::get('/package-dispatch/export/{idCompany}/{dateStart}/{dateEnd}/{idTeam}/{idDriver}/{states}/{routes}/{idCellar}/{type}', [PackageDispatchController::class, 'Export']);
	Route::get('/package-dispatch/getAll', [PackageDispatchController::class, 'GetAll']);
	Route::get('/package-dispatch/get-by-team-driver/{idTeam}/{idDriver}', [PackageDispatchController::class, 'GetByTeamDriver']);
	Route::post('/package-dispatch/insert', [PackageDispatchController::class, 'Insert']);
	Route::get('/package-dispatch/get/{PACKAGE_ID}', [PackageDispatchController::class, 'Get']);
	Route::post('/package-dispatch/update', [PackageDispatchController::class, 'Update']);
	Route::post('/package-dispatch/change', [PackageDispatchController::class, 'Change']);
	Route::post('/package-dispatch/import', [PackageDispatchController::class, 'Import']);
	Route::get('/package-dispatch/getCoordinates/{taskOnfleet}', [PackageDispatchController::class, 'GetOnfleetShorId']);
	Route::get('/package-dispatch/update/prices-teams/{startDate}/{endDate}', [PackageDispatchController::class, 'UpdatePriceTeams']);
	Route::post('/package-dispatch/update/change-team', [PackageDispatchController::class, 'UpdateChangeTeam']);

	//============ PALET RTS
	Route::get('/pallet-rts/list/{dateStart}/{dateEnd}/', [PalletRtsController::class, 'List']);
	Route::get('/pallet-rts/export/{idCompany}/{dateStart}/{dateEnd}/', [PalletRtsController::class, 'Export']);
	Route::post('/pallet-rts/insert', [PalletRtsController::class, 'Insert']);
	Route::get('/pallet-rts/print/{numberPallet}', [PalletRtsController::class, 'Print']);

	Route::get('/package-pre-rts', [PackageReturnCompanyController::class, 'IndexPreRts'])->middleware('permission:prerts.index');
	Route::get('/package-pre-rts/list/{numberPallet}', [PackageReturnCompanyController::class, 'ListPreRts']);
	Route::post('/package-pre-rts/insert', [PackageReturnCompanyController::class, 'InsertPreRts']);
	Route::get('/package-pre-rts/export/{dateInit}/{dateEnd}/{routes}/{states}', [PackageReturnCompanyController::class, 'Export']);
	Route::post('/package-pre-rts/chage-to-return-company', [PackageReturnCompanyController::class, 'ChangeToReturnCompany']);
	Route::get('/package-rts/move-to-warehouse/{PACKAGE_ID}', [PackageReturnCompanyController::class, 'MoveToWarehouse']);

	//============ PALET DISPACTH
	Route::get('/pallet-dispatch/list/{dateStart}/{dateEnd}/{routes}', [PalletDispatchController::class, 'List']);
	Route::post('/pallet-dispatch/insert', [PalletDispatchController::class, 'Insert']);
	Route::get('/pallet-dispatch/delete/{numberPallet}', [PalletDispatchController::class, 'Delete']);
	Route::get('/pallet-dispatch/export/{dateStart}/{dateEnd}/{routes}', [PalletDispatchController::class, 'Export']);
	Route::get('/pallet-dispatch/print/{numberPallet}', [PalletDispatchController::class, 'Print']);

	//============ Dispatch package
	Route::get('/package-pre-dispatch', [PackagePreDispatchController::class, 'Index'])->middleware('permission:predispatch.index');
	Route::get('/package-pre-dispatch/list/{numberPallet}', [PackagePreDispatchController::class, 'List']);
	Route::get('/package-pre-dispatch/export/{idCompany}/{dateStart}/{dateEnd}/{idTeam}/{idDriver}/{states}/{routes}', [PackagePreDispatchController::class, 'Export']);
	Route::get('/package-pre-dispatch/getAll', [PackagePreDispatchController::class, 'GetAll']);
	Route::post('/package-pre-dispatch/insert', [PackagePreDispatchController::class, 'Insert']);
	Route::post('/package-pre-dispatch/chage-to-dispatch', [PackagePreDispatchController::class, 'ChangeToDispatch']);

	//============ Failed package
	Route::get('/package-failed', [PackageFailedController::class, 'Index'])->middleware('permission:failed.index');
	Route::get('/package-failed/list/{idCompany}/{dateStart}/{dateEnd}/{idTeam}/{idDriver}/{states}/{routes}', [PackageFailedController::class, 'List']);
	Route::get('/package-failed/move/prefailed-to-failed', [PackageFailedController::class, 'MovePreFailedToFailed']);
	Route::get('/package-failed/export/{idCompany}/{dateStart}/{dateEnd}/{idTeam}/{idDriver}/{states}/{routes}', [PackageFailedController::class, 'Export']);

	//============ Validation delivery
	Route::get('/package-delivery', [PackageDeliveryController::class, 'Index'])->middleware('permission:delivery.index');
	Route::get('/package-delivery/list', [PackageDeliveryController::class, 'List']);
	Route::post('/package-delivery/insert', [PackageDeliveryController::class, 'Insert']);
	Route::post('/package-delivery/import', [PackageDeliveryController::class, 'Import']);
	Route::post('/package-delivery/import-photo', [PackageDeliveryController::class, 'ImportPhoto']);
	Route::get('/package-delivery/updatedTeamOrDriverFailed', [PackageDeliveryController::class, 'UpdatedTeamOrDriverFailed']);
	Route::get('/package-delivery/updatedDeliverFields', [PackageDeliveryController::class, 'UpdatedDeliverFields']);
	Route::get('/package-delivery/updatedCreatedDate', [PackageDeliveryController::class, 'UpdatedCreatedDate']);
	Route::get('/package-delivery/check', [PackageDeliveryController::class, 'IndexForCheck'])->middleware('permission:checkDelivery.index');
	Route::get('/package-delivery/list-for-check/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}', [PackageDeliveryController::class, 'ListForCheck']);
	Route::post('/package-delivery/insert-for-check', [PackageDeliveryController::class, 'InsertForCheck']);
	Route::get('/package-delivery/confirmation-check', [PackageDeliveryController::class, 'ConfirmationCheck']);
	Route::get('/package-delivery/finance', [PackageDeliveryController::class, 'IndexFinance'])->middleware('permission:validatedDelivery.index');
	Route::get('/package-delivery/list-finance/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{checked}/{routes}/{states}', [PackageDeliveryController::class, 'ListFinance']);
	Route::get('/package-delivery/list-invoiced', [PackageDeliveryController::class, 'ListInvoiced']);

	//=========== Charge Company
	Route::get('/charge-company', [ChargeCompanyController::class, 'Index']);
	Route::get('/charge-company/list/{dateInit}/{initDate}/{endDate}/{idCompany}/{status}', [ChargeCompanyController::class, 'List']);
	Route::get('/charge-company/confirm/{idCharge}/{status}', [ChargeCompanyController::class, 'Confirm']);
	Route::get('/charge-company/import', [ChargeCompanyController::class, 'Import']);
	Route::get('/charge-company/export/{id}/{download}', [ChargeCompanyController::class, 'Export']);
	Route::get('/charge-company/export-all/{dateInit}/{initDate}/{endDate}/{idCompany}/{status}', [ChargeCompanyController::class, 'ExportAll']);

	Route::get('/charge-company/delete-detail', [ChargeCompanyController::class, 'DeletePackagesDetail']);

	Route::get('/charge-company-adjustment/{idCharge}', [ChargeCompanyAdjustmentController::class, 'List']);
	Route::post('/charge-company-adjustment/insert', [ChargeCompanyAdjustmentController::class, 'Insert']);

	//=========== Payment Team
	Route::get('/payment-team', [PaymentTeamController::class, 'Index']);
	Route::get('/payment-team/list/{dateInit}/{initDate}/{endDate}/{idteam}/{status}', [PaymentTeamController::class, 'List']);
	Route::get('/payment-team/edit/{idPayment}', [PaymentTeamController::class, 'Edit']);

	Route::get('/payment-team/list-by-route/{idPayment}', [PaymentTeamController::class, 'ListByRoute']);
	Route::post('/payment-team/insert-pod-failed', [PaymentTeamController::class, 'InserPODFailed']);
	Route::get('/payment-team/list-by-pod-failed/{idPayment}', [PaymentTeamController::class, 'ListByPODFailed']);
	Route::get('/payment-team/list-revert-shipments/{idPayment}', [PaymentTeamController::class, 'ListRevertShipments']);
	Route::get('/payment-team/status-change/{idpayment}/{status}', [PaymentTeamController::class, 'StatusChange']);
	Route::get('/payment-team/import', [PaymentTeamController::class, 'Import']);
	Route::get('/payment-team/export/{id}', [PaymentTeamController::class, 'Export']);
	Route::get('/payment-team/export-receipt/{id}/{type}', [PaymentTeamController::class, 'ExportReceipt']);
	Route::get('/payment-team/export-all/{dateInit}/{initDate}/{endDate}/{idCompany}/{status}', [PaymentTeamController::class, 'ExportAll']);
	Route::get('/payment-team/delete-detail', [PaymentTeamController::class, 'DeletePackagesDetail']);
	Route::get('/payment-team/recalculate/{idPayment}', [PaymentTeamController::class, 'Recalculate']);
	Route::get('/payment-team-adjustment/{idPaymentTeam}', [PaymentTeamAdjustmentController::class, 'List']);
	Route::post('/payment-team-adjustment/insert', [PaymentTeamAdjustmentController::class, 'Insert']);
	Route::get('/payment-team/deductions', [PaymentTeamController::class, 'CalculateDeduction']);
	
	//=========== Payment Team Revert
	Route::get('/payment-revert', [ToReversePackagesController::class, 'Index'])->middleware('permission:paymentTeamReverts.index');
	Route::get('/payment-revert/{dateInit}/{dateEnd}/{idTeam}/{status}', [ToReversePackagesController::class, 'List']);
	Route::post('/payment-revert/insert', [ToReversePackagesController::class, 'Insert']);

	//=========== Payment Team Revert
	Route::get('/to-deduct-lost-packages', [ToDeductLostPackagesController::class, 'Index'])->middleware('permission:toDeductLostPackages.index');
	Route::get('/to-deduct-lost-packages/list', [ToDeductLostPackagesController::class, 'List']);
	Route::get('/to-deduct-lost-packages/update/{shipmentId}/{priceToDeduct}', [ToDeductLostPackagesController::class, 'UpdateDeductPrice']);
	Route::get('/to-deduct-lost-packages/update-team/{shipmentId}/{idTeam}', [ToDeductLostPackagesController::class, 'UpdateTeam']);

	//=========== PAYMENT TEAM
	Route::get('/payment-delivery-team', [PaymentDeliveryTeamController::class, 'Index']);
	Route::get('/payment-delivery/list/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}', [PaymentDeliveryTeamController::class, 'List']);
	Route::post('/payment-delivery/insert', [PaymentDeliveryTeamController::class, 'Insert']);
	Route::get('/payment-delivery/export/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}', [PaymentDeliveryTeamController::class, 'Export']);


	//=========== Age of Package
	Route::get('/package-age', [PackageAgeController::class, 'Index']);
	Route::get('/package-age/list/{idCompany}/{routes}/{states}/{status}', [PackageAgeController::class, 'List']);
	Route::get('/package-age/export/{idCompany}/{routes}/{states}/{status}', [PackageAgeController::class, 'Export']);

	Route::get('/package-high-priority', [PackageHighPriorityController::class, 'Index'])->middleware('permission:highPriority.index');
	Route::get('/package-high-priority/list/{idCompany}/{routes}/{states}', [PackageHighPriorityController::class, 'List']);
	Route::get('/package-high-priority/export/{idCompany}/{routes}/{states}/{type}', [PackageHighPriorityController::class, 'Export']);

	//============ Validation package not exists
	Route::get('/package-not-exists', [PackageNotExistsController::class, 'Index']);
	Route::get('/package-not-exists/list', [PackageNotExistsController::class, 'List']);
	Route::get('/package-not-exists/export-excel', [PackageNotExistsController::class, 'ExportExcel']);

	Route::get('/package/return', [PackageController::class, 'IndexReturn'])->middleware('permission:reinbound.index');
	Route::get('/package/list/return/{idCompany}/{dateStart}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}', [PackageController::class, 'ListReturn']);
	Route::get('/package/list/return/export/{idCompany}/{dateStart}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}/{type}', [PackageController::class, 'ListReturnExport']);
	Route::post('/package/return/dispatch', [PackageDispatchController::class, 'Return']);
	Route::get('/package/download/roadwarrior/{idCompany}/{idTeam}/{idDriver}/{StateSearch}/{RouteSearch}/{initDate}/{endDate}', [PackageController::class, 'DownloadRoadWarrior']);

	Route::post('/package/dispatch/import', [PackageController::class, 'ImportDispatch']);

	//============ Validation warehouse
	Route::get('/package-warehouse', [PackageWarehouseController::class, 'Index'])->middleware('permission:warehouse.index');
	Route::get('/package-warehouse/list/{idCompany}/{idValidator}/{dateStart}/{dateEnd}/{route}/{state}/{idCellar}', [PackageWarehouseController::class, 'List']);
	Route::post('/package-warehouse/insert', [PackageWarehouseController::class, 'Insert']);
	Route::get('/package-warehouse/list-in-delivery', [PackageWarehouseController::class, 'ListInDelivery']);
	Route::get('/package-warehouse/delete-in-delivery', [PackageWarehouseController::class, 'DeleteInDelivery']);
	Route::get('/package-warehouse/export/{idCompany}/{idValidator}/{dateStart}/{dateEnd}/{route}/{state}/{idCellar}/{type}', [PackageWarehouseController::class, 'Export']);
	Route::post('/package-warehouse/import', [PackageWarehouseController::class, 'Import']);

	//============ Validation warehouse
	Route::get('/package-mms', [PackageMiddleMileScanController::class, 'Index'])->middleware('permission:mms.index');
	Route::get('/package-mms/list/{idCompany}/{idValidator}/{dateStart}/{dateEnd}/{route}/{state}', [PackageMiddleMileScanController::class, 'List']);
	Route::post('/package-mms/insert', [PackageMiddleMileScanController::class, 'Insert']);
	Route::get('/package-mms/list-in-delivery', [PackageMiddleMileScanController::class, 'ListInDelivery']);
	Route::get('/package-mms/delete-in-delivery', [PackageMiddleMileScanController::class, 'DeleteInDelivery']);
	Route::get('/package-mms/export/{idCompany}/{idValidator}/{dateStart}/{dateEnd}/{route}/{state}/{type}', [PackageMiddleMileScanController::class, 'Export']);

	//============ Validation LM CARRIER
	Route::get('/package-lm-carrier', [PackageLmCarrierController::class, 'Index'])->middleware('permission:mms.index');
	Route::get('/package-lm-carrier/list/{idCompany}/{dateStart}/{dateEnd}/{route}/{state}', [PackageLmCarrierController::class, 'List']);
	Route::get('/package-lm-carrier/export/{idCompany}/{idValidator}/{dateStart}/{dateEnd}/{route}/{state}/{type}', [PackageLmCarrierController::class, 'Export']);
	Route::post('/package-lm-carrier/send-pallet', [PackageLmCarrierController::class, 'SendPallet']);

	//============ Validation Package DispatchToMiddleMile
	Route::get('/package-dispatch-to-middlemile', [PackageDispatchToMiddleMileController::class, 'Index'])->middleware('permission:packageDispatchToMiddleMile.index');
	Route::get('/package-dispatch-to-middlemile/list/{idCompany}/{dateStart}/{dateEnd}/{route}/{state}', [PackageDispatchToMiddleMileController::class, 'List']);
	Route::get('/package-dispatch-to-middlemile/export/{idCompany}/{idValidator}/{dateStart}/{dateEnd}/{route}/{state}/{type}', [PackageDispatchToMiddleMileController::class, 'Export']);
	Route::post('/package-dispatch-to-middlemile/send-pallet', [PackageDispatchToMiddleMileController::class, 'SendPallet']);

	//============ Maintenance of users
	Route::get('role/list', [RoleController::class, 'List']);

	Route::get('cellar/get-all', [CellarController::class, 'GetAll']);
	Route::get('cellar/list-active', [CellarController::class, 'ListActive']);

    Route::get('/roles',[RoleController::class,'index'])->middleware('permission:role.index');
    Route::get('/roles/getList',[RoleController::class,'getList']);
    Route::get('/roles/getPermissions',[RoleController::class,'getPermissions']);
    Route::post('/roles/insert',[RoleController::class,'create']);
    Route::get('/roles/{role_id}',[RoleController::class,'getRole']);
    Route::put('/roles/update/{role_id}',[RoleController::class,'update']);
    Route::delete('/roles/delete',[RoleController::class,'delete']);

	//============ Maintenance of comments
	Route::get('comments', [CommentsController::class, 'Index'])->middleware('permission:comment.index');
	Route::get('comments/list', [CommentsController::class, 'List']);
	Route::get('comments/get-all-by-category/{category}', [CommentsController::class, 'GetAllByCategory']);
	Route::post('comments/insert', [CommentsController::class, 'Insert']);
	Route::get('comments/get/{id}', [CommentsController::class, 'Get']);
	Route::post('comments/update/{id}', [CommentsController::class, 'Update']);
	Route::get('comments/delete/{id}', [CommentsController::class, 'Delete']);

	//============ Maintenance of company
	Route::get('company', [CompanyController::class, 'Index'])->middleware('permission:company.index');
	Route::get('company/list', [CompanyController::class, 'List']);
	Route::get('company/getAll', [CompanyController::class, 'GetAll']);
	Route::get('company/getAll/delivery', [CompanyController::class, 'GetAllDelivery']);
	Route::post('company/insert', [CompanyController::class, 'Insert']);
	Route::get('company/get/{id}', [CompanyController::class, 'Get']);
	Route::post('company/update/{id}', [CompanyController::class, 'Update']);
	Route::get('company/delete/{id}', [CompanyController::class, 'Delete']);

	//============ Maintenance of anti-scan
	Route::get('anti-scan', [StateController::class, 'Index'])->middleware('permission:antiscan.index');
	Route::get('anti-scan/list', [StateController::class, 'List']);
	Route::post('anti-scan/insert', [StateController::class, 'Insert']);
	Route::get('anti-scan/get/{id}', [StateController::class, 'Get']);
	Route::post('anti-scan/update/{id}', [StateController::class, 'Update']);
	Route::get('anti-scan/delete/{id}', [StateController::class, 'Delete']);

	//============ Maintenance of drivers
	Route::get('driver', [DriverController::class, 'Index'])->middleware('permission:driver.index');
	Route::get('driver/list', [DriverController::class, 'List']);
	Route::get('driver/team/list/{idTeam}/{usageApp}', [DriverController::class, 'ListAllByTeam']);
    Route::get('driver/team/list/{idTeam}', [DriverController::class, 'ListUserByTeam']);
	Route::post('driver/insert', [DriverController::class, 'Insert']);
	Route::get('driver/get/{id}', [DriverController::class, 'Get']);
	Route::post('driver/update/{id}', [DriverController::class, 'Update']);
	Route::get('driver/changeStatus/{id}', [DriverController::class, 'ChangeStatus']);
	Route::get('driver/delete/{id}', [DriverController::class, 'Delete']);
	Route::get('driver/synchronize/{id}', [DriverController::class, 'SynchronizeNewSystem']);
	Route::get('driver/defrief', [DriverController::class, 'IndexDebrief']);
	Route::get('driver/defrief/list/{idTeam}', [DriverController::class, 'ListDebrief']);
	Route::get('driver/defrief/list-packages/{idDriver}', [DriverController::class, 'ListPackagesDebrief']);
	Route::get('driver/defrief/packages-change-status/{PACKAGE_ID}/{stsatus}/{comment}', [DriverController::class, 'ChangeStatusPackageDebrief']);

	//============ Maintenance of stores
	Route::get('stores/list/{idCompany}', [StoreController::class, 'List']);
	Route::post('stores/insert', [StoreController::class, 'Insert']);
	Route::get('stores/get/{id}', [StoreController::class, 'Get']);
	Route::post('stores/update/{id}', [StoreController::class, 'Update']);
	Route::get('stores/delete/{id}', [StoreController::class, 'Delete']);

	//============ Maintenance of ranges company
	Route::get('range-price-company/list/{idCompany}', [RangePriceCompanyController::class, 'List']);
	Route::post('range-price-company/insert', [RangePriceCompanyController::class, 'Insert']);
	Route::get('range-price-company/get/{id}', [RangePriceCompanyController::class, 'Get']);
	Route::post('range-price-company/update/{id}', [RangePriceCompanyController::class, 'Update']);
	Route::get('range-price-company/delete/{id}', [RangePriceCompanyController::class, 'Delete']);
	Route::get('range-price-company/update/prices', [RangePriceCompanyController::class, 'UpdatePrices']);

	//============ Maintenance of ranges prices teams
	Route::get('range-price-base-team/list/{idTeam}', [RangePaymentTeamController::class, 'List']);
	Route::post('range-price-base-team/insert', [RangePaymentTeamController::class, 'Insert']);
	Route::get('range-price-base-team/get/{id}', [RangePaymentTeamController::class, 'Get']);
	Route::post('range-price-base-team/update/{id}', [RangePaymentTeamController::class, 'Update']);
	Route::get('range-price-base-team/delete/{id}', [RangePaymentTeamController::class, 'Delete']);
	Route::get('range-price-base-team/update/prices', [RangePaymentTeamController::class, 'UpdatePrices']);

	//============ Maintenance of ranges prices teams by route
	Route::get('range-price-team-by-route/list/{idTeam}', [RangePaymentTeamByRouteController::class, 'List']);
	Route::post('range-price-team-by-route/insert', [RangePaymentTeamByRouteController::class, 'Insert']);
	Route::get('range-price-team-by-route/get/{id}', [RangePaymentTeamByRouteController::class, 'Get']);
	Route::post('range-price-team-by-route/update/{id}', [RangePaymentTeamByRouteController::class, 'Update']);
	Route::get('range-price-team-by-route/delete/{id}', [RangePaymentTeamByRouteController::class, 'Delete']);
	Route::get('range-price-team-by-route/update/prices', [RangePaymentTeamByRouteController::class, 'UpdatePrices']);

	//============ Maintenance of ranges prices teams by company
	Route::get('range-price-team-by-company/list/{idTeam}', [RangePaymentTeamByCompanyController::class, 'List']);
	Route::post('range-price-team-by-company/insert', [RangePaymentTeamByCompanyController::class, 'Insert']);
	Route::get('range-price-team-by-company/get/{id}', [RangePaymentTeamByCompanyController::class, 'Get']);
	Route::post('range-price-team-by-company/update/{id}', [RangePaymentTeamByCompanyController::class, 'Update']);
	Route::get('range-price-team-by-company/delete/{id}', [RangePaymentTeamByCompanyController::class, 'Delete']);
	Route::get('range-price-team-by-company/update/prices', [RangePaymentTeamByCompanyController::class, 'UpdatePrices']);

	//============ Maintenance of ranges teams
	Route::get('range-price-team-route-company/list/{idTeam}/{idCompany}/{Route}', [RangePriceTeamRouteCompanyController::class, 'List']);
	Route::post('range-price-team-route-company/insert', [RangePriceTeamRouteCompanyController::class, 'Insert']);
	Route::get('range-price-team-route-company/get/{id}', [RangePriceTeamRouteCompanyController::class, 'Get']);
	Route::post('range-price-team-route-company/update/{id}', [RangePriceTeamRouteCompanyController::class, 'Update']);
	Route::get('range-price-team-route-company/delete/{id}', [RangePriceTeamRouteCompanyController::class, 'Delete']);
	Route::post('range-price-team-route-company/import', [RangePriceTeamRouteCompanyController::class, 'Import']);
	Route::get('range-price-team-route-company/list-configuration-price-team/{idTeam}', [RangePriceTeamRouteCompanyController::class, 'ListConfigurationPrice']);
	Route::get('range-price-team-route-company/get-prices-team/{idTeam}/{routes}', [RangePriceTeamRouteCompanyController::class, 'GetPricesByIdTeam']);

	//============ Processof orders
	Route::get('orders', [OrderController::class, 'Index'])->middleware('permission:orders.index');
    Route::get('orders/list/{idCompany}/{routes}/{states}', [OrderController::class, 'List']);
    Route::post('orders/number-search', [OrderController::class, 'SearchOrderNumber']);
    Route::post('orders/insert', [OrderController::class, 'Insert']);
    Route::get('orders/print/{PACKAGE_ID}', [OrderController::class, 'Print']);
    Route::get('orders/delete/{PACKAGE_ID}', [OrderController::class, 'Delete']);

	//============ Maintenance of teams
	Route::get('routes', [RoutesController::class, 'Index'])->middleware('permission:route.index');
	Route::get('routes/list/{CitySearchList}/{CountySearchList}/{TypeSearchList}/{StateSearchList}/{RouteSearchList}/{LatitudeSearchList}/{LongitudeSearchList}', [RoutesController::class, 'List']);
	Route::get('routes/filter/list', [RoutesController::class, 'FilterList']);
	Route::get('routes-aux/list', [RoutesController::class, 'AuxList']);
	Route::post('routes/insert', [RoutesController::class, 'Insert']);
	Route::post('routes/import', [RoutesController::class, 'Import']);
	Route::get('routes/zip-code-delete/{zipCode}', [RoutesController::class, 'DeleteZipCode']);
	Route::get('routes/get/{id}', [RoutesController::class, 'Get']);
	Route::post('routes/update/{id}', [RoutesController::class, 'Update']);
	Route::get('routes/delete/{id}', [RoutesController::class, 'Delete']);
	Route::get('routes/update/package/manifest/inbound/warehouse', [RoutesController::class, 'UpdateRoutePackageManifestInboundWarehouse']);
	Route::get('routes/update/package', [RoutesController::class, 'UpdateRoutePackage']);
	Route::get('routes/pass/routes-aux', [RoutesController::class, 'UpdatePassRouteAux']);
	Route::get('routes/pass/routes-zip-code', [RoutesController::class, 'UpdatePassRoutesZipCode']);

	//============ Maintenance of teams
	Route::get('team', [TeamController::class, 'Index'])->middleware('permission:team.index');
	Route::get('team/list', [TeamController::class, 'List']);
	Route::get('team/listall', [TeamController::class, 'ListAll']);
	Route::get('team/list-all-filter', [TeamController::class, 'ListAllFilter']);
	Route::post('team/insert', [TeamController::class, 'Insert']);
	Route::get('team/get/{id}', [TeamController::class, 'Get']);
	Route::post('team/update/{id}', [TeamController::class, 'Update']);
	Route::get('team/changeStatus/{id}', [TeamController::class, 'ChangeStatus']);
	Route::get('team/delete/{id}', [TeamController::class, 'Delete']);

	//============ Maintenance of users
	Route::get('user', [UserController::class, 'Index'])->middleware('permission:admin.index');
	Route::get('user/list', [UserController::class, 'List']);
	Route::post('user/insert', [UserController::class, 'Insert']);
	Route::get('user/get/{id}', [UserController::class, 'Get']);
	Route::post('user/update/{id}', [UserController::class, 'Update']);
	Route::get('user/delete/{id}', [UserController::class, 'Delete']);
	Route::get('user/changePassword', [UserController::class, 'ChangePassword']);
	Route::post('user/changePassword/save', [UserController::class, 'SaveChangePassword']);
	Route::get('user/resetPassword/{userEmail}', [UserController::class, 'ResetPassword']);
	Route::get('profile', [UserController::class, 'Profile']);
	Route::post('profile', [UserController::class, 'UpdateProfile']);
	Route::get('getProfile', [UserController::class, 'getProfile']);



	Route::get('user/logout', [UserController::class, 'Logout']);

	Route::get('/reports', [ReportController::class, 'Index']);
	Route::get('/reports/general', [ReportController::class, 'general'])->middleware('permission:report.index');

	Route::get('/report/manifest', [ReportController::class, 'IndexManifest'])->middleware('permission:reportManifest.index');
	Route::get('/report/list/manifest/{idCompany}/{dateInit}/{dateEnd}/{routes}/{states}', [ReportController::class, 'ListManifest']);
	Route::get('/report/export/manifest/{idCompany}/{dateInit}/{dateEnd}/{routes}/{states}/{type}', [ReportController::class, 'ExportManifest']);

	Route::get('/report/mms', [ReportController::class, 'IndexMMS'])->middleware('permission:reportMiddleMileScan.index');
	Route::get('/report/list/mms/{idCompany}/{dateInit}/{dateEnd}/{routes}/{states}', [ReportController::class, 'ListMMS']);
	Route::get('/report/export/mms/{idCompany}/{dateInit}/{dateEnd}/{routes}/{states}/{type}', [ReportController::class, 'ExportMMS']);

	Route::get('/report/lm-carrier', [ReportController::class, 'IndexLmCarrier'])->middleware('permission:reportLmCarrier.index');
	Route::get('/report/list/lm-carrier/{idCompany}/{dateInit}/{dateEnd}/{routes}/{states}', [ReportController::class, 'ListLmCarrier']);
	Route::get('/report/export/lm-carrier/{idCompany}/{dateInit}/{dateEnd}/{routes}/{states}/{type}', [ReportController::class, 'ExportLmCarrier']);

	Route::get('/report/inbound', [ReportController::class, 'IndexInbound'])->middleware('permission:reportInbound.index');
	Route::get('/report/list/inbound/{idCompany}/{dateInit}/{dateEnd}/{routes}/{states}/{truck}', [ReportController::class, 'ListInbound']);
	Route::get('/report/export/inbound/{idCompany}/{dateInit}/{dateEnd}/{routes}/{states}/{truck}/{type}', [ReportController::class, 'ExportInbound']);

	//lost
	Route::get('/report/lost', [ReportController::class, 'IndexLost'])->middleware('permission:reportLost.index');
	Route::get('/report/list/lost/{idCompany}/{idTeam}/{dateInit}/{dateEnd}/{routes}/{states}', [ReportController::class, 'ListLost']);
	Route::get('/report/export/lost/{idCompany}/{idTeam}/{dateInit}/{dateEnd}/{routes}/{states}/{type}', [ReportController::class, 'ExportLost']);

	Route::get('/report/delivery', [ReportController::class, 'IndexDelivery'])->middleware('permission:reportDelivery.index');
	Route::get('/report/list/delivery/{idCompany}/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}', [ReportController::class, 'ListDelivery']);
	Route::get('/report/export/delivery/{idCompany}/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}/{type}', [ReportController::class, 'ExportDelivery']);

	Route::get('/report/dispatch', [ReportController::class, 'IndexDispatch'])->middleware('permission:reportDispatch.index');
	Route::get('/report/list/dispatch/{idCompany}/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}', [ReportController::class, 'ListDispatch']);
	Route::get('/report/export/dispatch/{idCompany}/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}/{type}', [ReportController::class, 'ExportDispatch']);

	Route::get('/report/delete', [ReportController::class, 'IndexDelete'])->middleware('permission:reportDelete.index');
	Route::get('/report/list/delete/{idCompany}/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}/{statusDescription}', [ReportController::class, 'ListDelete']);
	Route::get('/report/export/delete/{idCompany}/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}/{statusDescription}/{type}', [ReportController::class, 'ExportDelete']);

	Route::get('/report/failed', [ReportController::class, 'IndexFailed'])->middleware('permission:reportFailed.index');
	Route::get('/report/list/failed/{idCompany}/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}/{statusDescription}', [ReportController::class, 'ListFailed']);
	Route::get('/report/export/failed/{idCompany}/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}/{statusDescription}/{type}', [ReportController::class, 'ExportFailed']);

	Route::get('/report/notExists', [ReportController::class, 'IndexNotExists'])->middleware('permission:reportNotexists.index');
	Route::get('/report/list/notexists/{dateInit}/{dateEnd}', [ReportController::class, 'ListNotExists']);
	Route::get('/report/export/notexists/{dateInit}/{dateEnd}', [ReportController::class, 'ExportNotExists']);

	Route::get('/report/assigns', [ReportController::class, 'IndexAssigns']);
	Route::get('/report/list/assigns/{dateInit}/{dateEnd}/{routes}/{states}', [ReportController::class, 'ListAssigns']);
	Route::get('/report/export/assigns/{dateInit}/{dateEnd}/{routes}/{states}', [ReportController::class, 'ExportAssigns']);

	Route::get('/report/return-company', [PackageReturnCompanyController::class, 'Index'])->middleware('permission:reportReturncompany.index');
	Route::get('/report/return-company/list/{dateInit}/{dateEnd}/{idCompany}/{routes}/{states}', [PackageReturnCompanyController::class, 'List']);
	Route::post('/report/return-company/insert', [PackageReturnCompanyController::class, 'Insert']);
	Route::post('/report/return-company/import', [PackageReturnCompanyController::class, 'Import']);
	Route::get('/report/return-company/export/{dateInit}/{dateEnd}/{idCompany}/{routes}/{states}/{type}', [PackageReturnCompanyController::class, 'Export']);
	Route::get('/report/return-company/update-created-at', [PackageReturnCompanyController::class, 'UpdateCreatedAt']);

	Route::get('/report/mass-query', [PackageMassQueryController::class, 'Index'])->middleware('permission:reportMassquery.index');
	Route::post('/report/mass-query/import', [PackageMassQueryController::class, 'Import']);

	Route::get('/report/all-pending', [ReportController::class, 'IndexAllPending'])->middleware('permission:reportAllPending.index');
	Route::get('/report/all-pending/list/{idCompany}/{dateInit}/{dateEnd}/{states}/{status}', [ReportController::class, 'ListAllPending']);
	Route::get('/report/all-pending/export/{idCompany}/{dateInit}/{dateEnd}/{states}/{status}/{type}', [ReportController::class, 'ExportAllPending']);

    Route::get('/configurations', [ConfigurationController::class, 'index'])->middleware('permission:configuration.index');

    Route::get('/validator/warehouse/getAll', [ValidatorController::class, 'GetAllWarehouse']);

    Route::get('/package-terminal/move-to-warehouse/{PACKAGE_ID}', [PackageTerminalController::class, 'MoveToWarehouse']);

    Route::get('report-invoices', [ReportInvoiceController::class, 'Index'])->middleware('permission:reportInvoices.index');
    Route::get('/report-invoices/list/delivery/{idCompany}/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}', [ReportInvoiceController::class, 'List']);
	Route::get('/report-invoices/export/delivery/{idCompany}/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}/{type}', [ReportInvoiceController::class, 'Export']);
});

//============ Check Stop package
Route::get('/package-check', [PackageCheckController::class, 'Index']);
Route::post('/package-check/import', [PackageCheckController::class, 'Import']);


Route::get('/package-delivery/updatedOnfleet', [PackageDeliveryController::class, 'UpdatedOnfleet']);
Route::get('find-route/{barcCode}', [PackageInboundController::class,'findRoute']);
Route::post('upload-live-routes',[RoutesController::class,'UploadLiveRoutes']);

