<?php

namespace App\Domain\Tenancy\Scopes;

use App\Domain\Tenancy\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tenantContext = app(TenantContext::class);
        $agencyId = $tenantContext->getAgencyId();

        // If the context is empty (e.g. CLI without specific configuration, or Super Admin without impersonation),
        // we enforce a condition that will match nothing for tenant-scoped models.
        // This ensures a Super Admin doesn't accidentally query all agencies' records when they shouldn't.
        if (! $agencyId) {
            $builder->whereRaw('1 = 0');
            return;
        }

        $builder->where($model->getTable() . '.agency_id', $agencyId);
    }
}
