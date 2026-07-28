<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        $middleware->alias([
            'tenant.team' => App\Domain\Tenancy\Middleware\SetPermissionsTeamId::class,
            'user.active' => App\Domain\Identity\Middleware\EnsureUserIsActive::class,
            'agency.active' => App\Domain\Tenancy\Middleware\EnsureAgencyIsActive::class,
        ]);
    })->withExceptions(function (Exceptions $exceptions): void {})->create();
