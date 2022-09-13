<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\{ PackageController, WHookController };
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

Route::get('packages-manifest', [PackageController::class, 'UpdateManifestRouteByZipCode']);
Route::get('packages-inbound', [PackageController::class, 'UpdateInboundRouteByZipCode']);