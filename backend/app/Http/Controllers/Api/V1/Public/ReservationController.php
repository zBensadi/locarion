<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Domain\Fleet\Actions\CreateReservationAction;
use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Fleet\Requests\ReservationRequest;
use App\Domain\Fleet\Resources\ReservationResource;
use App\Domain\Tenancy\Scopes\TenantScope;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    /**
     * Submit a public reservation request.
     */
    public function store(ReservationRequest $request, CreateReservationAction $action): ReservationResource
    {
        // Public API is not scoped to a tenant initially. We must extract the tenant from the requested vehicle.
        // Also, the vehicle must belong to an active agency.
        $vehicle = Vehicle::withoutGlobalScope(TenantScope::class)
            ->whereHas('agency', function ($q) {
                $q->where('status', 'active');
            })
            ->where('status', 'available')
            ->find($request->validated('vehicle_id'));

        if (! $vehicle) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'The selected vehicle is not available for reservation.',
            ]);
        }

        $reservation = $action->execute($request->validated(), $vehicle->agency_id);

        return new ReservationResource($reservation->load('vehicle'));
    }
}
