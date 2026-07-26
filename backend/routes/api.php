<?php

use App\Domain\Identity\Controllers\LoginController;
use App\Domain\Identity\Controllers\LogoutController;
use App\Domain\Identity\Controllers\MeController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Auth routes
    Route::post('/login', LoginController::class)->middleware('throttle:api-auth'); // We'll just use a basic throttle for now or the login specific one

    // Authenticated routes
    Route::middleware(['auth:sanctum', 'user.active', 'tenant.team'])->group(function () {
        Route::post('/logout', LogoutController::class);
        Route::get('/me', MeController::class);

        // Agency active check should be applied to routes that are not platform level
        Route::middleware(['agency.active'])->group(function () {
            // Tenant-scoped routes will go here in future
        });
    });
});
