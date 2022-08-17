<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\{AssignedController, ClientController, CommentsController, CompanyController, DriverController, IndexController, PackageController, PackageCheckController, PackageDeliveryController, PackageDispatchController, PackageInboundController, PackageManifestController, PackageNotExistsController, PackageReturnCompanyController, ReportController, RoleController, RoutesController, StateController, TeamController, UnassignedController, UserController};
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

//============ User Login - Logout
Route::get('/', [UserController::class, 'Login']);
Route::post('/user/login', [UserController::class, 'ValidationLogin']);

Route::group(['middleware' => 'login'], function() {

	Route::get('/home', [IndexController::class, 'Index']);
		
	Route::get('/dashboard', [IndexController::class, 'Dashboard']);
	Route::get('/dashboard/getallquantity', [IndexController::class, 'GetAllQuantity']);

	Route::get('/package-history/search/{PACKAGE_ID}', [PackageController::class, 'Search']);
	Route::get('/package-history/search-task/{TASK}', [PackageController::class, 'SearchTask']);
	Route::post('/package-history/update', [PackageController::class, 'Update']);

	//============ Assigned
	Route::get('assigned', [AssignedController::class, 'Index']);
	Route::get('/assigned/list/{dataView}/{idTeam}', [AssignedController::class, 'List']);
	Route::post('/assigned/insert', [AssignedController::class, 'Insert']);

	//============ Assigned Team
	Route::get('assignedTeam', [AssignedController::class, 'IndexTeam']);
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
	Route::get('/unassignedTeam/', [UnassignedController::class, 'IndexTeam']);
	Route::get('/unassignedTeam/list/{dataView}/{idDriver}', [UnassignedController::class, 'ListUnassignedTeam']);
	Route::post('/unassignedTeam/insert', [UnassignedController::class, 'RemoveDriver']);

	//============ Maintenance package manifest
	Route::get('/package-manifest', [PackageManifestController::class, 'Index']);
	Route::get('/package-manifest/search/{PACKAGE_ID}', [PackageController::class, 'Search']);
	Route::get('/package-manifest/list/{routes}/{states}', [PackageManifestController::class, 'List']);
	Route::post('/package-manifest/insert', [PackageManifestController::class, 'Insert']);
	Route::get('/package-manifest/get/{PACKAGE_ID}', [PackageManifestController::class, 'Get']);
	Route::post('/package-manifest/update', [PackageManifestController::class, 'Update']);
	Route::post('/package-manifest/update/filter', [PackageManifestController::class, 'UpdateFilter']);
	Route::post('/package-manifest/import', [PackageManifestController::class, 'Import']);
	Route::get('/package-manifest/delete-duplicate', [PackageManifestController::class, 'DeleteDuplicate']);

	//============ Validation inbound
	Route::get('/package-inbound', [PackageInboundController::class, 'Index']);
	Route::get('/package-inbound/list/{dataView}/{route}/{state}', [PackageInboundController::class, 'List']);
	Route::post('/package-inbound/insert', [PackageInboundController::class, 'Insert']);
	Route::get('/package-inbound/get/{PACKAGE_ID}', [PackageInboundController::class, 'Get']);
	Route::post('/package-inbound/update', [PackageInboundController::class, 'Update']);
	Route::post('/package-inbound/import', [PackageInboundController::class, 'Import']);
	Route::get('/package-inbound/pdf-label/{Reference}', [PackageInboundController::class, 'PdfLabel']);

	//============ Dispatch package
	Route::get('/package-dispatch', [PackageDispatchController::class, 'Index']);
	Route::get('/package-dispatch/list/{dataView}/{idTeam}/{idDriver}/{states}/{routes}', [PackageDispatchController::class, 'List']);
	Route::get('/package-dispatch/getAll', [PackageDispatchController::class, 'GetAll']);
	Route::post('/package-dispatch/insert', [PackageDispatchController::class, 'Insert']);
	Route::get('/package-dispatch/get/{PACKAGE_ID}', [PackageDispatchController::class, 'Get']);
	Route::post('/package-dispatch/update', [PackageDispatchController::class, 'Update']);
	Route::post('/package-dispatch/change', [PackageDispatchController::class, 'Change']);
	Route::post('/package-dispatch/import', [PackageDispatchController::class, 'Import']);

	

	//============ Validation delivery
	Route::get('/package-delivery', [PackageDeliveryController::class, 'Index']);
	Route::get('/package-delivery/list', [PackageDeliveryController::class, 'List']);
	Route::post('/package-delivery/import', [PackageDeliveryController::class, 'Import']);
	Route::get('/package-delivery/updatedTeamOrDriverFailed', [PackageDeliveryController::class, 'UpdatedTeamOrDriverFailed']);
	Route::get('/package-delivery/updatedDeliverFields', [PackageDeliveryController::class, 'UpdatedDeliverFields']);
	

	//============ Validation package not exists
	Route::get('/package-not-exists', [PackageNotExistsController::class, 'Index']);
	Route::get('/package-not-exists/list', [PackageNotExistsController::class, 'List']);
	Route::get('/package-not-exists/export-excel', [PackageNotExistsController::class, 'ExportExcel']);

	Route::get('/package/return', [PackageController::class, 'IndexReturn']);
	Route::get('/package/list/return/{routes}/{states}', [PackageController::class, 'ListReturn']);
	Route::post('/package/return/dispatch', [PackageDispatchController::class, 'Return']);
	Route::get('/package/download/onfleet/{idTeam}/{idDriver}/{type}/{valuesCheck}/{StateSearch}/{day}/{dateInit}/{dateEnd}', [PackageController::class, 'DownloadOnfleet']);
	Route::get('/package/download/roadwarrior/{idTeam}/{idDriver}/{type}/{valuesCheck}/{StateSearch}/{day}/{dateInit}/{dateEnd}', [PackageController::class, 'DownloadRoadWarrior']);
	Route::get('/package/download/onfleet/{idTeam}/{idDriver}/{type}/{valuesCheck}/{StateSearch}/{dayNight}', [PackageController::class, 'DownloadOnfleet']);
	Route::get('/package/download/roadwarrior/{idTeam}/{idDriver}/{type}/{valuesCheck}/{StateSearch}/{dayNight}', [PackageController::class, 'DownloadRoadWarrior']);
	Route::post('/package/dispatch/import', [PackageController::class, 'ImportDispatch']);

	//============ Maintenance of users
	Route::get('role/list', [RoleController::class, 'List']);

	//============ Maintenance of comments
	Route::get('comments', [CommentsController::class, 'Index']);
	Route::get('comments/list', [CommentsController::class, 'List']);
	Route::post('comments/insert', [CommentsController::class, 'Insert']);
	Route::get('comments/get/{id}', [CommentsController::class, 'Get']);
	Route::post('comments/update/{id}', [CommentsController::class, 'Update']);
	Route::get('comments/delete/{id}', [CommentsController::class, 'Delete']);

	//============ Maintenance of company
	Route::get('company', [CompanyController::class, 'Index']);
	Route::get('company/list', [CompanyController::class, 'List']);
	Route::get('company/getAll', [CompanyController::class, 'GetAll']);
	Route::post('company/insert', [CompanyController::class, 'Insert']);
	Route::get('company/get/{id}', [CompanyController::class, 'Get']);
	Route::post('company/update/{id}', [CompanyController::class, 'Update']);
	Route::get('company/delete/{id}', [CompanyController::class, 'Delete']);

	//============ Maintenance of anti-scan
	Route::get('anti-scan', [StateController::class, 'Index']);
	Route::get('anti-scan/list', [StateController::class, 'List']);
	Route::post('anti-scan/insert', [StateController::class, 'Insert']);
	Route::get('anti-scan/get/{id}', [StateController::class, 'Get']);
	Route::post('anti-scan/update/{id}', [StateController::class, 'Update']);
	Route::get('anti-scan/delete/{id}', [StateController::class, 'Delete']);

	//============ Maintenance of drivers
	Route::get('driver', [DriverController::class, 'Index']);
	Route::get('driver/list', [DriverController::class, 'List']);
	Route::get('driver/team/list/{idTeam}', [DriverController::class, 'ListAllByTeam']);
	Route::post('driver/insert', [DriverController::class, 'Insert']);
	Route::get('driver/get/{id}', [DriverController::class, 'Get']);
	Route::post('driver/update/{id}', [DriverController::class, 'Update']);
	Route::get('driver/delete/{id}', [DriverController::class, 'Delete']);

	//============ Maintenance of teams
	Route::get('routes', [RoutesController::class, 'Index']);
	Route::get('routes/list', [RoutesController::class, 'List']);
	Route::get('routes/getAll', [RoutesController::class, 'GetAll']);
	Route::post('routes/insert', [RoutesController::class, 'Insert']);
	Route::post('routes/import', [RoutesController::class, 'Import']);
	Route::get('routes/get/{id}', [RoutesController::class, 'Get']);
	Route::post('routes/update/{id}', [RoutesController::class, 'Update']);
	Route::get('routes/delete/{id}', [RoutesController::class, 'Delete']);
	Route::get('routes/update/package', [RoutesController::class, 'UpdateRoutePackage']);

	//============ Maintenance of teams
	Route::get('team', [TeamController::class, 'Index']);
	Route::get('team/list', [TeamController::class, 'List']);
	Route::get('team/listall', [TeamController::class, 'ListAll']);
	Route::post('team/insert', [TeamController::class, 'Insert']);
	Route::get('team/get/{id}', [TeamController::class, 'Get']);
	Route::post('team/update/{id}', [TeamController::class, 'Update']);
	Route::get('team/delete/{id}', [TeamController::class, 'Delete']);
	 
	//============ Maintenance of users
	Route::get('user', [UserController::class, 'Index']);
	Route::get('user/list', [UserController::class, 'List']);
	Route::post('user/insert', [UserController::class, 'Insert']);
	Route::get('user/get/{id}', [UserController::class, 'Get']);
	Route::post('user/update/{id}', [UserController::class, 'Update']);
	Route::get('user/delete/{id}', [UserController::class, 'Delete']);
	Route::get('user/changePassword', [UserController::class, 'ChangePassword']);
	Route::post('user/changePassword/save', [UserController::class, 'SaveChangePassword']);

	Route::get('user/logout', [UserController::class, 'Logout']);

	Route::get('/report/manifest', [ReportController::class, 'IndexManifest']);
	Route::get('/report/list/manifest/{dateInit}/{dateEnd}/{routes}/{states}', [ReportController::class, 'ListManifest']);
	Route::get('/report/export/manifest/{dateInit}/{dateEnd}/{routes}/{states}', [ReportController::class, 'ExportManifest']);

	Route::get('/report/inbound', [ReportController::class, 'IndexInbound']);
	Route::get('/report/list/inbound/{dateInit}/{dateEnd}/{routes}/{states}', [ReportController::class, 'ListInbound']);
	Route::get('/report/export/inbound/{dateInit}/{dateEnd}/{routes}/{states}', [ReportController::class, 'ExportInbound']);

	Route::get('/report/delivery', [ReportController::class, 'IndexDelivery']);
	Route::get('/report/list/delivery/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}', [ReportController::class, 'ListDelivery']);
	Route::get('/report/export/delivery/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}', [ReportController::class, 'ExportDelivery']);

	Route::get('/report/dispatch', [ReportController::class, 'IndexDispatch']);
	Route::get('/report/list/dispatch/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}', [ReportController::class, 'ListDispatch']);
	Route::get('/report/export/dispatch/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}', [ReportController::class, 'ExportDispatch']);

	Route::get('/report/failed', [ReportController::class, 'IndexFailed']);
	Route::get('/report/list/failed/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}', [ReportController::class, 'ListFailed']);
	Route::get('/report/export/failed/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}', [ReportController::class, 'ExportFailed']);

	Route::get('/report/notExists', [ReportController::class, 'IndexNotExists']);
	Route::get('/report/list/notexists/{dateInit}/{dateEnd}', [ReportController::class, 'ListNotExists']);
	Route::get('/report/export/notexists/{dateInit}/{dateEnd}', [ReportController::class, 'ExportNotExists']);

	Route::get('/report/assigns', [ReportController::class, 'IndexAssigns']);
	Route::get('/report/list/assigns/{dateInit}/{dateEnd}/{routes}/{states}', [ReportController::class, 'ListAssigns']);
	Route::get('/report/export/assigns/{dateInit}/{dateEnd}/{routes}/{states}', [ReportController::class, 'ExportAssigns']);

	Route::get('/report/return-company', [PackageReturnCompanyController::class, 'Index']);
	Route::get('/report/return-company/list/{dateInit}/{dateEnd}/{routes}/{states}', [PackageReturnCompanyController::class, 'List']);
	Route::post('/report/return-company/insert', [PackageReturnCompanyController::class, 'Insert']);
	Route::get('/report/return-company/export/{dateInit}/{dateEnd}/{routes}/{states}', [PackageReturnCompanyController::class, 'Export']);
});

//============ Check Stop package
Route::get('/package-check', [PackageCheckController::class, 'Index']);
Route::post('/package-check/import', [PackageCheckController::class, 'Import']);

Route::get('/package-delivery/updatedOnfleet', [PackageDeliveryController::class, 'UpdatedOnfleet']);