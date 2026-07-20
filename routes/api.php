<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PortController;
use App\Http\Controllers\RiskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Global Supply Chain Intelligence API Routes
|--------------------------------------------------------------------------
|
| Base URL:
|
| /api/v1
|
*/

Route::prefix('v1')
    ->name('api.v1.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Countries
        |--------------------------------------------------------------------------
        */

        Route::prefix('countries')
            ->name('countries.')
            ->group(function () {

                Route::get(
                    '/',
                    [
                        CountryController::class,
                        'index',
                    ]
                )->name('index');

                Route::get(
                    '/{country}',
                    [
                        CountryController::class,
                        'show',
                    ]
                )->name('show');

                Route::post(
                    '/{country}/sync',
                    [
                        CountryController::class,
                        'sync',
                    ]
                )->name('sync');
            });

        /*
        |--------------------------------------------------------------------------
        | Ports
        |--------------------------------------------------------------------------
        |
        | Route statis diletakkan sebelum /{port}.
        |
        */

        Route::prefix('ports')
            ->name('ports.')
            ->group(function () {

                Route::get(
                    '/',
                    [
                        PortController::class,
                        'index',
                    ]
                )->name('index');

                Route::get(
                    '/geojson',
                    [
                        PortController::class,
                        'geoJson',
                    ]
                )->name('geojson');

                Route::get(
                    '/statistics',
                    [
                        PortController::class,
                        'statistics',
                    ]
                )->name('statistics');

                Route::get(
                    '/{port}',
                    [
                        PortController::class,
                        'show',
                    ]
                )->name('show');
            });

        /*
        |--------------------------------------------------------------------------
        | Risk Scores
        |--------------------------------------------------------------------------
        |
        | Route /latest diletakkan sebelum route dinamis.
        |
        */

        Route::prefix('risk')
            ->name('risk.')
            ->group(function () {

                Route::get(
                    '/',
                    [
                        RiskController::class,
                        'index',
                    ]
                )->name('index');

                Route::get(
                    '/latest',
                    [
                        RiskController::class,
                        'latest',
                    ]
                )->name('latest');

                Route::post(
                    '/{country}/calculate',
                    [
                        RiskController::class,
                        'calculate',
                    ]
                )->name('calculate');
            });

        /*
        |--------------------------------------------------------------------------
        | News Intelligence
        |--------------------------------------------------------------------------
        */

        Route::prefix('news')
            ->name('news.')
            ->group(function () {

                Route::get(
                    '/',
                    [
                        NewsController::class,
                        'index',
                    ]
                )->name('index');

                Route::get(
                    '/{news}',
                    [
                        NewsController::class,
                        'show',
                    ]
                )->name('show');

                Route::post(
                    '/{country}/sync',
                    [
                        NewsController::class,
                        'sync',
                    ]
                )->name('sync');
            });

        /*
        |--------------------------------------------------------------------------
        | Currency Exchange Rates
        |--------------------------------------------------------------------------
        |
        | Route /latest diletakkan sebelum route dinamis.
        |
        */

        Route::prefix('currency')
            ->name('currency.')
            ->group(function () {

                Route::get(
                    '/',
                    [
                        CurrencyController::class,
                        'index',
                    ]
                )->name('index');

                Route::get(
                    '/latest',
                    [
                        CurrencyController::class,
                        'latest',
                    ]
                )->name('latest');

                Route::post(
                    '/{country}/sync',
                    [
                        CurrencyController::class,
                        'sync',
                    ]
                )->name('sync');
            });

        /*
        |--------------------------------------------------------------------------
        | Admin
        |--------------------------------------------------------------------------
        */

        Route::prefix('admin')
            ->name('admin.')
            ->group(function () {

                Route::get(
                    '/statistics',
                    [
                        AdminController::class,
                        'statistics',
                    ]
                )->name('statistics');

                Route::get(
                    '/api-logs',
                    [
                        AdminController::class,
                        'apiLogs',
                    ]
                )->name('api-logs');
            });
    });