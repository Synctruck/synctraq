<?php

use App\Http\Controllers\Landing\CompanyController;
use App\Http\Controllers\Landing\HomeController;
use Illuminate\Support\Facades\Route;



Route::prefix('partners')->group(function () {


    Route::get('/login', [CompanyController::class, 'Login']);

    Route::post('/login', [CompanyController::class, 'ValidationLogin']);
    Route::get('/logout', [CompanyController::class, 'Logout']);


    Route::group(['middleware' => 'authPartner'], function() {
        Route::get('/', [HomeController::class, 'index']);
    });
});
