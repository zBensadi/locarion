<?php

use App\Domain\Identity\Controllers\LoginController;
use App\Domain\Identity\Controllers\LogoutController;
use App\Domain\Identity\Controllers\MeController;
use App\Http\Controllers\Api\V1\Admin\VehicleCategoryController as AdminCategoryController;
use App\Http\Controllers\Api\V1\BackOffice\ReservationController as BackOfficeReservationController;
use App\Http\Controllers\Api\V1\BackOffice\VehicleCategoryController as BackOfficeCategoryController;
use App\Http\Controllers\Api\V1\BackOffice\VehicleController as BackOfficeVehicleController;
use App\Http\Controllers\Api\V1\Public\ReservationController as PublicReservationController;
use App\Http\Controllers\Api\V1\Public\VehicleController as PublicVehicleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Auth routes
    Route::post('/login', LoginController::class)->middleware('throttle:api-auth'); // We'll just use a basic throttle for now or the login specific one

    // Public routes
    Route::prefix('public')->group(function () {
        Route::get('/vehicles', [PublicVehicleController::class, 'index']);
        Route::get('/vehicles/{id}', [PublicVehicleController::class, 'show']);
        Route::post('/reservations', [PublicReservationController::class, 'store']);
    });

    // Authenticated routes
    Route::middleware(['auth:sanctum', 'user.active', 'tenant.team'])->group(function () {
        Route::post('/logout', LogoutController::class);
        Route::get('/me', MeController::class);

        // Admin (Platform level)
        Route::prefix('admin')->group(function () {
            Route::apiResource('categories', AdminCategoryController::class);
        });

        // Agency active check should be applied to routes that are not platform level
        Route::middleware(['agency.active'])->group(function () {
            Route::get('/categories', [BackOfficeCategoryController::class, 'index']);
            Route::apiResource('vehicles', BackOfficeVehicleController::class);

            Route::apiResource('reservations', BackOfficeReservationController::class)->except(['update']);
            Route::put('reservations/{reservation}/status', [BackOfficeReservationController::class, 'updateStatus']);
        });
    });
});
