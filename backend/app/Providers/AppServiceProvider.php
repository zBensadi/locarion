<?php

namespace App\Providers;

use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Fleet\Policies\VehiclePolicy;
use App\Domain\PlatformAdmin\Models\VehicleCategory;
use App\Domain\PlatformAdmin\Policies\VehicleCategoryPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api-auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        Gate::policy(VehicleCategory::class, VehicleCategoryPolicy::class);
        Gate::policy(Vehicle::class, VehiclePolicy::class);

        Factory::guessFactoryNamesUsing(function (string $modelName) {
            return 'Database\\Factories\\' . class_basename($modelName) . 'Factory';
        });
    }
}
