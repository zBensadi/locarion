<?php

namespace App\Domain\Fleet\Actions;

use App\Domain\Fleet\Models\Reservation;
use App\Domain\Tenancy\Scopes\TenantScope;
use Carbon\Carbon;

class CheckVehicleAvailabilityAction
{
    /**
     * Check if a vehicle is available for the given date range.
     */
    public function execute(string $vehicleId, string|Carbon $startDate, string|Carbon $endDate, ?string $excludeReservationId = null): bool
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        $query = Reservation::withoutGlobalScope(TenantScope::class)
            ->where('vehicle_id', $vehicleId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function ($q) use ($start, $end) {
                // Check for overlapping date ranges
                // Overlap exists if existing.start <= new.end AND existing.end >= new.start
                $q->where('start_date', '<=', $end->format('Y-m-d'))
                    ->where('end_date', '>=', $start->format('Y-m-d'));
            });

        if ($excludeReservationId) {
            $query->where('id', '!=', $excludeReservationId);
        }

        return ! $query->exists();
    }
}
