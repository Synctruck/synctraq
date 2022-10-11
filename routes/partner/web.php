<?php

use App\Http\Controllers\Partner\CompanyController;
use App\Http\Controllers\Partner\HomeController;
use App\Http\Controllers\Partner\ReportController;
use App\Http\Controllers\Partner\PackageReturnCompanyController;
use Illuminate\Support\Facades\Route;



Route::prefix('partners')->group(function () {


    Route::get('/login', [CompanyController::class, 'Login']);

    Route::post('/login', [CompanyController::class, 'ValidationLogin']);
    Route::get('/logout', [CompanyController::class, 'Logout']);


    Route::group(['middleware' => 'authPartner'], function() {
        Route::get('/', [HomeController::class, 'index']);

        Route::get('/reports', [ReportController::class, 'Index']);

        Route::get('/report/manifest', [ReportController::class, 'IndexManifest']);
        Route::get('/report/list/manifest/{dateInit}/{dateEnd}/{routes}/{states}', [ReportController::class, 'ListManifest']);
        Route::get('/report/export/manifest/{dateInit}/{dateEnd}/{routes}/{states}', [ReportController::class, 'ExportManifest']);

        Route::get('/report/inbound', [ReportController::class, 'IndexInbound']);
        Route::get('/report/list/inbound/{dateInit}/{dateEnd}/{routes}/{states}/{truck}', [ReportController::class, 'ListInbound']);
        Route::get('/report/export/inbound/{dateInit}/{dateEnd}/{routes}/{states}/{truck}', [ReportController::class, 'ExportInbound']);

        Route::get('/report/delivery', [ReportController::class, 'IndexDelivery']);
        Route::get('/report/list/delivery/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}', [ReportController::class, 'ListDelivery']);
        Route::get('/report/export/delivery/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}', [ReportController::class, 'ExportDelivery']);

        Route::get('/report/dispatch', [ReportController::class, 'IndexDispatch']);
        Route::get('/report/list/dispatch/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}', [ReportController::class, 'ListDispatch']);
        Route::get('/report/export/dispatch/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}', [ReportController::class, 'ExportDispatch']);

        Route::get('/report/failed', [ReportController::class, 'IndexFailed']);
        Route::get('/report/list/failed/{idCompany}/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}', [ReportController::class, 'ListFailed']);
        Route::get('/report/export/failed/{idCompany}/{dateInit}/{dateEnd}/{idTeam}/{idDriver}/{routes}/{states}', [ReportController::class, 'ExportFailed']);

        Route::get('/report/notExists', [ReportController::class, 'IndexNotExists']);
        Route::get('/report/list/notexists/{dateInit}/{dateEnd}', [ReportController::class, 'ListNotExists']);
        Route::get('/report/export/notexists/{dateInit}/{dateEnd}', [ReportController::class, 'ExportNotExists']);

        Route::get('/report/assigns', [ReportController::class, 'IndexAssigns']);
        Route::get('/report/list/assigns/{dateInit}/{dateEnd}/{routes}/{states}', [ReportController::class, 'ListAssigns']);
        Route::get('/report/export/assigns/{dateInit}/{dateEnd}/{routes}/{states}', [ReportController::class, 'ExportAssigns']);

        Route::get('/report/return-company', [PackageReturnCompanyController::class, 'Index']);
        Route::get('/report/return-company/list/{dateInit}/{dateEnd}/{routes}/{states}', [PackageReturnCompanyController::class, 'List']);
        // Route::post('/report/return-company/insert', [PackageReturnCompanyController::class, 'Insert']);
        Route::get('/report/return-company/export/{dateInit}/{dateEnd}/{routes}/{states}', [PackageReturnCompanyController::class, 'Export']);
    });
});
