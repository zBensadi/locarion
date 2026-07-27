<?php

namespace App\Domain\Tenancy\Traits;

use App\Domain\Tenancy\Scopes\TenantScope;
use App\Domain\Tenancy\Services\TenantContext;

trait HasTenancy
{
    /**
     * Retrieve the model for a bound value.
     * Bypasses the TenantScope during Route Model Binding so that Policies can handle 403 vs 404 correctly,
     * and avoids 404s when route binding happens before tenant middleware is applied.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->withoutGlobalScope(TenantScope::class)->where($field ?? $this->getRouteKeyName(), $value)->first();
    }

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
