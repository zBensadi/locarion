<?php

namespace App\Domain\Fleet\Actions;

use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Tenancy\Scopes\TenantScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SearchVehiclesAction
{
    public function execute(array $filters): LengthAwarePaginator
    {
        return Vehicle::withoutGlobalScope(TenantScope::class)
            ->with(['category', 'agency'])
            ->whereHas('agency', function ($query) {
                $query->where('status', 'active');
            })
            ->where('status', 'available')
            ->when($filters['category_id'] ?? null, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($filters['make'] ?? null, function ($query, $make) {
                $query->where('make', 'ilike', '%' . $make . '%');
            })
            ->when($filters['agency_id'] ?? null, function ($query, $agencyId) {
                $query->where('agency_id', $agencyId);
            })
            ->latest()
            ->paginate(50);
    }
}
