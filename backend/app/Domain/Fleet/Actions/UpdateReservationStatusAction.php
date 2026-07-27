<?php

namespace App\Domain\Fleet\Actions;

use App\Domain\Fleet\Models\Reservation;
use Illuminate\Validation\ValidationException;

class UpdateReservationStatusAction
{
    /**
     * Update the status of a reservation adhering to strict transition rules.
     */
    public function execute(Reservation $reservation, string $newStatus): Reservation
    {
        $current = $reservation->status;

        if ($current === $newStatus) {
            return $reservation;
        }

        if (in_array($current, ['completed', 'rejected', 'cancelled'])) {
            throw ValidationException::withMessages([
                'status' => "Cannot change status from terminal state: {$current}.",
            ]);
        }

        $validTransitions = [
            'pending' => ['confirmed', 'rejected', 'cancelled'],
            'confirmed' => ['completed', 'cancelled'],
        ];

        if (! in_array($newStatus, $validTransitions[$current] ?? [])) {
            throw ValidationException::withMessages([
                'status' => "Invalid transition from {$current} to {$newStatus}.",
            ]);
        }

        $reservation->update(['status' => $newStatus]);

        return $reservation;
    }
}
