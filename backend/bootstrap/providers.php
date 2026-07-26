<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    App\Domain\Tenancy\Providers\TenantServiceProvider::class,
];
