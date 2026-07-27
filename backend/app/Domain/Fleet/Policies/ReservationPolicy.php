<?php

namespace App\Domain\Fleet\Policies;

use App\Domain\Fleet\Models\Reservation;
use App\Domain\Identity\Models\User;

class ReservationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('reservations.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Reservation $reservation): bool
    {
        return $user->hasPermissionTo('reservations.view') && $user->agency_id === $reservation->agency_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('reservations.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Reservation $reservation): bool
    {
        return $user->hasPermissionTo('reservations.update') && $user->agency_id === $reservation->agency_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Reservation $reservation): bool
    {
        return $user->hasPermissionTo('reservations.update') && $user->agency_id === $reservation->agency_id;
    }
}
