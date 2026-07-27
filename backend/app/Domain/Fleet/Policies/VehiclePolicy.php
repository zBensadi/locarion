<?php

namespace App\Domain\Fleet\Policies;

use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Identity\Models\User;

class VehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('fleet.view');
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        return $user->agency_id === $vehicle->agency_id && $user->hasPermissionTo('fleet.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('fleet.create');
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return $user->agency_id === $vehicle->agency_id && $user->hasPermissionTo('fleet.update');
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->agency_id === $vehicle->agency_id && $user->hasPermissionTo('fleet.delete');
    }
}
