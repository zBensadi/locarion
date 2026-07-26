<?php

namespace App\Domain\Tenancy\Traits;

use App\Domain\Tenancy\Scopes\TenantScope;
use App\Domain\Tenancy\Services\TenantContext;

trait HasTenancy
{
    /**
     * Boot the trait, applying the TenantScope.
     */
    protected static function bootHasTenancy(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            // Automatically assign the agency_id on creation if it's not set
            if (! $model->agency_id) {
                $model->agency_id = app(TenantContext::class)->getAgencyId();
            }
        });
    }
}
