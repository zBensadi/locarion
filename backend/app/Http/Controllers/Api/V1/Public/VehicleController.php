<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Domain\Fleet\Actions\SearchVehiclesAction;
use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Fleet\Resources\VehicleResource;
use App\Domain\Tenancy\Scopes\TenantScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VehicleController extends Controller
{
    public function index(Request $request, SearchVehiclesAction $action): AnonymousResourceCollection
    {
        $filters = $request->only(['category_id', 'make', 'agency_id']);

        $vehicles = $action->execute($filters);

        return VehicleResource::collection($vehicles);
    }

    public function show(string $id): VehicleResource
    {
        $vehicle = Vehicle::withoutGlobalScope(TenantScope::class)
            ->with(['category', 'agency'])
            ->whereHas('agency', function ($query) {
                $query->where('is_active', true);
            })
            ->where('status', 'available')
            ->findOrFail($id);

        return new VehicleResource($vehicle);
    }
}
