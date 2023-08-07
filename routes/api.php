<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\{ PackageController, PackageInboundController, PackageDispatchController, WHookController };
use App\Http\Controllers\{ IndexController };
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('packages', [PackageController::class, 'Index']);
Route::get('packages/{reference}', [PackageController::class, 'Get']);
Route::post('packages', [PackageController::class, 'Insert']);

Route::get('packages-webhook-taskCompleted', [WHookController::class, 'EndPointTaskCompleted']);
Route::post('packages-webhook-taskCompleted', [WHookController::class, 'TaskCompleted']);

Route::get('packages-webhook-taskFailed', [WHookController::class, 'EndPointTaskFailed']);
Route::post('packages-webhook-taskFailed', [WHookController::class, 'TaskFailed']);

Route::get('packages-webhook-taskCreated', [WHookController::class, 'EndPointTaskCreated']);
Route::post('packages-webhook-taskCreated', [WHookController::class, 'TaskCreated']);

Route::get('packages-webhook-taskDelete', [WHookController::class, 'EndPointTaskDelete']);
Route::post('packages-webhook-taskDelete', [WHookController::class, 'TaskDelete']);

Route::get('xceleator/task-response', [WHookController::class, 'EndPointTaskXcelerator']);
Route::post('xceleator/task-response', [WHookController::class, 'TaskXcelerator']);

Route::get('packages-manifest/updated-route', [PackageController::class, 'UpdateManifestRouteByZipCode']);
Route::get('packages-inbound/updated-route', [PackageController::class, 'UpdateInboundRouteByZipCode']);
Route::get('packages-warehouse/updated-route', [PackageController::class, 'UpdateWarehouseRouteByZipCode']);

Route::post('package-inbound/insert', [PackageInboundController::class, 'Insert']);
Route::post('package/shipments/inland/{keyApi}', [PackageInboundController::class, 'ShipmentInland']);

Route::get('package-dispatch/packages-by-driver-inland/{apiKey}/{idDriver}', [PackageDispatchController::class, 'ListByDriverInland']);
Route::post('package-dispatch/update-status/{apiKey}', [PackageDispatchController::class, 'UpdateStatus']);
Route::get('package-dispatch/get-package/{apiKey}/{Reference_Number_1}', [PackageDispatchController::class, 'GetPackage']);