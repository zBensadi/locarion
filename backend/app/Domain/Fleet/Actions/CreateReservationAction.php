<?php

namespace App\Domain\Fleet\Actions;

use App\Domain\Fleet\Models\Reservation;
use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Tenancy\Scopes\TenantScope;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class CreateReservationAction
{
    public function __construct(private CheckVehicleAvailabilityAction $checkAvailability) {}

    public function execute(array $data, ?string $forceAgencyId = null): Reservation
    {
        $vehicle = Vehicle::withoutGlobalScope(TenantScope::class)->findOrFail($data['vehicle_id']);

        $agencyId = $forceAgencyId ?? $vehicle->agency_id;

        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end = Carbon::parse($data['end_date'])->startOfDay();

        // Minimum rental duration is 1 day. If start and end are same day, it's 1 day.
        // If end is next day, it's 1 day... Wait, if start is 10th and end is 12th, diffInDays is 2.
        // But usually rental is inclusive or exclusive. Let's say diffInDays + 1 for inclusive days,
        // or just diffInDays if based on 24-hour periods.
        // The business rule says: "minimum rental duration is 1 day. (end_date - start_date in days)".
        // Let's use max(1, $start->diffInDays($end)).
        $days = (int) max(1, $start->diffInDays($end));

        if (! $this->checkAvailability->execute($vehicle->id, $start, $end)) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'The selected vehicle is not available for these dates.',
            ]);
        }

        $dailyRate = $vehicle->daily_rate;
        $totalPrice = $dailyRate * $days;

        return Reservation::create([
            'agency_id' => $agencyId,
            'vehicle_id' => $vehicle->id,
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'customer_phone' => $data['customer_phone'] ?? null,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'daily_rate_snapshot' => $dailyRate,
            'total_price' => $totalPrice,
            'status' => 'pending',
        ]);
    }
}
