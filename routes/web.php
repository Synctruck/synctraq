<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\{AssignedController, ClientController, CommentsController, CompanyController, ConfigurationController, ChargeCompanyController, DriverController, IndexController, OrderController, PackageAgeController, PackageBlockedController, PackageController, PackageCheckController, PackageDeliveryController, PackageDispatchController, PackageFailedController, PackageHighPriorityController, PackageInboundController, PalletDispatchController, PackageManifestController, PackageNotExistsController, PackagePreDispatchController, PackageWarehouseController,  PackageReturnCompanyController, PaymentDeliveryTeamController, RangePriceCompanyController, RangePriceTeamRouteCompanyController, ReportController, RoleController, RoutesController, StateController, StoreController, TeamController, Trackcontroller, UnassignedController, UserController, ViewerController,ValidatorController};
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

//============ User Login - Logout
Route::get('/', [UserController::class, 'Login']);
Route::post('/user/login', [UserController::class, 'ValidationLogin']);

Route::get('routes/getAll', [RoutesController::class, 'GetAll']);

Route::get('/package-history/search/{PACKAGE_ID}', [PackageController::class, 'Search']);
Route::get('/package-history/search-task/{TASK}', [PackageController::class, 'SearchTask']);
Route::post('/package-history/search-by-filters', [PackageController::class, 'SearchByFilters']);

Route::get('/package/all-delete/clear-package', [PackageController::class, 'DeleteClearPackage']);
Route::get('/package/all-change-to-delivery', [PackageController::class, 'ChangePackageToDispatch']);

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
	Route::get('/dashboard/getDataPerDate/{startDate}', [IndexController::class, 'GetDataPerDate']);

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
	Route::get('/package-manifest/list/{idCompany}/{routes}/{states}', [PackageManifestController::class, 'List']);
	Route::post('/package-manifest/insert', [PackageManifestController::class, 'Insert']);
	Route::get('/package-manifest/get/{PACKAGE_ID}', [PackageManifestController::class, 'Get']);
	Route::post('/package-manifest/update', [PackageManifestController::class, 'Update']);
	Route::post('/package-manifest/filter-check', [PackageManifestController::class, 'CheckFilter']);
	Route::post('/package-manifest/update/filter', [PackageManifestController::class, 'UpdateFilter']);
	Route::post('/package-manifest/import', [PackageManifestController::class, 'Import']);
	Route::get('/package-manifest/delete-duplicate', [PackageManifestController::class, 'DeleteDuplicate']);

	//============ Validation inbound
	Route::get('/package-inbound', [PackageInboundController::class, 'Index'])->middleware('permission:inbound.index');
	Route::get('/package-inbound/list/{idCompany}/{dateStart}/{dateEnd}/{route}/{state}', [PackageInboundController::class, 'List']);
	Route::get('/package-inbound/export/{idCompany}/{dateStart}/{dateEnd}/{route}/{state}', [PackageInboundController::class, 'Export']);
	Route::post('/package-inbound/insert', [PackageInboundController::class, 'Insert']);
	Route::get('/package-inbound/get/{PACKAGE_ID}', [PackageInboundController::class, 'Get']);
	Route::post('/package-inbound/update', [PackageInboundController::class, 'Update']);
	Route::post('/package-inbound/import', [PackageInboundController::class, 'Import']);
	Route::get('/package-inbound/pdf-label/{Reference}', [PackageInboundController::class, 'PdfLabel']);

	//============ Dispatch package
	Route::get('/package-dispatch', [PackageDispatchController::class, 'Index'])->middleware('permission:dispatch.index');
	Route::get('/package-dispatch/list/{idCompany}/{dateStart}/{dateEnd}/{idTeam}/{idDriver}/{states}/{routes}', [PackageDispatchController::class, 'List']);
	Route::get('/package-dispatch/export/{idCompany}/{dateStart}/{dateEnd}/{idTeam}/{idDriver}/{states}/{routes}', [PackageDispatchController::class, 'Export']);
	Route::get('/package-dispatch/getAll', [PackageDispatchController::class, 'GetAll']);
	Route::post('/package-dispatch/insert', [PackageDispatchController::class, 'Insert']);
	Route::get('/package-dispatch/get/{PACKAGE_ID}', [PackageDispatchController::class, 'Get']);
	Route::post('/package-dispatch/update', [PackageDispatchController::class, 'Update']);
	Route::post('/package-dispatch/change', [PackageDispatchController::class, 'Change']);
	Route::post('/package-dispatch/import', [PackageDispatchController::class, 'Import']);
	Route::get('/package-dispatch/getCoordinates/{taskOnfleet}', [PackageDispatchController::class, 'GetOnfleetShorId']);
	Route::get('/package-dispatch/update/prices-teams/{startDate}/{endDate}', [PackageDispatchController::class, 'UpdatePriceTeams']);

	//============ PALET DISPACTH 
	Route::get('/pallet-dispatch/list/{dateStart}/{dateEnd}/', [PalletDispatchController::class, 'List']);
	Route::post('/pallet-dispatch/insert', [PalletDispatchController::class, 'Insert']);
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
	Route::get('/package-failed/list/{dateStart}/{dateEnd}/{idTeam}/{idDriver}/{states}/{routes}', [PackageFailedController::class, 'List']);
	Route::get('/package-failed/move/prefailed-to-failed', [PackageFailedController::class, 'MovePreFailedToFailed']);
	Route::get('/package-failed/export/{dateStart}/{dateEnd}/{idTeam}/{idDriver}/{states}/{routes}', [PackageFailedController::class, 'Export']);

	//============ Validation delivery
	Route::get('/package-delivery', [PackageDeliveryController::class, 'Index'])->middleware('permission:delivery.index');
	Route::get('/package-delivery/list', [PackageDeliveryController::class, 'List']);
	Route::post('/package-delivery/import', [PackageDeliveryController::class, 'Import']);
	Route::get('/package-delivery/updatedTeamOrDriverFailed', [PackageDeliveryController::class, 'UpdatedTeamOrDriverFailed']);
	Route::get('/package-delivery/updatedDeliverFields', [PackageDeliveryController::class, 'UpdatedDeliverFields']);
	Route::get('/package-delivery/updatedCreatedDate', [PackageDeliveryController::class, 'UpdatedCreatedDate']);
	Route::get('/package-delivery/check', [PackageDeliveryController::class, 'IndexForCheck'])->middleware('permission:checkDelivery.index');
	Route::get('/package-delivery/list-for-check/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}', [PackageDeliveryController::class, 'ListForCheck']);
	Route::post('/package-delivery/insert-for-check', [PackageDeliveryController::class, 'InsertForCheck']);
	Route::get('/package-delivery/confirmation-check', [PackageDeliveryController::class, 'ConfirmationCheck']);
	Route::get('/package-delivery/finance', [PackageDeliveryController::class, 'IndexFinance'])->middleware('permission:validatedDelivery.index');
	Route::get('/package-delivery/list-finance/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{checked}/{routes}/{states}', [PackageDeliveryController::class, 'ListFinance']);

	//=========== Charge Company
	Route::get('/charge-delivery-company', [ChargeCompanyController::class, 'Index'])->middleware('permission:chargeDeliveryCompany.index');;
	Route::get('/charge-delivery-company/list/{idCompany}/{dateInit}/{dateEnd}', [ChargeCompanyController::class, 'List']);
	Route::post('/charge-delivery-company/insert', [ChargeCompanyController::class, 'Insert']);
	Route::get('/charge-delivery-company/export/{dateInit}/{dateEnd}/{idCompany}', [ChargeCompanyController::class, 'Export']);
	Route::get('/charge-company', [ChargeCompanyController::class, 'IndexCharge']);
	Route::get('/charge-company/list/{dateInit}/{endDate}/{idCompany}', [ChargeCompanyController::class, 'ChargeList']);
	Route::get('/charge-company/export/{id}', [ChargeCompanyController::class, 'ExportCharge']);

	//=========== PAYMENT TEAM
	Route::get('/payment-delivery-team', [PaymentDeliveryTeamController::class, 'Index']);
	Route::get('/payment-delivery/list/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}', [PaymentDeliveryTeamController::class, 'List']);
	Route::post('/payment-delivery/insert', [PaymentDeliveryTeamController::class, 'Insert']);
	Route::get('/payment-delivery/export/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}', [PaymentDeliveryTeamController::class, 'Export']);
	Route::get('/payment-team', [PaymentDeliveryTeamController::class, 'IndexPayment'])->middleware('permission:chargeCompany.index');
	Route::get('/payment-team/list/{dateInit}/{dateEnd}/{idTeam}', [PaymentDeliveryTeamController::class, 'PaymentList']);
	Route::get('/payment-team/export/{id}', [PaymentDeliveryTeamController::class, 'ExportPayment']);


	//=========== Age of Package
	Route::get('/package-age', [PackageAgeController::class, 'Index']);
	Route::get('/package-age/list/{idCompany}/{routes}/{states}', [PackageAgeController::class, 'List']);
	Route::get('/package-age/export/{idCompany}/{routes}/{states}', [PackageAgeController::class, 'Export']);

	Route::get('/package-high-priority', [PackageHighPriorityController::class, 'Index'])->middleware('permission:highPriority.index');
	Route::get('/package-high-priority/list/{idCompany}/{routes}/{states}', [PackageHighPriorityController::class, 'List']);
	Route::get('/package-high-priority/export/{idCompany}/{routes}/{states}', [PackageHighPriorityController::class, 'Export']);

	//============ Validation package not exists
	Route::get('/package-not-exists', [PackageNotExistsController::class, 'Index']);
	Route::get('/package-not-exists/list', [PackageNotExistsController::class, 'List']);
	Route::get('/package-not-exists/export-excel', [PackageNotExistsController::class, 'ExportExcel']);

	Route::get('/package/return', [PackageController::class, 'IndexReturn'])->middleware('permission:reinbound.index');
	Route::get('/package/list/return/{idCompany}/{dateStart}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}', [PackageController::class, 'ListReturn']);
	Route::get('/package/list/return/export/{idCompany}/{dateStart}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}', [PackageController::class, 'ListReturnExport']);
	Route::post('/package/return/dispatch', [PackageDispatchController::class, 'Return']);
	Route::get('/package/download/roadwarrior/{idCompany}/{idTeam}/{idDriver}/{StateSearch}/{RouteSearch}/{initDate}/{endDate}', [PackageController::class, 'DownloadRoadWarrior']);

	Route::post('/package/dispatch/import', [PackageController::class, 'ImportDispatch']);

	//============ Validation warehouse
	Route::get('/package-warehouse', [PackageWarehouseController::class, 'Index'])->middleware('permission:warehouse.index');
	Route::get('/package-warehouse/list/{idCompany}/{idValidator}/{dateStart}/{dateEnd}/{route}/{state}', [PackageWarehouseController::class, 'List']);
	Route::post('/package-warehouse/insert', [PackageWarehouseController::class, 'Insert']);
	Route::get('/package-warehouse/export/{idCompany}/{idValidator}/{dateStart}/{dateEnd}/{route}/{state}', [PackageWarehouseController::class, 'Export']);

	//============ Maintenance of users
	Route::get('role/list', [RoleController::class, 'List']);

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
	Route::get('comments/getAll/{finalStatus}', [CommentsController::class, 'GetAllFinalStatus']);
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
	Route::get('driver/team/list/{idTeam}', [DriverController::class, 'ListAllByTeam']);
	Route::post('driver/insert', [DriverController::class, 'Insert']);
	Route::get('driver/get/{id}', [DriverController::class, 'Get']);
	Route::post('driver/update/{id}', [DriverController::class, 'Update']);
	Route::get('driver/changeStatus/{id}', [DriverController::class, 'ChangeStatus']);
	Route::get('driver/delete/{id}', [DriverController::class, 'Delete']);

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

	//============ Maintenance of ranges teams
	Route::get('range-price-team-route-company/list/{idTeam}/{idCompany}/{Route}', [RangePriceTeamRouteCompanyController::class, 'List']);
	Route::post('range-price-team-route-company/insert', [RangePriceTeamRouteCompanyController::class, 'Insert']);
	Route::get('range-price-team-route-company/get/{id}', [RangePriceTeamRouteCompanyController::class, 'Get']);
	Route::post('range-price-team-route-company/update/{id}', [RangePriceTeamRouteCompanyController::class, 'Update']);
	Route::get('range-price-team-route-company/delete/{id}', [RangePriceTeamRouteCompanyController::class, 'Delete']);
	Route::post('range-price-team-route-company/import', [RangePriceTeamRouteCompanyController::class, 'Import']);

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
	Route::post('routes/insert', [RoutesController::class, 'Insert']);
	Route::post('routes/import', [RoutesController::class, 'Import']);
	Route::get('routes/get/{id}', [RoutesController::class, 'Get']);
	Route::post('routes/update/{id}', [RoutesController::class, 'Update']);
	Route::get('routes/delete/{id}', [RoutesController::class, 'Delete']);
	Route::get('routes/update/package/manifest/inbound/warehouse', [RoutesController::class, 'UpdateRoutePackageManifestInboundWarehouse']);
	Route::get('routes/update/package', [RoutesController::class, 'UpdateRoutePackage']);

	//============ Maintenance of teams
	Route::get('team', [TeamController::class, 'Index'])->middleware('permission:team.index');
	Route::get('team/list', [TeamController::class, 'List']);
	Route::get('team/listall', [TeamController::class, 'ListAll']);
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
	Route::get('profile', [UserController::class, 'Profile']);
	Route::post('profile', [UserController::class, 'UpdateProfile']);
	Route::get('getProfile', [UserController::class, 'getProfile']);



	Route::get('user/logout', [UserController::class, 'Logout']);

	Route::get('/reports', [ReportController::class, 'Index']);
	Route::get('/reports/general', [ReportController::class, 'general'])->middleware('permission:report.index');

	Route::get('/report/manifest', [ReportController::class, 'IndexManifest'])->middleware('permission:reportManifest.index');
	Route::get('/report/list/manifest/{idCompany}/{dateInit}/{dateEnd}/{routes}/{states}', [ReportController::class, 'ListManifest']);
	Route::get('/report/export/manifest/{idCompany}/{dateInit}/{dateEnd}/{routes}/{states}', [ReportController::class, 'ExportManifest']);

	Route::get('/report/inbound', [ReportController::class, 'IndexInbound'])->middleware('permission:reportInbound.index');
	Route::get('/report/list/inbound/{idCompany}/{dateInit}/{dateEnd}/{routes}/{states}/{truck}', [ReportController::class, 'ListInbound']);
	Route::get('/report/export/inbound/{idCompany}/{dateInit}/{dateEnd}/{routes}/{states}/{truck}', [ReportController::class, 'ExportInbound']);

	Route::get('/report/delivery', [ReportController::class, 'IndexDelivery'])->middleware('permission:reportDelivery.index');
	Route::get('/report/list/delivery/{idCompany}/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}', [ReportController::class, 'ListDelivery']);
	Route::get('/report/export/delivery/{idCompany}/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}', [ReportController::class, 'ExportDelivery']);

	Route::get('/report/dispatch', [ReportController::class, 'IndexDispatch'])->middleware('permission:reportDispatch.index');
	Route::get('/report/list/dispatch/{idCompany}/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}', [ReportController::class, 'ListDispatch']);
	Route::get('/report/export/dispatch/{idCompany}/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}', [ReportController::class, 'ExportDispatch']);

	Route::get('/report/failed', [ReportController::class, 'IndexFailed'])->middleware('permission:reportFailed.index');
	Route::get('/report/list/failed/{idCompany}/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}', [ReportController::class, 'ListFailed']);
	Route::get('/report/export/failed/{idCompany}/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}', [ReportController::class, 'ExportFailed']);

	Route::get('/report/notExists', [ReportController::class, 'IndexNotExists'])->middleware('permission:reportNotexists.index');
	Route::get('/report/list/notexists/{dateInit}/{dateEnd}', [ReportController::class, 'ListNotExists']);
	Route::get('/report/export/notexists/{dateInit}/{dateEnd}', [ReportController::class, 'ExportNotExists']);

	Route::get('/report/assigns', [ReportController::class, 'IndexAssigns']);
	Route::get('/report/list/assigns/{dateInit}/{dateEnd}/{routes}/{states}', [ReportController::class, 'ListAssigns']);
	Route::get('/report/export/assigns/{dateInit}/{dateEnd}/{routes}/{states}', [ReportController::class, 'ExportAssigns']);

	Route::get('/report/return-company', [PackageReturnCompanyController::class, 'Index'])->middleware('permission:reportReturncompany.index');
	Route::get('/report/return-company/list/{dateInit}/{dateEnd}/{routes}/{states}', [PackageReturnCompanyController::class, 'List']);
	Route::post('/report/return-company/insert', [PackageReturnCompanyController::class, 'Insert']);
	Route::get('/report/return-company/export/{dateInit}/{dateEnd}/{routes}/{states}', [PackageReturnCompanyController::class, 'Export']);
	Route::get('/report/return-company/update-created-at', [PackageReturnCompanyController::class, 'UpdateCreatedAt']);

    Route::get('/configurations', [ConfigurationController::class, 'index'])->middleware('permission:configuration.index');

    Route::get('/validator/warehouse/getAll', [ValidatorController::class, 'GetAllWarehouse']);
});

//============ Check Stop package
Route::get('/package-check', [PackageCheckController::class, 'Index']);
Route::post('/package-check/import', [PackageCheckController::class, 'Import']);

Route::get('/package-delivery/updatedOnfleet', [PackageDeliveryController::class, 'UpdatedOnfleet']);